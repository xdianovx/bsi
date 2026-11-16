<?php

/**
 * Админка → «Заявки» → «Выгрузить в Excel».
 *
 * Модалка на странице заявок (leads-page.php) шлёт POST на admin-post.php?action=bsi_leads_export:
 * период, формы, колонки. Ответ — .xlsx, собранный вручную (ZipArchive + SpreadsheetML),
 * без сторонних библиотек: заголовок, шапка с заливкой, зебра, закреплённая шапка, автофильтр.
 * Только manage_options + nonce.
 */

defined('ABSPATH') || exit;

/** Колонки выгрузки: ключ => [подпись, ширина в символах, включена по умолчанию, группа в модалке]. */
function bsi_leads_export_columns(): array
{
  return [
    'created_at' => ['Дата', 17, true, 'main'],
    'form' => ['Форма', 20, true, 'main'],
    'title' => ['Что / страница', 40, true, 'main'],
    'id' => ['№', 7, false, 'main'],
    'name' => ['Имя', 24, true, 'contacts'],
    'phone' => ['Телефон', 18, true, 'contacts'],
    'email' => ['Email', 28, true, 'contacts'],
    'payload' => ['Поля заявки', 0, true, 'details'],
    'utm' => ['UTM-метки', 0, false, 'details'],
    'page_url' => ['Ссылка на страницу', 45, false, 'details'],
    'mail_sent' => ['Письмо ушло', 13, false, 'details'],
  ];
}

/**
 * AJAX для модалки: сколько заявок по каждой форме за период (from/to в Y-m-d).
 * Только счётчики, без персональных данных.
 */
add_action('wp_ajax_bsi_leads_export_count', function () {
  if (!current_user_can('manage_options')) {
    wp_send_json_error(null, 403);
  }
  check_ajax_referer('bsi_leads_export', 'nonce');

  $tz = wp_timezone();
  $from = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_POST['from'] ?? '')), $tz);
  $to = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_POST['to'] ?? '')), $tz);
  if (!$from || !$to) {
    wp_send_json_success(['forms' => new stdClass()]);
  }
  if ($from > $to) {
    [$from, $to] = [$to, $from];
  }

  global $wpdb;
  $rows = $wpdb->get_results($wpdb->prepare(
    'SELECT form, COUNT(*) c FROM ' . bsi_leads_table_name() . ' WHERE created_at >= %s AND created_at < %s GROUP BY form',
    $from->format('Y-m-d H:i:s'),
    $to->modify('+1 day')->format('Y-m-d H:i:s')
  ), ARRAY_A);

  wp_send_json_success(['forms' => (object) array_map('intval', array_column($rows, 'c', 'form'))]);
});

/** Подписи полей payload для шапки. Неизвестный ключ выводится как есть. */
function bsi_leads_export_payload_labels(): array
{
  return [
    'comment' => 'Комментарий', 'comments' => 'Комментарий', 'message' => 'Сообщение', 'wishes' => 'Пожелания',
    'adults' => 'Взрослых', 'adults_count' => 'Взрослых', 'children' => 'Детей', 'children_count' => 'Детей',
    'children_ages' => 'Возраст детей', 'age' => 'Возраст', 'budget' => 'Бюджет',
    'check_in' => 'Дата заезда', 'checkin_date' => 'Дата заезда', 'nights' => 'Ночей',
    'duration' => 'Длительность', 'tour_duration' => 'Длительность тура',
    'date' => 'Дата', 'date_from' => 'Дата с', 'date_to' => 'Дата по', 'travel_dates' => 'Даты поездки',
    'city' => 'Город', 'country' => 'Страна', 'citizenship' => 'Гражданство', 'region' => 'Регион',
    'resort' => 'Курорт', 'resort_name' => 'Курорт',
    'company' => 'Компания', 'company_name' => 'Компания', 'inn' => 'ИНН', 'client_type' => 'Тип клиента',
    'hotel' => 'Отель', 'hotel_title' => 'Отель', 'hotel_stars' => 'Звёздность', 'room_name' => 'Номер',
    'accommodation' => 'Размещение', 'offer_dates' => 'Даты тарифа', 'offer_meal' => 'Питание', 'offer_price' => 'Цена тарифа',
    'quantity' => 'Количество', 'sector' => 'Сектор',
    'event_date' => 'Дата события', 'event_time' => 'Время события', 'event_venue' => 'Место проведения',
    'event_price' => 'Цена события', 'event_details' => 'Детали события', 'event_kind' => 'Тип события',
    'booking_kind' => 'Вид бронирования', 'price_line' => 'Цена', 'total_price' => 'Итого',
    'selected_services' => 'Услуги', 'services' => 'Услуги',
    'program' => 'Программа', 'program_price' => 'Цена программы', 'program_date' => 'Дата программы',
    'program_duration' => 'Длительность программы', 'program_language' => 'Язык', 'language' => 'Язык',
    'school_name' => 'Школа', 'resume_name' => 'Резюме',
    'excursion_date' => 'Дата экскурсии', 'excursion_format' => 'Формат экскурсии',
    'excursion_transport' => 'Транспорт', 'excursion_type' => 'Тип экскурсии',
    'insurance_type' => 'Тип страховки', 'visa_type' => 'Тип визы', 'visa_type_label' => 'Тип визы',
    'visa_country_title' => 'Страна визы',
    'tour' => 'Тур', 'tour_title' => 'Тур', 'tour_price' => 'Цена тура', 'tour_type' => 'Тип тура',
    'group_arrival' => 'Групповой заезд', 'claim' => 'Заявка Samo', 'referrer' => 'Откуда пришёл',
    'sight_title' => 'Достопримечательность', 'sight_country' => 'Страна',
    'source_page_title' => 'Страница-источник', 'source_page_url' => 'URL источника', 'method' => 'Способ связи',
  ];
}

/** Служебные ключи payload, которым не нужна своя колонка. */
function bsi_leads_export_payload_skip(string $key): bool
{
  return in_array($key, ['reply_to', 'booking_context', 'attachments', 'visa_page_slug'], true)
    || (bool) preg_match('/_(id|ids|nonce)$/', $key);
}

/**
 * Колонки листа из выбранных: payload раскрывается в колонку на каждое поле,
 * которое встречается в выгружаемых заявках (порядок — по частоте), UTM — в три колонки.
 * Элемент: ['key' => ..., 'label' => ..., 'width' => ...].
 */
function bsi_leads_export_sheet_columns(array $selected, array $rows): array
{
  $defs = bsi_leads_export_columns();
  $out = [];
  foreach ($selected as $col) {
    if ($col === 'utm') {
      foreach (['utm_source' => 'utm_source', 'utm_medium' => 'utm_medium', 'utm_campaign' => 'utm_campaign'] as $key => $label) {
        $out[] = ['key' => $key, 'label' => $label, 'width' => 18];
      }
      continue;
    }
    if ($col !== 'payload') {
      $out[] = ['key' => $col, 'label' => $defs[$col][0], 'width' => $defs[$col][1]];
      continue;
    }

    $labels = bsi_leads_export_payload_labels();
    $stats = [];
    foreach ($rows as $lead) {
      foreach ($lead['_payload'] as $key => $value) {
        $key = (string) $key;
        if (bsi_leads_export_payload_skip($key)) {
          continue;
        }
        $stats[$key]['count'] = ($stats[$key]['count'] ?? 0) + 1;
        $stats[$key]['len'] = max($stats[$key]['len'] ?? 0, mb_strlen(bsi_leads_export_payload_value($value)));
      }
    }
    uasort($stats, static fn($a, $b) => $b['count'] <=> $a['count']);
    foreach ($stats as $key => $stat) {
      $label = $labels[$key] ?? $key;
      $out[] = ['key' => 'payload:' . $key, 'label' => $label, 'width' => min(45, max(12, mb_strlen($label) + 3, $stat['len'] + 2))];
    }
  }

  return $out;
}

function bsi_leads_export_payload_value($value): string
{
  return is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
}

add_action('admin_post_bsi_leads_export', 'bsi_leads_export_send');

function bsi_leads_export_send(): void
{
  if (!current_user_can('manage_options')) {
    wp_die('Недостаточно прав.');
  }
  check_admin_referer('bsi_leads_export');
  if (!class_exists('ZipArchive')) {
    wp_die('Для выгрузки нужен PHP ZipArchive.');
  }

  global $wpdb;

  $tz = wp_timezone();
  $from = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_POST['from'] ?? '')), $tz);
  $to = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field(wp_unslash($_POST['to'] ?? '')), $tz);
  if (!$from || !$to) {
    wp_die('Укажите период: даты «с» и «по».');
  }
  if ($from > $to) {
    [$from, $to] = [$to, $from];
  }

  $all_columns = bsi_leads_export_columns();
  $columns = array_values(array_intersect(array_keys($all_columns), array_map('sanitize_key', (array) wp_unslash($_POST['columns'] ?? []))));
  if (!$columns) {
    wp_die('Выберите хотя бы одну колонку.');
  }

  $labels = bsi_leads_form_labels();
  $forms = array_values(array_intersect(array_keys($labels), array_map('sanitize_key', (array) wp_unslash($_POST['forms'] ?? []))));
  if (!$forms) {
    wp_die('Выберите хотя бы одну форму.');
  }
  $all_forms = count($forms) === count($labels);

  $where = 'created_at >= %s AND created_at < %s';
  $args = [$from->format('Y-m-d H:i:s'), $to->modify('+1 day')->format('Y-m-d H:i:s')];
  if (!$all_forms) {
    $where .= ' AND form IN (' . implode(',', array_fill(0, count($forms), '%s')) . ')';
    $args = array_merge($args, $forms);
  }
  $rows = $wpdb->get_results(
    $wpdb->prepare('SELECT * FROM ' . bsi_leads_table_name() . " WHERE {$where} ORDER BY created_at ASC, id ASC", $args),
    ARRAY_A
  );
  foreach ($rows as &$lead) {
    $lead['_payload'] = $lead['payload'] ? (array) json_decode((string) $lead['payload'], true) : [];
  }
  unset($lead);
  $sheet_columns = bsi_leads_export_sheet_columns($columns, $rows);
  if (!$sheet_columns) {
    $sheet_columns = [['key' => 'created_at', 'label' => 'Дата', 'width' => 17]];
  }

  $forms_note = $all_forms ? 'все формы' : implode(', ', array_map(static fn($f) => $labels[$f], $forms));
  $meta = [
    'title' => 'Заявки с сайта BSI Group',
    'subtitle' => sprintf(
      'Период: %s — %s  ·  Формы: %s  ·  Заявок: %d  ·  Выгружено %s',
      $from->format('d.m.Y'),
      $to->format('d.m.Y'),
      $forms_note,
      count($rows),
      wp_date('d.m.Y H:i')
    ),
  ];

  $path = wp_tempnam('bsi-leads.xlsx');
  try {
    bsi_leads_export_build_xlsx($path, $sheet_columns, $rows, $meta);
  } catch (\Throwable $e) {
    @unlink($path);
    wp_die('Не удалось сформировать файл: ' . esc_html($e->getMessage()));
  }

  $filename = sprintf('zayavki_%s_%s.xlsx', $from->format('Y-m-d'), $to->format('Y-m-d'));
  // Метка для модалки: по ней кнопка понимает, что файл готов, и снимает «Готовим файл…».
  $token = preg_replace('/\D/', '', (string) ($_POST['download_token'] ?? ''));
  if ($token !== '') {
    setcookie('bsi_leads_export_done', $token, [
      'expires' => time() + 10 * MINUTE_IN_SECONDS,
      'path' => '/',
      'secure' => is_ssl(),
      'httponly' => false,
      'samesite' => 'Lax',
    ]);
  }
  nocache_headers();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Content-Length: ' . (string) filesize($path));
  readfile($path);
  unlink($path);
  exit;
}

/** Значение ячейки для заявки: строка или ['date' => serial] для даты. */
function bsi_leads_export_value(array $lead, string $column)
{
  $anonymized = !empty($lead['anonymized_at']);
  if (str_starts_with($column, 'payload:')) {
    $value = $lead['_payload'][substr($column, 8)] ?? '';
    return bsi_leads_export_payload_value($value);
  }
  switch ($column) {
    case 'id':
      return (int) $lead['id'];
    case 'created_at':
      $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $lead['created_at'], new DateTimeZone('UTC'));
      return $dt ? ['date' => $dt->getTimestamp() / DAY_IN_SECONDS + 25569] : (string) $lead['created_at'];
    case 'form':
      return bsi_leads_form_labels()[$lead['form']] ?? (string) $lead['form'];
    case 'title':
      return $lead['title'] !== '' ? (string) $lead['title'] : (string) $lead['page_url'];
    case 'name':
    case 'phone':
    case 'email':
      $value = (string) ($lead[$column] ?? '');
      return $value === '' && $anonymized ? 'обезличено' : $value;
    case 'mail_sent':
      return $lead['mail_sent'] ? 'да' : 'нет';
    default:
      return (string) ($lead[$column] ?? '');
  }
}

function bsi_leads_export_xml(string $value): string
{
  $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';

  return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function bsi_leads_export_col(int $index): string
{
  $letters = '';
  for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
    $letters = chr(65 + ($n - 1) % 26) . $letters;
  }

  return $letters;
}

/**
 * Собрать .xlsx. Стили (индексы cellXfs):
 * 1 — заголовок, 2 — подзаголовок, 3 — шапка, 4/5 — текст/дата, 6/7 — то же с заливкой зебры, 8/9 — число.
 */
function bsi_leads_export_build_xlsx(string $path, array $columns, array $rows, array $meta): void
{
  $last_col = bsi_leads_export_col(count($columns) - 1);
  $header_row = 3;
  $last_row = $header_row + max(1, count($rows));

  $sheet_file = wp_tempnam('bsi-leads-sheet.xml');
  $fh = fopen($sheet_file, 'wb');
  if (!$fh) {
    throw new RuntimeException('не открыть временный файл');
  }

  fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheetViews><sheetView workbookViewId="0" zoomScale="110"><pane ySplit="' . $header_row . '" topLeftCell="A' . ($header_row + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
    . '<sheetFormatPr defaultRowHeight="15"/><cols>');
  foreach ($columns as $i => $col) {
    $n = $i + 1;
    fwrite($fh, '<col min="' . $n . '" max="' . $n . '" width="' . $col['width'] . '" customWidth="1"/>');
  }
  fwrite($fh, '</cols><sheetData>');

  $text_cell = static fn(string $ref, string $value, int $style): string => '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . bsi_leads_export_xml($value) . '</t></is></c>';

  fwrite($fh, '<row r="1" ht="26" customHeight="1">' . $text_cell('A1', $meta['title'], 1) . '</row>');
  fwrite($fh, '<row r="2" ht="20" customHeight="1">' . $text_cell('A2', $meta['subtitle'], 2) . '</row>');

  fwrite($fh, '<row r="' . $header_row . '" ht="30" customHeight="1">');
  foreach ($columns as $i => $col) {
    fwrite($fh, $text_cell(bsi_leads_export_col($i) . $header_row, $col['label'], 3));
  }
  fwrite($fh, '</row>');

  foreach ($rows as $r => $lead) {
    $row_num = $header_row + 1 + $r;
    $zebra = $r % 2 === 1;
    $lines_max = 1;
    $cells = '';
    foreach ($columns as $i => $col) {
      $ref = bsi_leads_export_col($i) . $row_num;
      $value = bsi_leads_export_value($lead, $col['key']);
      if (is_array($value)) {
        $cells .= '<c r="' . $ref . '" s="' . ($zebra ? 7 : 5) . '"><v>' . sprintf('%.10F', $value['date']) . '</v></c>';
        continue;
      }
      if (is_int($value)) {
        $cells .= '<c r="' . $ref . '" s="' . ($zebra ? 9 : 8) . '"><v>' . $value . '</v></c>';
        continue;
      }
      // Высоту строки Excel при открытии сам не пересчитывает — оцениваем по переносам.
      $lines = 0;
      foreach (explode("\n", $value) as $line) {
        $lines += max(1, (int) ceil(mb_strlen($line) / max(1, $col['width'] - 2)));
      }
      $lines_max = max($lines_max, $lines);
      $cells .= $text_cell($ref, $value, $zebra ? 6 : 4);
    }
    $height = min(300, 6 + 15 * $lines_max);
    fwrite($fh, '<row r="' . $row_num . '" ht="' . $height . '" customHeight="1">' . $cells . '</row>');
  }

  if (!$rows) {
    fwrite($fh, '<row r="' . ($header_row + 1) . '" ht="22" customHeight="1">' . $text_cell('A' . ($header_row + 1), 'За выбранный период заявок нет.', 2) . '</row>');
  }

  fwrite($fh, '</sheetData>');
  if ($rows) {
    fwrite($fh, '<autoFilter ref="A' . $header_row . ':' . $last_col . $last_row . '"/>');
  }
  if (count($columns) > 1) {
    fwrite($fh, '<mergeCells count="2"><mergeCell ref="A1:' . $last_col . '1"/><mergeCell ref="A2:' . $last_col . '2"/></mergeCells>');
  }
  fwrite($fh, '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
    . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>');
  fclose($fh);

  $filter_name = $rows
    ? '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">\'Заявки\'!$A$' . $header_row . ':$' . $last_col . '$' . $last_row . '</definedName></definedNames>'
    : '';

  $zip = new ZipArchive();
  if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    unlink($sheet_file);
    throw new RuntimeException('не создать архив');
  }

  $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '</Types>');

  $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>');

  $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Заявки" sheetId="1" r:id="rId1"/></sheets>' . $filter_name . '</workbook>');

  $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>');

  $border = '<border><left style="thin"><color rgb="FFE3E6EA"/></left><right style="thin"><color rgb="FFE3E6EA"/></right><top style="thin"><color rgb="FFE3E6EA"/></top><bottom style="thin"><color rgb="FFE3E6EA"/></bottom><diagonal/></border>';
  $text_align = '<alignment vertical="top" wrapText="1"/>';
  $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd.mm.yyyy hh:mm"/></numFmts>'
    . '<fonts count="4">'
    . '<font><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>'
    . '<font><b/><sz val="16"/><color rgb="FF111827"/><name val="Calibri"/><family val="2"/></font>'
    . '<font><sz val="10"/><color rgb="FF6B7280"/><name val="Calibri"/><family val="2"/></font>'
    . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
    . '</fonts>'
    . '<fills count="4">'
    . '<fill><patternFill patternType="none"/></fill>'
    . '<fill><patternFill patternType="gray125"/></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FF2B3A55"/><bgColor indexed="64"/></patternFill></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FFF4F6F9"/><bgColor indexed="64"/></patternFill></fill>'
    . '</fills>'
    . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $border . '</borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="10">'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
    . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
    . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
    . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">' . $text_align . '</xf>'
    . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top"/></xf>'
    . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1">' . $text_align . '</xf>'
    . '<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top"/></xf>'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top"/></xf>'
    . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top"/></xf>'
    . '</cellXfs>'
    . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
    . '</styleSheet>');

  $zip->addFile($sheet_file, 'xl/worksheets/sheet1.xml');
  $ok = $zip->close();
  unlink($sheet_file);
  if (!$ok) {
    throw new RuntimeException('не закрыть архив');
  }
}

/** Модалка выгрузки. Вызывается из bsi_leads_render_page(); открывается кнопкой .js-bsi-leads-export-open. */
function bsi_leads_export_render_modal(array $period): void
{
  global $wpdb;

  $current_form = isset($_GET['form']) ? sanitize_key(wp_unslash($_GET['form'])) : '';
  $first = $wpdb->get_var('SELECT MIN(created_at) FROM ' . bsi_leads_table_name());
  $min_date = $first ? substr((string) $first, 0, 10) : current_datetime()->format('Y-m-d');
  $presets = ['7' => '7 дней', '30' => '30 дней', '90' => '90 дней', 'month' => 'Этот месяц', 'prev-month' => 'Прошлый месяц', '365' => 'Год', 'all' => 'Всё время'];
  $groups = ['main' => 'Основное', 'contacts' => 'Контакты', 'details' => 'Подробности'];
  $columns = bsi_leads_export_columns();
  $hints = [
    'payload' => 'каждое поле — своя колонка',
    'utm' => 'source, medium, campaign',
  ];
  ?>
  <dialog class="bsi-leads-export is-hide-empty js-bsi-leads-export" data-min="<?php echo esc_attr($min_date); ?>">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bsi-leads-export__form js-bsi-leads-export-form">
      <input type="hidden" name="action" value="bsi_leads_export">
      <input type="hidden" name="download_token" value="">
      <?php wp_nonce_field('bsi_leads_export'); ?>

      <div class="bsi-leads-export__head">
        <h2 class="bsi-leads-export__title">Выгрузка заявок в Excel</h2>
        <button type="button" class="bsi-leads-export__close js-bsi-leads-export-close" title="Закрыть">
          <span class="dashicons dashicons-no-alt"></span>
        </button>
      </div>

      <div class="bsi-leads-export__body">
        <section class="bsi-leads-export__section">
          <div class="bsi-leads-export__section-head">
            <span class="bsi-leads-export__section-title">Период</span>
          </div>
          <div class="bsi-leads-export__presets">
            <?php foreach ($presets as $key => $label): ?>
              <button type="button" class="bsi-leads-export__preset js-bsi-leads-export-preset" data-preset="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
            <?php endforeach; ?>
          </div>
          <div class="bsi-leads-export__dates">
            <label class="bsi-leads-export__date">
              <span>С</span>
              <input type="date" name="from" required value="<?php echo esc_attr($period['from_input']); ?>">
            </label>
            <span class="bsi-leads-export__dash">—</span>
            <label class="bsi-leads-export__date">
              <span>По</span>
              <input type="date" name="to" required value="<?php echo esc_attr($period['to_input']); ?>">
            </label>
          </div>
        </section>

        <section class="bsi-leads-export__section">
          <div class="bsi-leads-export__section-head">
            <span class="bsi-leads-export__section-title">Формы <span class="bsi-leads-export__counter js-bsi-leads-export-counter" data-group="forms"></span></span>
            <span class="bsi-leads-export__links">
              <button type="button" class="button-link js-bsi-leads-export-select" data-group="forms" data-mode="all">Все</button>
              <button type="button" class="button-link js-bsi-leads-export-select" data-group="forms" data-mode="nonempty">С заявками</button>
              <button type="button" class="button-link js-bsi-leads-export-select" data-group="forms" data-mode="none">Снять</button>
            </span>
          </div>
          <div class="bsi-leads-export__forms">
            <?php foreach (bsi_leads_form_labels() as $key => $label): ?>
              <label class="bsi-leads-export__option js-bsi-leads-export-form-option" data-form="<?php echo esc_attr($key); ?>">
                <input type="checkbox" name="forms[]" value="<?php echo esc_attr($key); ?>"<?php checked($current_form === '' || $current_form === $key); ?>>
                <span class="bsi-leads-export__option-label"><?php echo esc_html($label); ?></span>
                <span class="bsi-leads-export__option-count js-bsi-leads-export-count"></span>
              </label>
            <?php endforeach; ?>
          </div>
          <button type="button" class="button-link bsi-leads-export__more js-bsi-leads-export-more" hidden></button>
        </section>

        <section class="bsi-leads-export__section">
          <div class="bsi-leads-export__section-head">
            <span class="bsi-leads-export__section-title">Колонки <span class="bsi-leads-export__counter js-bsi-leads-export-counter" data-group="columns"></span></span>
            <span class="bsi-leads-export__links">
              <button type="button" class="button-link js-bsi-leads-export-select" data-group="columns" data-mode="all">Все</button>
              <button type="button" class="button-link js-bsi-leads-export-select" data-group="columns" data-mode="default">По умолчанию</button>
            </span>
          </div>
          <div class="bsi-leads-export__groups">
            <?php foreach ($groups as $group => $group_label): ?>
              <div class="bsi-leads-export__group">
                <div class="bsi-leads-export__group-title"><?php echo esc_html($group_label); ?></div>
                <?php foreach ($columns as $key => $def):
                  if ($def[3] !== $group) {
                    continue;
                  } ?>
                  <label class="bsi-leads-export__option bsi-leads-export__option--column">
                    <input type="checkbox" name="columns[]" value="<?php echo esc_attr($key); ?>" data-default="<?php echo $def[2] ? '1' : '0'; ?>"<?php checked($def[2]); ?>>
                    <span class="bsi-leads-export__option-label">
                      <?php echo esc_html($def[0]); ?>
                      <?php if (isset($hints[$key])): ?><span class="bsi-leads-export__option-hint"><?php echo esc_html($hints[$key]); ?></span><?php endif; ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <div class="bsi-leads-export__foot">
        <div class="bsi-leads-export__summary js-bsi-leads-export-summary"></div>
        <div class="bsi-leads-export__actions">
          <button type="button" class="button button-large js-bsi-leads-export-close">Отмена</button>
          <button type="submit" class="button button-primary button-large bsi-leads-export__submit js-bsi-leads-export-submit">
            <span class="dashicons dashicons-download"></span>
            <span class="js-bsi-leads-export-submit-text">Скачать .xlsx</span>
          </button>
        </div>
      </div>
    </form>
  </dialog>
  <?php
}

add_action('admin_head', function () {
  if (($_GET['page'] ?? '') !== 'bsi-leads' || isset($_GET['lead'])) {
    return;
  }
  ?>
  <style>
    .bsi-leads-export{width:760px;max-width:calc(100vw - 32px);max-height:calc(100vh - 48px);padding:0;border:0;border-radius:12px;background:#fff;color:#1d2327;box-shadow:0 24px 64px rgba(0,0,0,.28);overflow:hidden}
    .bsi-leads-export[open]{display:flex}
    .bsi-leads-export::backdrop{background:rgba(17,24,39,.5)}
    .bsi-leads-export__form{display:flex;flex-direction:column;width:100%;min-height:0}
    .bsi-leads-export__head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 24px 16px;border-bottom:1px solid #f0f0f1}
    .bsi-leads-export__title{margin:0;padding:0;font-size:20px;font-weight:600;line-height:120%}
    .bsi-leads-export__close{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border:0;border-radius:8px;background:none;color:#646970;cursor:pointer}
    .bsi-leads-export__close:hover{background:#f0f0f1;color:#1d2327}
    .bsi-leads-export__body{display:flex;flex-direction:column;gap:24px;padding:20px 24px 24px;overflow-y:auto;min-height:0}
    .bsi-leads-export__section{display:flex;flex-direction:column;gap:12px}
    .bsi-leads-export__section-head{display:flex;align-items:baseline;justify-content:space-between;gap:16px}
    .bsi-leads-export__section-title{font-size:16px;font-weight:600;line-height:140%}
    .bsi-leads-export__counter{margin-left:4px;font-size:14px;font-weight:400;color:#646970}
    .bsi-leads-export__links{display:flex;gap:16px}
    .bsi-leads-export__links .button-link{font-size:14px;line-height:140%;text-decoration:none}
    .bsi-leads-export__links .button-link:hover{text-decoration:underline}
    .bsi-leads-export__presets{display:flex;flex-wrap:wrap;gap:6px}
    .bsi-leads-export__preset{min-height:36px;padding:0 12px;border:0;border-radius:8px;background:#f0f0f1;color:#1d2327;font-size:14px;line-height:140%;cursor:pointer;transition:background-color .15s,color .15s}
    .bsi-leads-export__preset:hover{background:#e4e5e7}
    .bsi-leads-export__preset.is-active{background:#2271b1;color:#fff}
    .bsi-leads-export__dates{display:flex;align-items:flex-end;gap:12px}
    .bsi-leads-export__date{display:flex;flex-direction:column;gap:4px;font-size:14px;color:#646970;line-height:140%}
    .bsi-leads-export__date input[type=date]{min-height:40px;padding:0 10px;border-radius:8px;font-size:14px}
    .bsi-leads-export__dash{padding-bottom:10px;color:#646970}
    .bsi-leads-export__forms{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px}
    .bsi-leads-export__option{display:flex;align-items:flex-start;gap:8px;padding:8px 10px;border-radius:8px;font-size:14px;line-height:140%;cursor:pointer;transition:background-color .15s}
    .bsi-leads-export__option:hover{background:#f6f7f7}
    .bsi-leads-export__option input[type=checkbox]{flex:none;margin:2px 0 0}
    .bsi-leads-export__option-label{flex:1 1 auto;min-width:0;overflow-wrap:anywhere;hyphens:auto}
    .bsi-leads-export__option-count{flex:none;min-width:20px;padding:0 6px;border-radius:10px;background:#f0f0f1;color:#50575e;font-size:14px;line-height:20px;text-align:center}
    .bsi-leads-export__option-count:empty{display:none}
    .bsi-leads-export__option.is-empty .bsi-leads-export__option-label,.bsi-leads-export__option.is-empty .bsi-leads-export__option-count{color:#a7aaad}
    .bsi-leads-export.is-hide-empty .bsi-leads-export__option.is-empty{display:none}
    .bsi-leads-export__more{align-self:flex-start;font-size:14px;line-height:140%;text-decoration:none}
    .bsi-leads-export__more:hover{text-decoration:underline}
    .bsi-leads-export__more[hidden]{display:none}
    .bsi-leads-export__option.is-checked .bsi-leads-export__option-count{background:#e5f0fa;color:#135e96}
    .bsi-leads-export__groups{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
    .bsi-leads-export__group{display:flex;flex-direction:column;gap:2px;padding:12px 8px 8px;border-radius:10px;background:#f6f7f7}
    .bsi-leads-export__group-title{padding:0 10px 4px;font-size:14px;font-weight:600;color:#646970}
    .bsi-leads-export__option--column:hover{background:#fff}
    .bsi-leads-export__option-hint{display:block;margin-top:2px;font-size:14px;color:#646970;line-height:140%}
    .bsi-leads-export__foot{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 24px;border-top:1px solid #f0f0f1;background:#fff}
    .bsi-leads-export__summary{font-size:14px;line-height:140%;color:#50575e}
    .bsi-leads-export__summary strong{font-size:16px;color:#1d2327}
    .bsi-leads-export__summary.is-warning{color:#b32d2e}
    .bsi-leads-export__summary.is-done{color:#008a20}
    .bsi-leads-export__actions{display:flex;flex:none;gap:8px}
    .bsi-leads-export__submit{display:inline-flex !important;align-items:center;gap:6px}
    .bsi-leads-export__submit .dashicons{width:18px;height:18px;font-size:18px}
    .bsi-leads-export__submit.is-loading .dashicons{animation:bsi-leads-spin .8s linear infinite}
    .bsi-leads-export__submit.is-loading .dashicons:before{content:"\f463"}
    @media (max-width:782px){
      .bsi-leads-export__forms{grid-template-columns:repeat(2,minmax(0,1fr))}
      .bsi-leads-export__groups{grid-template-columns:1fr}
      .bsi-leads-export__foot{flex-direction:column;align-items:stretch}
      .bsi-leads-export__actions{justify-content:flex-end}
    }
    @media (max-width:480px){
      .bsi-leads-export__forms{grid-template-columns:1fr}
      .bsi-leads-export__dates{flex-wrap:wrap}
      .bsi-leads-export__date{flex:1 1 0}
      .bsi-leads-export__dash{display:none}
    }
  </style>
  <?php
});

add_action('admin_footer', function () {
  if (($_GET['page'] ?? '') !== 'bsi-leads' || isset($_GET['lead'])) {
    return;
  }
  ?>
  <script>
    (function () {
      var dialog = document.querySelector('.js-bsi-leads-export');
      if (!dialog || !dialog.showModal) return;
      var form = dialog.querySelector('.js-bsi-leads-export-form');
      var from = form.elements.from;
      var to = form.elements.to;
      var summary = dialog.querySelector('.js-bsi-leads-export-summary');
      var submit = dialog.querySelector('.js-bsi-leads-export-submit');
      var submitText = dialog.querySelector('.js-bsi-leads-export-submit-text');
      var presets = dialog.querySelectorAll('.js-bsi-leads-export-preset');
      var more = dialog.querySelector('.js-bsi-leads-export-more');
      var storageKey = 'bsi_leads_export_columns';
      var counts = null;
      var loading = false;
      var countTimer = null;
      var countRequest = 0;
      var pendingNonempty = false;

      function selectNonempty() {
        boxes('forms').forEach(function (b) { b.checked = !!counts[b.value]; });
      }

      function boxes(group) {
        return Array.prototype.slice.call(form.querySelectorAll('input[name="' + group + '[]"]'));
      }
      function ymd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
      }
      function plural(n) {
        var m10 = n % 10, m100 = n % 100;
        if (m10 === 1 && m100 !== 11) return 'заявка';
        if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) return 'заявки';
        return 'заявок';
      }
      function presetRange(key) {
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        if (key === 'all') return [dialog.dataset.min, ymd(today)];
        if (key === 'month') return [ymd(new Date(today.getFullYear(), today.getMonth(), 1)), ymd(today)];
        if (key === 'prev-month') return [ymd(new Date(today.getFullYear(), today.getMonth() - 1, 1)), ymd(new Date(today.getFullYear(), today.getMonth(), 0))];
        return [ymd(new Date(today.getFullYear(), today.getMonth(), today.getDate() - Number(key) + 1)), ymd(today)];
      }
      // Подсветить пресет, если даты совпадают с ним (в т.ч. после ручного ввода).
      function syncPresets() {
        presets.forEach(function (b) {
          var r = presetRange(b.dataset.preset);
          b.classList.toggle('is-active', r[0] === from.value && r[1] === to.value);
        });
      }

      function render() {
        ['forms', 'columns'].forEach(function (group) {
          var list = boxes(group);
          var on = list.filter(function (b) { return b.checked; }).length;
          dialog.querySelector('.js-bsi-leads-export-counter[data-group="' + group + '"]').textContent = on + ' из ' + list.length;
        });

        var total = 0;
        var empty = 0;
        dialog.querySelectorAll('.js-bsi-leads-export-form-option').forEach(function (option) {
          var box = option.querySelector('input');
          var n = counts ? (counts[option.dataset.form] || 0) : null;
          option.querySelector('.js-bsi-leads-export-count').textContent = n === null ? '' : n;
          option.classList.toggle('is-empty', n === 0);
          option.classList.toggle('is-checked', box.checked);
          if (box.checked && n) total += n;
          if (n === 0) empty++;
        });
        // Формы без заявок за период свёрнуты, чтобы не мешали.
        more.hidden = !empty;
        more.textContent = dialog.classList.contains('is-hide-empty') ? 'Показать формы без заявок (' + empty + ')' : 'Скрыть формы без заявок';

        if (loading) return;
        var formsOn = boxes('forms').some(function (b) { return b.checked; });
        var columnsOn = boxes('columns').some(function (b) { return b.checked; });
        var message = '';
        var warning = true;
        if (!from.value || !to.value) message = 'Укажите даты периода';
        else if (!formsOn) message = 'Выберите хотя бы одну форму';
        else if (!columnsOn) message = 'Выберите хотя бы одну колонку';
        else if (counts === null) { message = 'Считаем заявки…'; warning = false; }
        else if (!total) message = 'За этот период по выбранным формам заявок нет';
        else { message = 'Будет выгружено: <strong>' + total + '</strong> ' + plural(total); warning = false; }

        summary.innerHTML = message;
        summary.classList.toggle('is-warning', warning);
        summary.classList.remove('is-done');
        submit.disabled = warning;
      }

      function loadCounts() {
        clearTimeout(countTimer);
        counts = null;
        render();
        if (!from.value || !to.value) return;
        countTimer = setTimeout(function () {
          var id = ++countRequest;
          var data = new FormData();
          data.append('action', 'bsi_leads_export_count');
          data.append('nonce', form.elements._wpnonce.value);
          data.append('from', from.value);
          data.append('to', to.value);
          fetch(window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: data })
            .then(function (res) { return res.json(); })
            .then(function (json) {
              if (id !== countRequest) return;
              counts = json && json.success ? json.data.forms : {};
              // «С заявками» нажали, пока счётчики грузились, — применяем сейчас.
              if (pendingNonempty) selectNonempty();
              pendingNonempty = false;
              render();
            })
            .catch(function () {
              if (id !== countRequest) return;
              counts = {};
              render();
            });
        }, 200);
      }

      function saveColumns() {
        try {
          localStorage.setItem(storageKey, JSON.stringify(boxes('columns').filter(function (b) { return b.checked; }).map(function (b) { return b.value; })));
        } catch (e) {}
      }
      function restoreColumns() {
        try {
          var saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
          if (Array.isArray(saved) && saved.length) {
            boxes('columns').forEach(function (b) { b.checked = saved.indexOf(b.value) !== -1; });
          }
        } catch (e) {}
      }

      function setLoading(state) {
        loading = state;
        submit.classList.toggle('is-loading', state);
        submit.disabled = state;
        submitText.textContent = state ? 'Готовим файл…' : 'Скачать .xlsx';
        if (state) {
          summary.innerHTML = 'Собираем таблицу, скачивание начнётся само';
          summary.classList.remove('is-warning', 'is-done');
        }
      }

      restoreColumns();

      document.addEventListener('click', function (e) {
        if (!e.target.closest('.js-bsi-leads-export-open')) return;
        e.preventDefault();
        // Период по умолчанию — тот, что сейчас открыт на странице (меняется без перезагрузки).
        var custom = document.querySelector('.bsi-leads__custom');
        if (custom) {
          from.value = custom.elements.from.value;
          to.value = custom.elements.to.value;
        }
        syncPresets();
        dialog.showModal();
        loadCounts();
      });

      dialog.addEventListener('click', function (e) {
        if (e.target === dialog || e.target.closest('.js-bsi-leads-export-close')) {
          dialog.close();
          return;
        }
        var preset = e.target.closest('.js-bsi-leads-export-preset');
        if (preset) {
          var r = presetRange(preset.dataset.preset);
          from.value = r[0];
          to.value = r[1];
          syncPresets();
          loadCounts();
          return;
        }
        if (e.target.closest('.js-bsi-leads-export-more')) {
          dialog.classList.toggle('is-hide-empty');
          render();
          return;
        }
        var select = e.target.closest('.js-bsi-leads-export-select');
        if (select) {
          var mode = select.dataset.mode;
          if (mode === 'nonempty') {
            if (counts) selectNonempty();
            else pendingNonempty = true;
            render();
            return;
          }
          pendingNonempty = false;
          boxes(select.dataset.group).forEach(function (b) {
            if (mode === 'all') b.checked = true;
            else if (mode === 'none') b.checked = false;
            else if (mode === 'default') b.checked = b.dataset.default === '1';
          });
          if (select.dataset.group === 'columns') saveColumns();
          render();
        }
      });

      form.addEventListener('change', function (e) {
        if (e.target === from || e.target === to) {
          syncPresets();
          loadCounts();
          return;
        }
        if (e.target.name === 'columns[]') saveColumns();
        render();
      });

      form.addEventListener('submit', function (e) {
        if (submit.disabled) {
          e.preventDefault();
          return;
        }
        var token = String(Date.now());
        form.elements.download_token.value = token;
        setLoading(true);
        var started = Date.now();
        var timer = setInterval(function () {
          var done = document.cookie.split('; ').some(function (row) { return row === 'bsi_leads_export_done=' + token; });
          if (!done && Date.now() - started < 120000) return;
          clearInterval(timer);
          document.cookie = 'bsi_leads_export_done=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
          setLoading(false);
          render();
          if (done) {
            summary.textContent = 'Файл скачан';
            summary.classList.add('is-done');
            setTimeout(function () { if (dialog.open) dialog.close(); }, 1200);
          } else {
            summary.textContent = 'Скачивание не началось. Попробуйте ещё раз';
            summary.classList.add('is-warning');
          }
        }, 500);
      });
    })();
  </script>
  <?php
});
