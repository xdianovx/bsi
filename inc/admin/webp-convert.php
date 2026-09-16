<?php

/**
 * Настройки сайта → «Конвертация в WebP» — прогон медиатеки на проде,
 * где есть только FTP (CLI недоступен).
 *
 * Новые загрузки обрабатывает хук в inc/helpers/webp.php; эта страница
 * догоняет всё, что лежало в медиатеке раньше. Работа идёт батчами
 * через admin-ajax, чтобы не упереться в 30-секундный таймаут FastCGI.
 *
 * Логика конвертации — inc/helpers/webp.php, CLI-двойник — tools/convert-webp.php.
 */

if (!defined('ABSPATH')) {
  exit;
}

const BSI_WEBP_BATCH = 10;
const BSI_WEBP_CAP = 'manage_options';

add_action('admin_menu', function () {
  add_submenu_page(
    'bsi-hub-settings',
    'Конвертация в WebP',
    'Конвертация в WebP',
    BSI_WEBP_CAP,
    'bsi-webp-convert',
    'bsi_webp_convert_page'
  );
}, 998.6);

/**
 * ID картинок, которым ещё нужен WebP.
 *
 * @param int $offset сколько вложений пропустить
 * @return int[]
 */
function bsi_webp_queue(int $offset = 0, int $limit = BSI_WEBP_BATCH): array
{
  return get_posts([
    'post_type' => 'attachment',
    'post_mime_type' => bsi_webp_convertible_mimes(),
    'post_status' => 'inherit',
    'posts_per_page' => $limit,
    'offset' => $offset,
    'orderby' => 'ID',
    'order' => 'ASC',
    'fields' => 'ids',
  ]);
}

/**
 * Всего картинок, подходящих для конвертации.
 */
function bsi_webp_total(): int
{
  $counts = (array) wp_count_attachments();
  $total = 0;

  foreach (bsi_webp_convertible_mimes() as $mime) {
    $total += (int) ($counts[$mime] ?? 0);
  }

  return $total;
}

function bsi_webp_convert_page(): void
{
  if (!current_user_can(BSI_WEBP_CAP)) {
    wp_die('Недостаточно прав.');
  }

  $total = bsi_webp_total();
  $has_gd = function_exists('imagewebp');
  ?>
  <div class="wrap">
    <h1>Конвертация картинок в WebP</h1>

    <?php if (!$has_gd): ?>
      <div class="notice notice-error">
        <p>В PHP нет функции <code>imagewebp()</code> — GD собран без поддержки WebP.
          Конвертация невозможна, нужна настройка на стороне хостинга.</p>
      </div>
    <?php else: ?>
      <p>
        Рядом с каждой картинкой кладётся файл <code>.webp</code> — и для оригинала,
        и для каждого размера, который нарезает WordPress. Исходники остаются на месте:
        браузер без поддержки формата получит их через <code>&lt;picture&gt;</code>.
        Уже сконвертированные файлы пропускаются, так что прогон можно повторять
        и прерывать без последствий.
      </p>
      <p>
        <strong>Место на диске.</strong> WebP занимает примерно треть от исходного файла,
        и появляется он в дополнение к нему. Для медиатеки в несколько гигабайт
        заложите прибавку около трети объёма.
      </p>

      <p>Картинок в медиатеке: <strong><?= (int) $total; ?></strong></p>

      <p>
        <button class="button button-primary" id="bsi-webp-run">Запустить конвертацию</button>
        <button class="button" id="bsi-webp-stop" disabled>Остановить</button>
      </p>

      <div id="bsi-webp-progress" style="display:none">
        <progress id="bsi-webp-bar" value="0" max="<?= (int) $total; ?>" style="width:420px;height:22px"></progress>
        <p id="bsi-webp-status"></p>
      </div>

      <pre id="bsi-webp-log" style="max-height:320px;overflow:auto;background:#fff;border:1px solid #dcdcde;padding:12px;display:none"></pre>

      <script>
        (function () {
          const nonce = <?= wp_json_encode(wp_create_nonce('bsi_webp_convert')); ?>;
          const total = <?= (int) $total; ?>;
          const runBtn = document.getElementById('bsi-webp-run');
          const stopBtn = document.getElementById('bsi-webp-stop');
          const bar = document.getElementById('bsi-webp-bar');
          const status = document.getElementById('bsi-webp-status');
          const log = document.getElementById('bsi-webp-log');
          const box = document.getElementById('bsi-webp-progress');

          let offset = 0;
          let files = 0;
          let stopped = false;

          function say(line) {
            log.style.display = 'block';
            log.textContent += line + '\n';
            log.scrollTop = log.scrollHeight;
          }

          async function step() {
            if (stopped) {
              status.textContent = 'Остановлено на ' + offset + ' из ' + total + '. Можно продолжить позже — готовые файлы пропускаются.';
              runBtn.disabled = false;
              stopBtn.disabled = true;
              return;
            }

            const body = new URLSearchParams({
              action: 'bsi_webp_convert',
              _wpnonce: nonce,
              offset: String(offset)
            });

            let data;
            try {
              const res = await fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' });
              data = await res.json();
            } catch (e) {
              say('Сеть не ответила, пробую ещё раз через 3 секунды');
              setTimeout(step, 3000);
              return;
            }

            if (!data || !data.success) {
              say('Ошибка: ' + ((data && data.data && data.data.message) || 'неизвестная'));
              runBtn.disabled = false;
              stopBtn.disabled = true;
              return;
            }

            offset = data.data.offset;
            files += data.data.files;
            bar.value = Math.min(offset, total);
            status.textContent = 'Обработано ' + Math.min(offset, total) + ' из ' + total + ', новых файлов WebP: ' + files;

            if (data.data.log) {
              data.data.log.forEach(say);
            }

            if (data.data.done) {
              status.textContent = 'Готово. Обработано ' + Math.min(offset, total) + ', создано файлов WebP: ' + files;
              runBtn.disabled = false;
              stopBtn.disabled = true;
              return;
            }

            step();
          }

          runBtn.addEventListener('click', function () {
            stopped = false;
            offset = 0;
            files = 0;
            log.textContent = '';
            box.style.display = 'block';
            runBtn.disabled = true;
            stopBtn.disabled = false;
            step();
          });

          stopBtn.addEventListener('click', function () {
            stopped = true;
            stopBtn.disabled = true;
          });
        })();
      </script>
    <?php endif; ?>
  </div>
  <?php
}

add_action('wp_ajax_bsi_webp_convert', function () {
  if (!current_user_can(BSI_WEBP_CAP)) {
    wp_send_json_error(['message' => 'Недостаточно прав'], 403);
  }

  check_ajax_referer('bsi_webp_convert');

  if (!function_exists('imagewebp')) {
    wp_send_json_error(['message' => 'В PHP нет imagewebp()'], 500);
  }

  $offset = max(0, (int) ($_POST['offset'] ?? 0));
  $ids = bsi_webp_queue($offset);

  if (empty($ids)) {
    wp_send_json_success(['offset' => $offset, 'files' => 0, 'done' => true, 'log' => []]);
  }

  $files = 0;
  $log = [];

  foreach ($ids as $id) {
    $made = bsi_webp_convert_attachment((int) $id);
    $files += $made;

    if ($made > 0) {
      $log[] = sprintf('%s — файлов: %d', get_the_title((int) $id) ?: ('ID ' . $id), $made);
    }
  }

  wp_send_json_success([
    'offset' => $offset + count($ids),
    'files' => $files,
    'done' => count($ids) < BSI_WEBP_BATCH,
    'log' => $log,
  ]);
});
