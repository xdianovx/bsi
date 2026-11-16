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

/** Колонки выгрузки: ключ => [подпись, ширина в символах, включена по умолчанию]. */
function bsi_leads_export_columns(): array
{
  return [
    'id' => ['№', 7, false],
    'created_at' => ['Дата', 17, true],
    'form' => ['Форма', 20, true],
    'title' => ['Что / страница', 40, true],
    'page_url' => ['Ссылка на страницу', 45, false],
    'name' => ['Имя', 24, true],
    'phone' => ['Телефон', 18, true],
    'email' => ['Email', 28, true],
    'payload' => ['Детали заявки', 50, true],
    'utm' => ['UTM (source / medium / campaign)', 30, false],
    'mail_sent' => ['Письмо ушло', 13, false],
  ];
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
    bsi_leads_export_build_xlsx($path, $columns, $rows, $meta);
  } catch (\Throwable $e) {
    @unlink($path);
    wp_die('Не удалось сформировать файл: ' . esc_html($e->getMessage()));
  }

  $filename = sprintf('zayavki_%s_%s.xlsx', $from->format('Y-m-d'), $to->format('Y-m-d'));
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
    case 'payload':
      if ($anonymized && empty($lead['payload'])) {
        return 'обезличено';
      }
      $payload = $lead['payload'] ? (array) json_decode((string) $lead['payload'], true) : [];
      $lines = [];
      foreach ($payload as $key => $value) {
        $value = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
        $lines[] = $key . ': ' . $value;
      }
      return implode("\n", $lines);
    case 'utm':
      return implode(' / ', array_filter([$lead['utm_source'], $lead['utm_medium'], $lead['utm_campaign']]));
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
  $defs = bsi_leads_export_columns();
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
    fwrite($fh, '<col min="' . $n . '" max="' . $n . '" width="' . $defs[$col][1] . '" customWidth="1"/>');
  }
  fwrite($fh, '</cols><sheetData>');

  $text_cell = static fn(string $ref, string $value, int $style): string => '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . bsi_leads_export_xml($value) . '</t></is></c>';

  fwrite($fh, '<row r="1" ht="26" customHeight="1">' . $text_cell('A1', $meta['title'], 1) . '</row>');
  fwrite($fh, '<row r="2" ht="20" customHeight="1">' . $text_cell('A2', $meta['subtitle'], 2) . '</row>');

  fwrite($fh, '<row r="' . $header_row . '" ht="30" customHeight="1">');
  foreach ($columns as $i => $col) {
    fwrite($fh, $text_cell(bsi_leads_export_col($i) . $header_row, $defs[$col][0], 3));
  }
  fwrite($fh, '</row>');

  foreach ($rows as $r => $lead) {
    $row_num = $header_row + 1 + $r;
    $zebra = $r % 2 === 1;
    $lines_max = 1;
    $cells = '';
    foreach ($columns as $i => $col) {
      $ref = bsi_leads_export_col($i) . $row_num;
      $value = bsi_leads_export_value($lead, $col);
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
        $lines += max(1, (int) ceil(mb_strlen($line) / max(1, $defs[$col][1] - 2)));
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

/** Кнопка рядом с заголовком и модалка. Вызывается из bsi_leads_render_page(). */
function bsi_leads_export_render_modal(array $period): void
{
  $current_form = isset($_GET['form']) ? sanitize_key(wp_unslash($_GET['form'])) : '';
  ?>
  <dialog class="bsi-leads-export js-bsi-leads-export">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bsi-leads-export__form js-bsi-leads-export-form">
      <input type="hidden" name="action" value="bsi_leads_export">
      <?php wp_nonce_field('bsi_leads_export'); ?>

      <div class="bsi-leads-export__head">
        <h2 class="bsi-leads-export__title">Выгрузить в Excel</h2>
        <button type="button" class="bsi-leads-export__close js-bsi-leads-export-close">×</button>
      </div>

      <div class="bsi-leads-export__section">
        <div class="bsi-leads-export__label">Период</div>
        <div class="bsi-leads-export__presets">
          <?php foreach (['7' => '7 дней', '30' => '30 дней', '90' => '90 дней', '365' => 'Год', 'month' => 'Этот месяц', 'prev-month' => 'Прошлый месяц'] as $key => $label): ?>
            <button type="button" class="bsi-leads-export__preset js-bsi-leads-export-preset" data-preset="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
          <?php endforeach; ?>
        </div>
        <div class="bsi-leads-export__dates">
          <label>с <input type="date" name="from" required value="<?php echo esc_attr($period['from_input']); ?>"></label>
          <label>по <input type="date" name="to" required value="<?php echo esc_attr($period['to_input']); ?>"></label>
        </div>
      </div>

      <div class="bsi-leads-export__section">
        <div class="bsi-leads-export__label">
          Формы
          <button type="button" class="button-link js-bsi-leads-export-toggle" data-group="forms">выбрать все / снять</button>
        </div>
        <div class="bsi-leads-export__checks">
          <?php foreach (bsi_leads_form_labels() as $key => $label): ?>
            <label class="bsi-leads-export__check">
              <input type="checkbox" name="forms[]" value="<?php echo esc_attr($key); ?>"<?php checked($current_form === '' || $current_form === $key); ?>>
              <?php echo esc_html($label); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bsi-leads-export__section">
        <div class="bsi-leads-export__label">
          Колонки
          <button type="button" class="button-link js-bsi-leads-export-toggle" data-group="columns">выбрать все / снять</button>
        </div>
        <div class="bsi-leads-export__checks">
          <?php foreach (bsi_leads_export_columns() as $key => $def): ?>
            <label class="bsi-leads-export__check">
              <input type="checkbox" name="columns[]" value="<?php echo esc_attr($key); ?>"<?php checked($def[2]); ?>>
              <?php echo esc_html($def[0]); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <p class="bsi-leads-export__error js-bsi-leads-export-error" hidden></p>

      <div class="bsi-leads-export__actions">
        <button type="button" class="button js-bsi-leads-export-close">Отмена</button>
        <button type="submit" class="button button-primary">Скачать .xlsx</button>
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
    .bsi-leads-export{width:640px;max-width:calc(100vw - 32px);padding:0;border:0;border-radius:8px;box-shadow:0 12px 40px rgba(0,0,0,.25)}
    .bsi-leads-export::backdrop{background:rgba(17,24,39,.45)}
    .bsi-leads-export__form{display:flex;flex-direction:column;gap:20px;padding:24px}
    .bsi-leads-export__head{display:flex;align-items:center;justify-content:space-between;gap:16px}
    .bsi-leads-export__title{margin:0;font-size:20px;line-height:120%}
    .bsi-leads-export__close{border:0;background:none;font-size:24px;line-height:100%;color:#646970;cursor:pointer;padding:4px}
    .bsi-leads-export__close:hover{color:#1d2327}
    .bsi-leads-export__section{display:flex;flex-direction:column;gap:8px}
    .bsi-leads-export__label{display:flex;align-items:center;justify-content:space-between;font-size:14px;font-weight:600;color:#1d2327;line-height:140%}
    .bsi-leads-export__label .button-link{font-size:14px;font-weight:400}
    .bsi-leads-export__presets{display:flex;flex-wrap:wrap;gap:8px}
    .bsi-leads-export__preset{padding:4px 12px;border:0;border-radius:6px;background:#f0f0f1;color:#1d2327;font-size:14px;line-height:140%;cursor:pointer}
    .bsi-leads-export__preset:hover{color:#2271b1}
    .bsi-leads-export__preset.is-active{background:#2271b1;color:#fff}
    .bsi-leads-export__dates{display:flex;flex-wrap:wrap;gap:16px}
    .bsi-leads-export__dates label{display:flex;align-items:center;gap:8px;font-size:14px}
    .bsi-leads-export__checks{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px 16px;padding:12px;background:#f6f7f7;border-radius:6px}
    .bsi-leads-export__check{display:flex;align-items:flex-start;gap:6px;font-size:14px;line-height:140%}
    .bsi-leads-export__check input{margin-top:2px}
    .bsi-leads-export__error{margin:0;color:#b32d2e;font-size:14px}
    .bsi-leads-export__actions{display:flex;justify-content:flex-end;gap:8px}
    @media (max-width:600px){.bsi-leads-export__checks{grid-template-columns:repeat(2,minmax(0,1fr))}}
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
      var error = dialog.querySelector('.js-bsi-leads-export-error');
      var presets = dialog.querySelectorAll('.js-bsi-leads-export-preset');

      function ymd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
      }
      function clearPresets() {
        presets.forEach(function (b) { b.classList.remove('is-active'); });
      }

      document.addEventListener('click', function (e) {
        if (e.target.closest('.js-bsi-leads-export-open')) {
          e.preventDefault();
          // Период по умолчанию — тот, что сейчас открыт на странице (он меняется без перезагрузки).
          var custom = document.querySelector('.bsi-leads__custom');
          if (custom) {
            from.value = custom.elements.from.value;
            to.value = custom.elements.to.value;
          }
          clearPresets();
          error.hidden = true;
          dialog.showModal();
        }
      });

      dialog.addEventListener('click', function (e) {
        if (e.target === dialog || e.target.closest('.js-bsi-leads-export-close')) {
          dialog.close();
          return;
        }
        var preset = e.target.closest('.js-bsi-leads-export-preset');
        if (preset) {
          var now = new Date();
          var start, end = now;
          var key = preset.dataset.preset;
          if (key === 'month') {
            start = new Date(now.getFullYear(), now.getMonth(), 1);
          } else if (key === 'prev-month') {
            start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            end = new Date(now.getFullYear(), now.getMonth(), 0);
          } else {
            start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - Number(key) + 1);
          }
          from.value = ymd(start);
          to.value = ymd(end);
          clearPresets();
          preset.classList.add('is-active');
          return;
        }
        var toggle = e.target.closest('.js-bsi-leads-export-toggle');
        if (toggle) {
          var boxes = form.querySelectorAll('input[name="' + toggle.dataset.group + '[]"]');
          var allOn = Array.prototype.every.call(boxes, function (b) { return b.checked; });
          boxes.forEach(function (b) { b.checked = !allOn; });
        }
      });

      [from, to].forEach(function (input) { input.addEventListener('input', clearPresets); });

      form.addEventListener('submit', function (e) {
        var msg = '';
        if (!form.querySelector('input[name="forms[]"]:checked')) msg = 'Выберите хотя бы одну форму.';
        else if (!form.querySelector('input[name="columns[]"]:checked')) msg = 'Выберите хотя бы одну колонку.';
        if (msg) {
          e.preventDefault();
          error.textContent = msg;
          error.hidden = false;
          return;
        }
        error.hidden = true;
        setTimeout(function () { dialog.close(); }, 300);
      });
    })();
  </script>
  <?php
});
