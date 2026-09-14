<?php

/**
 * Настройки сайта → «Импорт со старого сайта» — заливка JSON на проде,
 * где есть только FTP (CLI недоступен).
 *
 * Одна страница на обе сущности: файл tools/legacy-import/data/{код}.json
 * содержит и экскурсии, и достопримечательности страны. Раньше страниц было
 * две («Импорт экскурсий» и «Импорт достопримечательностей»), с раздельными
 * файлами и раздельными прогонами.
 *
 * Импорт идёт батчами по 20 записей через admin-ajax, чтобы не упереться
 * в 30-секундный таймаут FastCGI: сначала экскурсии, затем достопримечательности.
 * Логика — tools/legacy-import/importer.php.
 */

if (!defined('ABSPATH')) {
  exit;
}

const BSI_LEGACY_IMPORT_BATCH = 20;
const BSI_LEGACY_IMPORT_CAP = 'manage_options';

require_once get_template_directory() . '/tools/legacy-import/importer.php';

add_action('admin_menu', function () {
  add_submenu_page(
    'bsi-hub-settings',
    'Импорт со старого сайта',
    'Импорт со старого сайта',
    BSI_LEGACY_IMPORT_CAP,
    'bsi-legacy-import',
    'bsi_legacy_import_page'
  );
}, 998.5);

function bsi_legacy_import_page(): void
{
  if (!current_user_can(BSI_LEGACY_IMPORT_CAP)) {
    wp_die('Недостаточно прав.');
  }

  $files = bsi_legacy_unified_available_files();
  ?>
  <div class="wrap">
    <h1>Импорт со старого сайта</h1>

    <?php if (empty($files)): ?>
      <div class="notice notice-warning">
        <p>Нет файлов в <code>wp-content/themes/bsi/tools/legacy-import/data/</code>. Загрузите JSON по FTP.</p>
      </div>
    <?php else: ?>
      <p>
        Один файл — одна страна, внутри и экскурсии, и достопримечательности.
        Записи ищутся по мете <code>bsi_legacy_excursion_id</code> и <code>bsi_legacy_sight_id</code>:
        повторный запуск обновляет только свои записи. Заведённые вручную не перезаписываются
        и не дублируются — такие совпадения попадут в отчёт как «конфликт».
        <strong>Записи, текст которых правили на сайте после импорта, повторный прогон
        не перетирает</strong> — они попадут в отчёт как «сохранено правленых».
        Фото не переносятся: остались на старом сайте.
      </p>

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="bsi-legacy-file">Файл</label></th>
          <td>
            <select id="bsi-legacy-file">
              <?php foreach ($files as $name => $path): ?>
                <option value="<?= esc_attr($name); ?>"><?= esc_html($name); ?> (<?= esc_html(size_format((int) filesize($path))); ?>)</option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="bsi-legacy-status">Статус записей</label></th>
          <td>
            <select id="bsi-legacy-status">
              <option value="draft">Черновик</option>
              <option value="publish">Опубликовать</option>
            </select>
            <p class="description">Уже опубликованные записи импорт не понижает до черновика.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">Правленые вручную</th>
          <td>
            <label>
              <input type="checkbox" id="bsi-legacy-force">
              Перезаписать текстом из источника
            </label>
            <p class="description">
              По умолчанию такие записи импорт не трогает. Галочка вернёт им текст старого сайта —
              правки контент-команды пропадут.
            </p>
          </td>
        </tr>
      </table>

      <p>
        <button type="button" class="button" id="bsi-legacy-dry">Прогон без записи</button>
        <button type="button" class="button button-primary" id="bsi-legacy-run">Импортировать</button>
        <span id="bsi-legacy-progress" style="margin-left:12px;"></span>
      </p>

      <pre id="bsi-legacy-log" style="max-height:420px;overflow:auto;background:#fff;border:1px solid #dcdcde;padding:12px;display:none;"></pre>
    <?php endif; ?>
  </div>

  <script>
    (function () {
      const runBtn = document.getElementById('bsi-legacy-run');
      const dryBtn = document.getElementById('bsi-legacy-dry');
      if (!runBtn || !dryBtn) {
        return;
      }

      const fileEl = document.getElementById('bsi-legacy-file');
      const statusEl = document.getElementById('bsi-legacy-status');
      const forceEl = document.getElementById('bsi-legacy-force');
      const progressEl = document.getElementById('bsi-legacy-progress');
      const logEl = document.getElementById('bsi-legacy-log');
      const nonce = <?= wp_json_encode(wp_create_nonce('bsi_legacy_import')); ?>;
      const ajaxUrl = <?= wp_json_encode(admin_url('admin-ajax.php')); ?>;
      const labels = { excursions: 'экскурсии', sights: 'достопримечательности' };

      async function runBatch(entity, offset, dryRun, totals) {
        const body = new URLSearchParams({
          action: 'bsi_legacy_import',
          _wpnonce: nonce,
          file: fileEl.value,
          entity: entity,
          status: statusEl.value,
          offset: String(offset),
          dry_run: dryRun ? '1' : '0',
          force: forceEl.checked ? '1' : '0'
        });

        const response = await fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' });
        const json = await response.json();

        if (!json.success) {
          progressEl.textContent = 'Ошибка: ' + (json.data && json.data.message ? json.data.message : 'неизвестно');
          runBtn.disabled = false;
          dryBtn.disabled = false;
          return false;
        }

        const data = json.data;
        totals.created += data.created;
        totals.updated += data.updated;
        totals.skipped += data.skipped;
        totals.conflicts += data.conflicts;
        totals.protected += data.protected;

        if (data.log.length) {
          logEl.style.display = 'block';
          logEl.textContent += data.log.join('\n') + '\n';
          logEl.scrollTop = logEl.scrollHeight;
        }

        progressEl.textContent = labels[entity] + ': ' + data.next + ' из ' + data.total;

        if (data.next < data.total) {
          return runBatch(entity, data.next, dryRun, totals);
        }

        return true;
      }

      async function start(dryRun) {
        runBtn.disabled = true;
        dryBtn.disabled = true;
        logEl.textContent = '';
        logEl.style.display = 'none';
        progressEl.textContent = 'Старт…';

        const totals = { created: 0, updated: 0, skipped: 0, conflicts: 0, protected: 0 };

        for (const entity of ['excursions', 'sights']) {
          const ok = await runBatch(entity, 0, dryRun, totals);
          if (!ok) {
            return;
          }
        }

        progressEl.textContent = 'Готово: создано ' + totals.created +
          ', обновлено ' + totals.updated +
          ', сохранено правленых ' + totals.protected +
          ', конфликтов ' + totals.conflicts +
          (dryRun ? ' [прогон, ничего не записано]' : '');
        runBtn.disabled = false;
        dryBtn.disabled = false;
      }

      runBtn.addEventListener('click', function () { start(false); });
      dryBtn.addEventListener('click', function () { start(true); });
    })();
  </script>
  <?php
}

add_action('wp_ajax_bsi_legacy_import', function () {
  if (!current_user_can(BSI_LEGACY_IMPORT_CAP)) {
    wp_send_json_error(['message' => 'Недостаточно прав'], 403);
  }
  check_ajax_referer('bsi_legacy_import');

  if (!function_exists('update_field')) {
    wp_send_json_error(['message' => 'ACF не активен'], 500);
  }

  $files = bsi_legacy_unified_available_files();
  $file = isset($_POST['file']) ? sanitize_file_name(wp_unslash($_POST['file'])) : '';
  if (!isset($files[$file])) {
    wp_send_json_error(['message' => 'Файл не найден'], 400);
  }

  $entity = (isset($_POST['entity']) && $_POST['entity'] === 'sights') ? 'sights' : 'excursions';
  $status = (isset($_POST['status']) && $_POST['status'] === 'publish') ? 'publish' : 'draft';
  $dry_run = !empty($_POST['dry_run']) && $_POST['dry_run'] !== '0';
  $force = !empty($_POST['force']) && $_POST['force'] !== '0';
  $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;

  $loaded = bsi_legacy_unified_load_payload($files[$file]);
  if (is_wp_error($loaded)) {
    wp_send_json_error(['message' => $loaded->get_error_message()], 400);
  }

  $items = (array) ($loaded['payload'][$entity] ?? []);
  $total = count($items);
  $batch = array_slice($items, $offset, BSI_LEGACY_IMPORT_BATCH);

  $stats = $batch
    ? ($entity === 'sights'
      ? bsi_legacy_import_sights($batch, $loaded['country_id'], $status, $dry_run, $force)
      : bsi_legacy_import_items($batch, $loaded['country_id'], $status, $dry_run, $force))
    : ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'protected' => 0, 'log' => []];

  wp_send_json_success([
    'created' => $stats['created'],
    'updated' => $stats['updated'],
    'skipped' => $stats['skipped'],
    'conflicts' => $stats['conflicts'],
    'protected' => $stats['protected'],
    'log' => $stats['log'],
    'next' => min($offset + BSI_LEGACY_IMPORT_BATCH, $total),
    'total' => $total,
  ]);
});
