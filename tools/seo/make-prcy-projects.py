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

Колонки повторяют формат выгрузки pr-cy («Позиции ключевых слов»), чтобы импорт
опознал их без ручного маппинга: Запрос;Группы;Целевой url

Группа = «раздел | страна», но только если по связке набирается MIN_IN_GROUP
запросов — иначе страна схлопывается в «прочие страны», чтобы отчёт не рассыпался
на группы по одному запросу. Полные 5 меток (включая интент и сезон) остаются
в размеченном CSV, он и есть источник для разбора.

Запуск:
  python3 tools/seo/make-prcy-projects.py <clustered.csv> <выходная-папка>
"""
import csv, os, sys
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


def write(path, rows, group_of, title, verbose=True):
    rows = sorted(rows, key=lambda r: (group_of(r), -int(r['Частотность'])))
    with open(path, 'w', encoding='utf-8-sig', newline='') as f:
        w = csv.writer(f, delimiter=';', quoting=csv.QUOTE_MINIMAL)
        w.writerow(['Запрос', 'Группы', 'Целевой url'])
        for r in rows:
            w.writerow([r['Запрос'], group_of(r),
                        'https://bsigroup.ru' + r['Целевой URL (гипотеза)']])
    freq = sum(int(r['Частотность']) for r in rows)
    gc = Counter(group_of(r) for r in rows)
    print(f'{path}\n  {title}\n  {len(rows)} запросов, {len(gc)} групп, частотность {freq}')
    if verbose:
        for g, n in gc.most_common():
            print(f'    {n:>4}  {g}')
    print()


def main(src, outdir):
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig'), delimiter=';'))
    os.makedirs(outdir, exist_ok=True)

    # основной файл: группы считаются по всей семантике разом, чтобы «прочие страны»
    # не разъезжались между тирами
    group_of = make_grouper(rows)
    write(os.path.join(outdir, '00-vse-zaprosy.csv'), rows, group_of,
          'ВСЕ ЗАПРОСЫ — лить поверх существующего проекта, история сохраняется')

    print('--- справочные срезы по тирам (отдельные проекты = потеря истории) ---\n')
    for t, (fname, title) in PROJECTS.items():
        sub = [r for r in rows if r['Тир'] == t]
        if sub:
            write(os.path.join(outdir, f'{fname}.csv'), sub, group_of, title, verbose=False)


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
