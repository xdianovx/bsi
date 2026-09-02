<?php

declare(strict_types=1);

/**
 * AJAX: проверка статуса заявки по номеру (page-check-claim.php).
 *
 * Само отдаёт готовый HTML по публичному URL без авторизации:
 *   https://online.bsigroup.ru/check_confirm?samo_action=check_popup&CLAIM=N
 * API-токен и БД не нужны. Забираем страницу, вытаскиваем таблицу `.check_c`
 * и возвращаем строки «подпись → значение» — фронт рисует свою разметку.
 * Если заявки нет, Само отдаёт страницу логина без `.check_c`.
 */

const BSI_CLAIM_CHECK_URL = 'https://online.bsigroup.ru/check_confirm';

/** Лимит запросов с одного IP в минуту — страница публичная, а мы ходим к Само от своего имени. */
const BSI_CLAIM_CHECK_RATE_LIMIT = 30;

add_action('wp_ajax_check_claim', 'bsi_handle_check_claim');
add_action('wp_ajax_nopriv_check_claim', 'bsi_handle_check_claim');

function bsi_handle_check_claim(): void
{
  if (!bsi_claim_check_rate_limit_ok()) {
    wp_send_json_error(['message' => 'Слишком много запросов. Подождите минуту.'], 429);
  }

  $raw = trim((string) ($_POST['claim'] ?? ''));
  if ($raw === '' || !preg_match('/^\d{1,10}$/', $raw)) {
    wp_send_json_error([
      'message' => 'Введите номер заявки',
      'errors' => ['claim' => 'Номер заявки — только цифры'],
    ]);
  }

  $claim = (int) $raw;
  if ($claim <= 0) {
    wp_send_json_error(['errors' => ['claim' => 'Номер заявки — только цифры']]);
  }

  $result = bsi_claim_check_fetch($claim);

  if (!$result['ok']) {
    wp_send_json_error(['message' => $result['error']]);
  }

  if ($result['data'] === null) {
    wp_send_json_error([
      'message' => 'Заявка № ' . $claim . ' не найдена',
      'not_found' => true,
    ]);
  }

  wp_send_json_success($result['data']);
}

/**
 * @return array{ok: bool, data?: array|null, error?: string}
 */
function bsi_claim_check_fetch(int $claim): array
{
  $url = add_query_arg([
    'samo_action' => 'check_popup',
    'CLAIM' => $claim,
  ], BSI_CLAIM_CHECK_URL);

  $res = wp_remote_get($url, ['timeout' => 15]);

  if (is_wp_error($res)) {
    return ['ok' => false, 'error' => 'Сервис проверки временно недоступен'];
  }

  $code = wp_remote_retrieve_response_code($res);
  $body = wp_remote_retrieve_body($res);

  if ($code >= 400 || $body === '') {
    return ['ok' => false, 'error' => 'Сервис проверки временно недоступен (HTTP ' . $code . ')'];
  }

  // Без кеша: менеджеру нужен актуальный статус на момент запроса.
  return ['ok' => true, 'data' => bsi_claim_check_parse($body, $claim)];
}

/**
 * Разбираем HTML Само. null — блока `.check_c` нет (заявка не найдена).
 *
 * @return array{claim: int, rows: list<array{label: string, value: string, tone: string}>, note: string, details_url: string}|null
 */
function bsi_claim_check_parse(string $html, int $claim): ?array
{
  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
  libxml_clear_errors();

  $xpath = new DOMXPath($dom);
  $root = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " check_c ")]')->item(0);

  if (!$root instanceof DOMElement) {
    return null;
  }

  $rows = [];
  foreach ($xpath->query('.//tbody/tr', $root) as $tr) {
    $cells = $xpath->query('./td', $tr);
    if ($cells->length < 2) {
      continue;
    }

    $label = rtrim(bsi_claim_check_text($cells->item(0)), ': ');
    $value_cell = $cells->item(1);
    $value = bsi_claim_check_text($value_cell);

    if ($label === '' || $value === '') {
      continue;
    }

    $tone = '';
    $span = $xpath->query('.//span[@class]', $value_cell)->item(0);
    if ($span instanceof DOMElement) {
      $classes = preg_split('/\s+/', $span->getAttribute('class')) ?: [];
      foreach (['green' => 'success', 'red' => 'error', 'orange' => 'warning', 'yellow' => 'warning'] as $cls => $t) {
        if (in_array($cls, $classes, true)) {
          $tone = $t;
          break;
        }
      }
    }

    $rows[] = [
      'label' => mb_strtoupper(mb_substr($label, 0, 1)) . mb_substr($label, 1),
      'value' => $value,
      'tone' => $tone,
    ];
  }

  $note = '';
  $p = $xpath->query('./p', $root)->item(0);
  if ($p instanceof DOMNode) {
    $note = bsi_claim_check_text($p);
  }

  $details_url = '';
  $a = $xpath->query('./a[@href]', $root)->item(0);
  if ($a instanceof DOMElement) {
    $href = $a->getAttribute('href');
    if (strpos($href, 'https://online.bsigroup.ru/') === 0) {
      $details_url = esc_url_raw($href);
    }
  }

  return [
    'claim' => $claim,
    'rows' => $rows,
    'note' => $note,
    'details_url' => $details_url,
  ];
}

/** Текст узла: <br> → перевод строки, пробелы схлопнуты. */
function bsi_claim_check_text(DOMNode $node): string
{
  $text = '';
  foreach ($node->childNodes as $child) {
    if ($child instanceof DOMElement && strtolower($child->tagName) === 'br') {
      $text .= "\n";
    } elseif ($child->hasChildNodes()) {
      $text .= bsi_claim_check_text($child);
    } else {
      $text .= $child->textContent;
    }
  }

  $text = str_replace("\u{A0}", ' ', $text);
  $lines = array_map(
    static fn(string $l): string => trim(preg_replace('/[ \t]+/', ' ', $l) ?? ''),
    explode("\n", $text)
  );

  return implode("\n", array_filter($lines, static fn(string $l): bool => $l !== ''));
}

function bsi_claim_check_rate_limit_ok(): bool
{
  $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
  if ($ip === '') {
    return true;
  }

  $key = 'bsi_claim_rl_' . md5($ip);
  $count = (int) get_transient($key);

  if ($count >= BSI_CLAIM_CHECK_RATE_LIMIT) {
    return false;
  }

  set_transient($key, $count + 1, MINUTE_IN_SECONDS);

  return true;
}
