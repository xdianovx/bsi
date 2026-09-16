<?php

/**
 * Настройки сайта → «Детализация счёта» — выгрузка в Excel детализации
 * по номерам путёвок (счетов).
 *
 * Перенос функционала со старого сайта на Битриксе:
 * old.bsigroup.ru → bitrix/modules/tour/admin/check.php. Там форма ходила
 * напрямую в MS SQL МастерТура (Мегатек) через TMasterDB::connectDB()
 * и собирала таблицу по tbl_dogovor / tbl_dogovorlist / tbl_turist.
 *
 * У нового сайта такого доступа нет — он работает с SAMO по HTTP
 * (inc/samo/). Поэтому источник данных здесь вынесен за фильтр
 * `bsi_invoice_detail_rows`: форма, разбор номеров, нормализация и выгрузка
 * работают всегда, а способ достать строки подключается отдельно.
 *
 * Что выяснено про SAMO на 15.09.2026 (проверено запросами к
 * online.bsigroup.ru, дока — dokuwiki.samo.ru/doku.php?id=onlinest:api):
 *  - по заявкам в API есть только Claim_DocumentList / Claim_DocumentGet /
 *    Claim_DocumentDownload, то есть готовые документы, а не строки услуг;
 *  - все три требуют параметр `claim` и авторизацию агента (Auth_Login):
 *    с одним oauth_token отвечают 403 «Доступ запрещён»;
 *  - метода, отдающего детализацию услуг таблицей, в доке нет.
 *
 * Поэтому источник пока не включён. Когда способ найдётся, достаточно
 * объявить имя метода в wp-config.php:
 *
 *   define('BSI_SAMO_CLAIM_ACTION', 'Claim_XXX');
 *
 * либо повесить свой обработчик на фильтр `bsi_invoice_detail_rows`.
 */

if (!defined('ABSPATH')) {
  exit;
}

const BSI_INVOICE_DETAIL_CAP = 'manage_options';
const BSI_INVOICE_DETAIL_SLUG = 'bsi-invoice-detail';

/** Больше за один раз не выгружаем: запрос к источнику и так не быстрый. */
const BSI_INVOICE_DETAIL_MAX_IDS = 500;

/**
 * Колонки выгрузки — порядок и подписи повторяют старый Битрикс,
 * чтобы бухгалтерия открывала привычный файл.
 *
 * Ключ — внутреннее имя поля строки, значение — заголовок в файле.
 */
function bsi_invoice_detail_columns(): array
{
  return [
    'order_date' => 'Дата создания заказа',
    'invoice' => 'Номер счета',
    'passengers' => 'Пассажир',
    'date_begin' => 'Дата начала услуги',
    'date_end' => 'Дата окончания услуги',
    'service' => 'Наименование услуги',
    'price' => 'Стоимость, руб',
    'currency' => 'Валюта путевки',
  ];
}

add_action('admin_menu', function () {
  add_submenu_page(
    'bsi-hub-settings',
    'Детализация счёта',
    'Детализация счёта',
    BSI_INVOICE_DETAIL_CAP,
    BSI_INVOICE_DETAIL_SLUG,
    'bsi_invoice_detail_page'
  );
}, 998.7);

/**
 * Разбор поля «Номера путёвок»: как в оригинале, режем по любым
 * не-словесным символам, чтобы прожевать и запятые, и переносы строк.
 *
 * @return string[] номера без пустышек и повторов
 */
function bsi_invoice_detail_parse_ids(string $raw): array
{
  $ids = preg_split('/[^\w-]+/u', trim($raw)) ?: [];
  $ids = array_filter(array_map('trim', $ids), static fn($id) => $id !== '');

  return array_slice(array_values(array_unique($ids)), 0, BSI_INVOICE_DETAIL_MAX_IDS);
}

/**
 * Строки детализации по номерам счетов.
 *
 * Возвращает null, если источник данных не подключён — это не ошибка
 * выгрузки, а «неоткуда взять», и на экране показывается отдельно.
 *
 * @param string[] $ids
 * @return array<int, array<string, mixed>>|null
 */
function bsi_invoice_detail_rows(array $ids): ?array
{
  /**
   * Подключение источника детализации.
   *
   * @param array<int, array<string, mixed>>|null $rows null — источника нет
   * @param string[] $ids номера счетов
   */
  $rows = apply_filters('bsi_invoice_detail_rows', null, $ids);

  if (!is_array($rows)) {
    return null;
  }

  return array_map('bsi_invoice_detail_normalize_row', $rows);
}

/**
 * Источник SAMO. Работает, только если в конфиге задан
 * BSI_SAMO_CLAIM_ACTION — имя метода API по заявкам.
 */
add_filter('bsi_invoice_detail_rows', function ($rows, array $ids) {
  if (is_array($rows) || !defined('BSI_SAMO_CLAIM_ACTION') || !BSI_SAMO_CLAIM_ACTION) {
    return $rows;
  }

  if (!class_exists('SamoService')) {
    return $rows;
  }

  try {
    $response = SamoService::client()->request(BSI_SAMO_CLAIM_ACTION, [
      'claims' => implode(',', $ids),
    ]);
  } catch (Throwable $e) {
    return $rows;
  }

  $payload = $response[BSI_SAMO_CLAIM_ACTION] ?? null;

  return is_array($payload) ? $payload : $rows;
}, 10, 2);

/**
 * Приведение строки источника к колонкам выгрузки.
 *
 * Здесь же повторены правила старого SQL-запроса, которые иначе теряются
 * при переносе:
 *  - дата окончания считается из даты начала и числа дней, а не берётся
 *    из готового поля: для типа услуги 3 это +ndays, для остальных +ndays-1;
 *  - туристы склеиваются в одну ячейку через запятую;
 *  - стоимость = брутто × курс, два знака, разделитель — запятая;
 *  - валюта «рб» выводится пустой строкой;
 *  - из названия услуги вырезается «,1 день/».
 *
 * @param array<string, mixed> $row
 * @return array<string, string>
 */
function bsi_invoice_detail_normalize_row(array $row): array
{
  $passengers = $row['passengers'] ?? '';
  if (is_array($passengers)) {
    $passengers = implode(', ', array_filter(array_map('strval', $passengers)));
  }

  $date_end = $row['date_end'] ?? '';
  if ($date_end === '' && isset($row['date_begin'])) {
    $date_end = bsi_invoice_detail_service_end(
      (string) $row['date_begin'],
      (int) ($row['nights'] ?? $row['ndays'] ?? 0),
      (int) ($row['service_type'] ?? $row['svkey'] ?? 0)
    );
  }

  $price = $row['price'] ?? null;
  if ($price === null && isset($row['brutto'])) {
    $price = (float) $row['brutto'] * (float) ($row['currency_rate'] ?? 1);
  }

  $currency = trim((string) ($row['currency'] ?? ''));
  if ($currency === 'рб') {
    $currency = '';
  }

  $service = trim(str_replace(',1 день/', ' ', (string) ($row['service'] ?? '')));

  return [
    'order_date' => (string) ($row['order_date'] ?? ''),
    'invoice' => (string) ($row['invoice'] ?? ''),
    'passengers' => trim((string) $passengers, ", \t\n\r\0\x0B"),
    'date_begin' => (string) ($row['date_begin'] ?? ''),
    'date_end' => (string) $date_end,
    'service' => $service,
    'price' => $price === null ? '' : str_replace('.', ',', number_format((float) $price, 2, '.', '')),
    'currency' => $currency,
  ];
}

/**
 * Дата окончания услуги по правилам МастерТура.
 *
 * @param string $begin дата начала в формате дд.мм.гггг
 * @param int $days число дней услуги
 * @param int $type тип услуги (3 — считается по-другому)
 */
function bsi_invoice_detail_service_end(string $begin, int $days, int $type): string
{
  $date = date_create_immutable_from_format('d.m.Y', $begin) ?: date_create_immutable($begin);

  if (!$date) {
    return '';
  }

  $shift = $type === 3
    ? max($days, 1)
    : max($days - 1, 0);

  return $date->modify('+' . $shift . ' day')->format('d.m.Y');
}

/**
 * Обработчик кнопки «Сохранить детализацию в эксель».
 *
 * Отдельный admin_post, а не обработка внутри страницы: файл уходит
 * заголовками, до этого в буфер ничего выводиться не должно.
 */
add_action('admin_post_bsi_invoice_detail_export', function () {
  if (!current_user_can(BSI_INVOICE_DETAIL_CAP)) {
    wp_die('Недостаточно прав.');
  }

  check_admin_referer('bsi_invoice_detail');

  $ids = bsi_invoice_detail_parse_ids((string) ($_POST['ids'] ?? ''));

  if (!$ids) {
    bsi_invoice_detail_redirect_back('empty', '');
  }

  $rows = bsi_invoice_detail_rows($ids);

  if ($rows === null) {
    bsi_invoice_detail_redirect_back('nosource', implode(' ', $ids));
  }

  if (!$rows) {
    bsi_invoice_detail_redirect_back('norows', implode(' ', $ids));
  }

  bsi_invoice_detail_send_file($rows, $ids);
});

/**
 * Возврат на страницу с сообщением вместо файла.
 */
function bsi_invoice_detail_redirect_back(string $notice, string $ids): void
{
  wp_safe_redirect(add_query_arg([
    'page' => BSI_INVOICE_DETAIL_SLUG,
    'bsi_notice' => $notice,
    'ids' => rawurlencode($ids),
  ], admin_url('admin.php')));
  exit;
}

/**
 * Отдача файла. CSV с BOM и точкой с запятой — русский Excel открывает
 * такой файл сразу, без мастера импорта.
 *
 * @param array<int, array<string, string>> $rows
 * @param string[] $ids
 */
function bsi_invoice_detail_send_file(array $rows, array $ids): void
{
  $columns = bsi_invoice_detail_columns();
  $name = count($ids) > 1
    ? reset($ids) . '--' . end($ids)
    : reset($ids);

  nocache_headers();
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . sanitize_file_name($name) . '.csv"');

  $out = fopen('php://output', 'w');
  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, array_values($columns), ';');

  foreach ($rows as $row) {
    $line = [];
    foreach (array_keys($columns) as $key) {
      $line[] = $row[$key] ?? '';
    }
    fputcsv($out, $line, ';');
  }

  fclose($out);
  exit;
}

function bsi_invoice_detail_page(): void
{
  if (!current_user_can(BSI_INVOICE_DETAIL_CAP)) {
    wp_die('Недостаточно прав.');
  }

  $ids = isset($_GET['ids']) ? rawurldecode((string) $_GET['ids']) : '';
  $notice = isset($_GET['bsi_notice']) ? sanitize_key((string) $_GET['bsi_notice']) : '';

  $notices = [
    'empty' => ['warning', 'Не указан ни один номер путёвки.'],
    'norows' => ['warning', 'По этим номерам детализации не нашлось. Проверьте номера счетов.'],
    'nosource' => [
      'error',
      'Источник данных не подключён. Детализация лежит в SAMO; чтобы её забирать, '
        . 'нужен метод API, отдающий услуги по номеру счёта, и доступ агента: '
        . 'в открытом API есть только документы по заявке, и те под авторизацией. '
        . 'После получения метода — константа <code>BSI_SAMO_CLAIM_ACTION</code> '
        . 'в <code>wp-config.php</code>.',
    ],
  ];
?>
  <div class="wrap">
    <h1>Детализация счёта</h1>

    <?php if (isset($notices[$notice])) : ?>
      <div class="notice notice-<?= esc_attr($notices[$notice][0]) ?>">
        <p><?= wp_kses_post($notices[$notice][1]) ?></p>
      </div>
    <?php endif; ?>

    <p class="description">
      Выгрузка услуг по номерам счетов в файл для Excel. Номера можно вводить
      через пробел, запятую или с новой строки, не более
      <?= (int) BSI_INVOICE_DETAIL_MAX_IDS ?> за раз.
    </p>

    <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
      <input type="hidden" name="action" value="bsi_invoice_detail_export">
      <?php wp_nonce_field('bsi_invoice_detail'); ?>

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row">
            <label for="bsi-invoice-ids">Номера путёвок для детализации</label>
          </th>
          <td>
            <textarea id="bsi-invoice-ids" name="ids" rows="6" class="large-text code" required><?= esc_textarea($ids) ?></textarea>
          </td>
        </tr>
      </table>

      <?php submit_button('Сохранить детализацию в эксель'); ?>
    </form>
  </div>
<?php
}
