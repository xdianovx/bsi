#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Разбор выгрузок Яндекс.Вебмастера «запрос → URL» в формате XLSX.

Вебмастер отдаёт широкую таблицу: колонки Query, Url и дальше по пять полей
на каждый период — shows, position, demand, ctr, clicks. Периоды бывают
дневные («2026-09-13_shows») и месячные («2026-08_shows»).

Файл читается без openpyxl — распаковкой xlsx и разбором XML: в окружении
MAMP ставить пакеты нельзя (PEP 668), а формат тут простой, все значения
записаны инлайн, sharedStrings пустой.

Запуск:
  python3 tools/seo/parse-webmaster-xlsx.py <файл.xlsx> [--from ПЕРИОД]

Печатает сводку по периодам, страницам и запросам: показы, клики, CTR,
среднюю позицию, спрос.
"""
import html
import re
import sys
import zipfile
from collections import defaultdict

CELL_RX = re.compile(
    r'<c[^>]*?(?:\st="(\w+)")?[^>]*>(?:<v>(.*?)</v>|<is>(.*?)</is>)?</c>', re.S)
ROW_RX = re.compile(r'<row[^>]*>(.*?)</row>', re.S)
METRICS = ('shows', 'position', 'demand', 'ctr', 'clicks')


def cells(row_xml):
    out = []
    for _type, value, inline in CELL_RX.findall(row_xml):
        out.append(html.unescape(re.sub(r'<[^>]+>', '', inline)) if inline else (value or ''))
    return out


def read(path):
    """-> (записи, периоды). Запись: {q, url, <период>: {метрика: число}}."""
    with zipfile.ZipFile(path) as z:
        sheet = z.read('xl/worksheets/sheet1.xml').decode('utf-8', 'ignore')

    rows = ROW_RX.findall(sheet)
    head = cells(rows[0])
    index = {name: i for i, name in enumerate(head)}
    periods = sorted({h.rsplit('_', 1)[0] for h in head if '_' in h})

    def num(row, i):
        try:
            return float(row[i]) if 0 <= i < len(row) and row[i] else 0.0
        except ValueError:
            return 0.0

    data = []
    for row_xml in rows[1:]:
        c = cells(row_xml)
        if len(c) < 3:
            continue
        rec = {'q': c[0], 'url': c[1]}
        for p in periods:
            rec[p] = {m: num(c, index.get(f'{p}_{m}', -1)) for m in METRICS}
        data.append(rec)

    return data, periods


def total(rec, metric, periods):
    return sum(rec[p][metric] for p in periods)


def avg_position(rec, periods):
    vals = [rec[p]['position'] for p in periods if rec[p]['position'] > 0]
    return sum(vals) / len(vals) if vals else 0.0


def ctr(clicks, shows):
    return 100 * clicks / shows if shows else 0.0


def main(path, since=None):
    data, periods = read(path)
    live = [p for p in periods if not since or p >= since]

    print(f'запросов: {len(data)}, периоды: {periods[0]} — {periods[-1]}\n')
    print('%-10s %10s %9s %8s' % ('период', 'показы', 'клики', 'CTR'))
    for p in live:
        s = sum(d[p]['shows'] for d in data)
        c = sum(d[p]['clicks'] for d in data)
        if s or c:
            print('%-10s %10.0f %9.0f %7.2f%%' % (p, s, c, ctr(c, s)))

    pages = defaultdict(lambda: {'shows': 0.0, 'clicks': 0.0, 'queries': 0, 'pos': []})
    for d in data:
        p = pages[d['url'] or '(нет)']
        p['shows'] += total(d, 'shows', live)
        p['clicks'] += total(d, 'clicks', live)
        p['queries'] += 1
        if avg_position(d, live):
            p['pos'].append(avg_position(d, live))

    print('\n%-52s %9s %8s %7s %6s %5s' % ('URL', 'показы', 'клики', 'CTR', 'поз', 'зпр'))
    for url, p in sorted(pages.items(), key=lambda kv: -kv[1]['shows'])[:25]:
        pos = sum(p['pos']) / len(p['pos']) if p['pos'] else 0
        print('%-52s %9.0f %8.0f %6.1f%% %6.1f %5d'
              % (url[:52], p['shows'], p['clicks'], ctr(p['clicks'], p['shows']), pos, p['queries']))


if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.exit('нужен путь к xlsx')
    src = sys.argv[1]
    frm = sys.argv[sys.argv.index('--from') + 1] if '--from' in sys.argv else None
    main(src, frm)
