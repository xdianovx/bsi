#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Кластеризация семантики из выгрузки pr-cy «Позиции ключевых слов».

Каждому запросу проставляется 5 меток:
  Тир     — ядро / рост / амбиции / бренд  (по текущей позиции и частотности)
  Раздел  — CPT/раздел сайта, под который запрос ведёт
  Гео     — страна (slug из CPT country) или «-»
  Интент  — commercial / transact / info / nav
  Сезон   — год / лето / зима / майские / нг

Запуск:
  python3 tools/seo/cluster-keywords.py <in.csv> <out.csv>
"""
import csv, re, sys, unicodedata
from collections import Counter, defaultdict

# --- справочник стран: варианты в запросе -> (Название, slug) -------------
COUNTRIES = {}
COUNTRY_SRC = """
Австрия|avstriya|австри
Азербайджан|azerbajdzhan|азербайджан|баку
Албания|albaniya|албани
Армения|armeniya|армени|ереван
Бахрейн|bahrejn|бахрейн
Белоруссия|belorussiya|белорус|беларус|минск
Бельгия|belgiya|бельги|брюссел
Бруней|brunej|бруней
Бутан|butan|бутан
Великобритания|velikobritaniya|великобритани|англи|лондон|британи|шотланди
Венгрия|vengriya|венгри|будапешт
Вьетнам|vetnam|вьетнам|нячанг|фукуок
Германия|germaniya|германи|берлин|мюнхен
Греция|grecziya|греци|афин|крит|родос
Грузия|gruziya|грузи|тбилиси|батуми
Индия|indiya|инди|гоа|дели
Индонезия|indoneziya|индонези|бали
Ирландия|irlandiya|ирланди|дублин
Испания|ispaniya|испани|барселон|мадрид|тенерифе|майорк
Италия|italiya|итали|рим|венеци|милан|флоренци|сицили
Казахстан|kazahstan|казахстан|алмат|астан
Камбоджа|kambodzha|камбодж
Катар|katar|катар|доха
Кипр|kipr|кипр|айя-напа|лимассол
Китай|kitaj|кита|пекин|шанха|санья|хайнан
Лаос|laos|лаос
Люксембург|lyuksemburg|люксембург
Маврикий|mavrikij|маврики
Малайзия|malajziya|малайзи|куала
Мальдивы|maldivy|мальдив
Мьянма|myanma|мьянм|бирм
Непал|nepal|непал
Нидерланды|niderlandy|нидерланд|голланди|амстердам
ОАЭ|oae|оаэ|эмират|дубай|абу-даби|абу даби
Оман|oman|оман|маскат
Португалия|portugaliya|португали|лиссабон|мадейр
Россия|rossiya|росси|москв|петербург|сочи|калининград|карели|байкал|камчатк|алтай|кавказ
Саудовская Аравия|saudovskaya-araviya|саудов
Сейшелы|sejshely|сейшел
Сербия|serbia|серби|белград
Сингапур|singapur|сингапур
Словакия|slovakiya|словаки
Словения|sloveniya|словени|любляна
США|ssha|сша|америк|нью-йорк|нью йорк
Таиланд|tailand|таиланд|тайланд|пхукет|паттай|бангкок|самуи
Турция|turcziya|турци|анталь|стамбул|кемер|бодрум|алань|сиде|мармарис
Узбекистан|uzbekistan|узбекистан|ташкент|самарканд|бухар
Филиппины|filippiny|филиппин
Финляндия|finlyandiya|финлянди|хельсинки|лаплан
Франция|francziya|франци|париж|ницц|прованс|лазурн
Хорватия|hovatiya|хорвати|дубровник
Черногория|chernogoriya|черногори|будв|котор
Чехия|chehiya|чехи|праг|карловы
Швейцария|shvejczariya|швейцари|женев|цюрих
Шри-Ланка|shri-lanka|шри-ланк|шри ланк|цейлон
Южная Корея|yuzhnaya-koreya|корея|корее|корею|сеул|корейск
Япония|yaponiya|япони|токио|киото
"""
for line in COUNTRY_SRC.strip().splitlines():
    parts = line.split('|')
    name, slug, aliases = parts[0], parts[1], parts[2:]
    for a in aliases:
        COUNTRIES[a] = (name, slug)
# длинные алиасы проверяем первыми, чтобы «абу-даби» не съелся «баку» и т.п.
COUNTRY_ALIASES = sorted(COUNTRIES, key=len, reverse=True)

# --- разделы сайта: (регексп по запросу, раздел, шаблон URL) --------------
SECTIONS = [
    (r'\b(bsi|бси|би\s?си\s?ай|би\s?эс\s?ай|bsigroup)\b', 'brand',     '/'),
    (r'круиз',                                            'cruise',    '/kruizy/'),
    (r'виз[аыуе]\b|визов|шенген',                         'visa',      '/country/{c}/visa/'),
    (r'страхов|страховк',                                 'insurance', '/strahovanie/'),
    (r'обучен|образован|учеб|универс|школ|языков',        'education', '/country/{c}/obuchenie/'),
    (r'событийн|концерт|фестивал|матч|гран-при|формул',   'event',     '/sobytiynye-tury/'),
    (r'экскурс',                                          'excursion', '/country/{c}/ekskursii/'),
    (r'отел|гостиниц|проживан',                           'hotel',     '/country/{c}/hotel/'),
    (r'достопримечат|что посмотреть',                     'sight',     '/country/{c}/dostoprimechatelnosti/'),
    (r'турагент|агентств|франшиз|партнёр|партнер',        'agency',    '/agentstvam/'),
    (r'mice|корпоратив|инсентив|деловой тур',             'mice',      '/business/'),
    (r'\bтур|путёвк|путевк|отдых|поездк',                 'tour',      '/country/{c}/tours/'),
]

INTENT = [
    (r'куп|заброниров|бронирован|заказать|цена|цены|стоимость|сколько стоит|дёшев|дешев|горящ', 'transact'),
    (r'\bтур|путёвк|путевк|отел|виза|страхов|круиз|обучен',                                     'commercial'),
    (r'что посмотреть|достопримечат|как добраться|отзыв|погода|когда лучше|нужна ли|виза нужна','info'),
]

SEASON = [
    (r'новый год|нг\b|рождеств',          'нг'),
    (r'майск|9 мая|1 мая|праздничн',      'майские'),
    (r'зим|горнолыж|лыж|снег|январ|феврал','зима'),
    (r'лет[оне]|июн|июл|август|пляж|море','лето'),
]

# посадочная, когда страна в запросе не названа
FALLBACK = {
    'tour': '/country/', 'excursion': '/country/', 'hotel': '/country/',
    'sight': '/country/', 'education': '/obrazovanie-za-rubezhom/',
    'visa': '/visa/', 'cruise': '/kruizy/', 'insurance': '/strahovanie/',
    'event': '/sobytiynye-tury/', 'agency': '/agentstvam/', 'mice': '/business/',
    'brand': '/', 'other': '/',
}

def norm(s):
    return unicodedata.normalize('NFKC', s).lower().replace('ё', 'е').strip()

def detect_country(q):
    for a in COUNTRY_ALIASES:
        if a.replace('ё', 'е') in q:
            return COUNTRIES[a]
    return ('-', '-')

def detect_section(q):
    for rx, sec, url in SECTIONS:
        if re.search(rx, q):
            return sec, url
    return 'other', '/'

def detect_intent(q):
    for rx, it in INTENT:
        if re.search(rx, q):
            return it
    return 'info'

def detect_season(q):
    for rx, s in SEASON:
        if re.search(rx, q):
            return s
    return 'год'

# страницы, которых нет (проверено HEAD-запросом по проду)
URL_OVERRIDE = {
    '/country/rossiya/visa/': '/visa/',
}


def tier(pos, freq, section):
    """
    Тир задаёт частоту съёма позиций и то, в какой проект pr-cy попадёт запрос.
      1-ядро    — бренд и топ-30: ежедневно, это рабочий отчёт
      2-рост    — 31-100: еженедельно, сюда дотягиваем
      3-амбиции — вне топ-100: раз в месяц, чтобы не шумели
    """
    if section == 'brand':
        return '1-ядро'
    if pos == -1:
        return '3-амбиции'
    if pos <= 30:
        return '1-ядро'
    return '2-рост'

def main(src, dst):
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig'), delimiter=';'))
    out = []
    for r in rows:
        q = norm(r['Запрос'])
        pos = int(r['Текущая позиция'] or -1)
        freq = int(r['Частотность'] or 0)
        sec, url_tpl = detect_section(q)
        cname, cslug = detect_country(q)
        t = tier(pos, freq, sec)
        target = url_tpl.replace('{c}', cslug) if cslug != '-' else FALLBACK.get(sec, '/')
        target = URL_OVERRIDE.get(target, target)
        rel = (r['Релевантный url'] or '').replace('https://www.bsigroup.ru', '').replace('https://bsigroup.ru', '')
        out.append({
            'Запрос': r['Запрос'],
            'Частотность': freq,
            'Позиция': pos if pos > 0 else '',
            'Тир': t,
            'Раздел': sec,
            'Гео': cname,
            'Интент': detect_intent(q),
            'Сезон': detect_season(q),
            'Группа (тег для pr-cy)': f'{t}|{sec}|{cslug}|{detect_intent(q)}|{detect_season(q)}',
            'Целевой URL (гипотеза)': target,
            'Релевантный URL (факт)': rel,
            'Совпало': 'да' if rel and target.rstrip('/') == rel.rstrip('/') else ('' if not rel else 'нет'),
        })
    out.sort(key=lambda r: (r['Тир'], r['Раздел'], -r['Частотность']))
    with open(dst, 'w', encoding='utf-8-sig', newline='') as f:
        w = csv.DictWriter(f, fieldnames=list(out[0].keys()), delimiter=';')
        w.writeheader()
        w.writerows(out)

    # --- сводка в stdout ---
    def s(rows_): return sum(r['Частотность'] for r in rows_)
    print(f'Всего запросов: {len(out)}, суммарная частотность: {s(out)}\n')
    print('ТИР                зпр   частотность')
    for t, c in sorted(Counter(r['Тир'] for r in out).items()):
        sub = [r for r in out if r['Тир'] == t]
        print(f'{t:<16}{c:>5}   {s(sub):>10}')
    print('\nРАЗДЕЛ             зпр   частотность   в топ-30')
    for sec, c in Counter(r['Раздел'] for r in out).most_common():
        sub = [r for r in out if r['Раздел'] == sec]
        top = sum(1 for r in sub if r['Позиция'] != '' and r['Позиция'] <= 30)
        print(f'{sec:<16}{c:>5}   {s(sub):>10}   {top:>5}')
    print('\nГЕО (топ-12)       зпр   частотность   лучшая поз.')
    geo = defaultdict(list)
    for r in out: geo[r['Гео']].append(r)
    for g, sub in sorted(geo.items(), key=lambda kv: -s(kv[1]))[:12]:
        poss = [r['Позиция'] for r in sub if r['Позиция'] != '']
        print(f'{g:<16}{len(sub):>5}   {s(sub):>10}   {min(poss) if poss else "—":>5}')
    print('\nИНТЕНТ / СЕЗОН')
    print(' ', dict(Counter(r['Интент'] for r in out)))
    print(' ', dict(Counter(r['Сезон'] for r in out)))
    mism = [r for r in out if r['Совпало'] == 'нет']
    print(f'\nURL не совпал с гипотезой: {len(mism)} (проверить каннибализацию)')
    print(f'Записано: {dst}')

if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
