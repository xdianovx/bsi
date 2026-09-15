#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Готовит размеченную семантику к импорту в pr-cy.

Основной файл — `00-vse-zaprosy.csv`: все запросы разом, для заливки поверх
существующего проекта. Так сохраняется история съёма позиций.

Важно: в группы попадают только СТАТИЧНЫЕ метки — раздел и гео. Тир (ядро/рост/
амбиции) меняется вместе с позицией, зашивать его в группу или в отдельный проект
нельзя: запрос, вышедший в топ-100, застрянет не там, а перенос между проектами
обнуляет историю. Тир смотрится фильтром по позиции, она в pr-cy и так есть.

Файлы `01..03` по тирам пишутся рядом как справочные срезы — они нужны, если
хочется развести частоту съёма по отдельным проектам, ценой потери истории.

Формат импорта pr-cy (проверен на ошибке валидатора, отличается от формата выгрузки):
  Запросы;"Целевая ссылка";"Имя группы";ГГГГ-ММ-ДД[;ГГГГ-ММ-ДД...]
Колонки дат обязательны — хотя бы одна. Позиции берутся из исходной выгрузки,
отсутствие в топ-100 (в выгрузке `-1`) записывается как `--`.

Группа = «раздел | страна», но только если по связке набирается MIN_IN_GROUP
запросов — иначе страна схлопывается в «прочие страны», чтобы отчёт не рассыпался
на группы по одному запросу. Полные 5 меток (включая интент и сезон) остаются
в размеченном CSV, он и есть источник для разбора.

Запуск:
  python3 tools/seo/make-prcy-projects.py <clustered.csv> <исходная-выгрузка.csv> <выходная-папка>
"""
import csv, os, re, sys
from collections import Counter

# меньше этого числа запросов в связке «раздел+страна» — страна уходит в «прочие»
MIN_IN_GROUP = 3

PROJECTS = {
    '1-ядро':    ('01-yadro',    'Ядро — бренд и топ-30, снимать ежедневно'),
    '2-рост':    ('02-rost',     'Рост — позиции 31-100, снимать еженедельно'),
    '3-амбиции': ('03-ambicii',  'Амбиции — вне топ-100, снимать раз в месяц'),
}


def make_grouper(rows):
    """Группа = «раздел | страна»; редкие страны схлопываются в «прочие»."""
    pair = Counter((r['Раздел'], r['Гео']) for r in rows)

    def group_of(r):
        sec, geo = r['Раздел'], r['Гео']
        if geo == '-':
            return f'{sec} | без гео'
        if pair[(sec, geo)] < MIN_IN_GROUP:
            return f'{sec} | прочие страны'
        return f'{sec} | {geo}'

    return group_of


DATE_RX = re.compile(r'^\d{4}-\d{2}-\d{2}$')


def read_history(src):
    """Позиции по датам из исходной выгрузки pr-cy: {запрос: {дата: позиция}}."""
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig'), delimiter=';'))
    dates = [k for k in rows[0] if DATE_RX.match(k)]
    dates.sort(reverse=True)
    hist = {}
    for r in rows:
        # в выгрузке «вне топ-100» это -1, импорт ждёт «--»
        hist[r['Запрос']] = {d: ('--' if r[d].strip() in ('-1', '') else r[d].strip())
                             for d in dates}
    return dates, hist


def write(path, rows, group_of, title, dates, hist, verbose=True):
    rows = sorted(rows, key=lambda r: (group_of(r), -int(r['Частотность'])))
    with open(path, 'w', encoding='utf-8-sig', newline='') as f:
        w = csv.writer(f, delimiter=';', quoting=csv.QUOTE_MINIMAL)
        w.writerow(['Запросы', 'Целевая ссылка', 'Имя группы'] + dates)
        for r in rows:
            h = hist.get(r['Запрос'], {})
            w.writerow([r['Запрос'],
                        'https://bsigroup.ru' + r['Целевой URL (гипотеза)'],
                        group_of(r)] + [h.get(d, '--') for d in dates])
    freq = sum(int(r['Частотность']) for r in rows)
    gc = Counter(group_of(r) for r in rows)
    print(f'{path}\n  {title}\n  {len(rows)} запросов, {len(gc)} групп, частотность {freq}')
    if verbose:
        for g, n in gc.most_common():
            print(f'    {n:>4}  {g}')
    print()


def main(src, export, outdir):
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig'), delimiter=';'))
    dates, hist = read_history(export)
    os.makedirs(outdir, exist_ok=True)

    missing = [r['Запрос'] for r in rows if r['Запрос'] not in hist]
    if missing:
        print(f'! нет истории у {len(missing)} запросов, им проставлено «--»\n')

    # основной файл: группы считаются по всей семантике разом, чтобы «прочие страны»
    # не разъезжались между тирами
    group_of = make_grouper(rows)
    write(os.path.join(outdir, '00-vse-zaprosy.csv'), rows, group_of,
          'ВСЕ ЗАПРОСЫ — основной файл импорта', dates, hist)

    print('--- справочные срезы по тирам (отдельные проекты = потеря истории) ---\n')
    for t, (fname, title) in PROJECTS.items():
        sub = [r for r in rows if r['Тир'] == t]
        if sub:
            write(os.path.join(outdir, f'{fname}.csv'), sub, group_of, title,
                  dates, hist, verbose=False)


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2], sys.argv[3])
