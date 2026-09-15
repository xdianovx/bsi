#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Режет размеченную семантику на 3 файла для импорта в pr-cy — по одному на проект.

Колонки повторяют формат выгрузки pr-cy («Позиции ключевых слов»), чтобы импорт
опознал их без ручного маппинга: Запрос;Группы;Целевой url

Группа в pr-cy = «раздел | страна», но только если по этой связке набирается
MIN_IN_GROUP запросов — иначе страна схлопывается в «прочие страны», чтобы отчёт
не рассыпался на группы по одному запросу. Полные 5 меток (включая интент и сезон)
остаются в размеченном CSV, он и есть источник для разбора.

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


def main(src, outdir):
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig'), delimiter=';'))
    os.makedirs(outdir, exist_ok=True)

    for t, (fname, title) in PROJECTS.items():
        sub = [r for r in rows if r['Тир'] == t]
        if not sub:
            continue
        pair = Counter((r['Раздел'], r['Гео']) for r in sub)

        def group_of(r):
            sec, geo = r['Раздел'], r['Гео']
            if geo == '-':
                return f'{sec} | без гео'
            if pair[(sec, geo)] < MIN_IN_GROUP:
                return f'{sec} | прочие страны'
            return f'{sec} | {geo}'

        sub.sort(key=lambda r: (group_of(r), -int(r['Частотность'])))
        path = os.path.join(outdir, f'{fname}.csv')
        with open(path, 'w', encoding='utf-8-sig', newline='') as f:
            w = csv.writer(f, delimiter=';', quoting=csv.QUOTE_MINIMAL)
            w.writerow(['Запрос', 'Группы', 'Целевой url'])
            for r in sub:
                w.writerow([r['Запрос'], group_of(r),
                            'https://bsigroup.ru' + r['Целевой URL (гипотеза)']])
        freq = sum(int(r['Частотность']) for r in sub)
        gc = Counter(group_of(r) for r in sub)
        groups = len(gc)
        print(f'{path}\n  {title}\n  {len(sub)} запросов, {groups} групп, частотность {freq}')
        for g, n in gc.most_common():
            print(f'    {n:>4}  {g}')
        print()


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
