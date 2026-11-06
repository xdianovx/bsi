<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

const BSI_EVENT_EXPORT_CAP = 'manage_options';

function bsi_event_export_statuses(): array
{
  return array_values(array_diff(array_keys(get_post_stati()), ['auto-draft']));
}

function bsi_event_export_ids(): array
{
  return array_map('intval', get_posts([
    'post_type' => 'event',
    'post_status' => bsi_event_export_statuses(),
    'posts_per_page' => -1,
    'fields' => 'ids',
    'orderby' => 'ID',
    'order' => 'ASC',
    'suppress_filters' => true,
    'bsi_include_finished_events' => true,
    'bsi_skip_schedule' => true,
  ]));
}

function bsi_event_export_meta(int $post_id): array
{
  $out = [];
  foreach (get_post_custom($post_id) as $key => $values) {
    if (in_array($key, ['_edit_lock', '_edit_last'], true)) {
      continue;
    }
    $normalized = array_map(static fn($value) => is_string($value) ? maybe_unserialize($value) : $value, $values);
    $out[$key] = count($normalized) === 1 ? reset($normalized) : $normalized;
  }

  return $out;
}

function bsi_event_export_local_file(string $file): ?string
{
  $uploads = wp_get_upload_dir();
  $base = realpath((string) $uploads['basedir']);
  $real = realpath($file);
  if (!$base || !$real || !is_file($real) || !is_readable($real)) {
    return null;
  }
  $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

  return str_starts_with($real, $prefix) ? $real : null;
}

function bsi_event_export_error(array &$errors, string $key, string $url, string $message): void
{
  $errors[] = [
    'media_key' => $key,
    'source_url' => $url,
    'message' => $message,
  ];
}

function bsi_event_export_add_attachment(
  int $attachment_id,
  array &$media,
  array &$refs,
  array &$errors
): void {
  if (get_post_type($attachment_id) !== 'attachment') {
    return;
  }

  $key = 'attachment_' . $attachment_id;
  $refs[$key] = true;
  if (isset($media[$key])) {
    return;
  }

  $attachment = get_post($attachment_id);
  $source_url = (string) wp_get_attachment_url($attachment_id);
  $source_file = (string) get_attached_file($attachment_id);
  $entry = [
    'key' => $key,
    'kind' => 'attachment',
    'attachment_id' => $attachment_id,
    'source_url' => $source_url,
    'source_path' => null,
    'package_path' => null,
    'file_name' => wp_basename($source_file),
    'mime_type' => get_post_mime_type($attachment_id),
    'title' => get_the_title($attachment_id),
    'alt' => (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
    'caption' => $attachment ? $attachment->post_excerpt : '',
    'description' => $attachment ? $attachment->post_content : '',
    'post_status' => $attachment ? $attachment->post_status : '',
    'post_parent' => $attachment ? (int) $attachment->post_parent : 0,
    'uploaded_at' => $attachment ? $attachment->post_date_gmt : '',
    'attachment_metadata' => wp_get_attachment_metadata($attachment_id) ?: null,
    'meta' => bsi_event_export_meta($attachment_id),
    'size_bytes' => null,
    'sha256' => null,
  ];

  $local = bsi_event_export_local_file($source_file);
  if ($local === null) {
    $message = 'Файл отсутствует, недоступен для чтения или находится вне каталога uploads.';
    $entry['error'] = $message;
    bsi_event_export_error($errors, $key, $source_url, $message);
    $media[$key] = $entry;
    return;
  }

  $uploads = wp_get_upload_dir();
  $base = realpath((string) $uploads['basedir']);
  $entry['source_path'] = ltrim(substr($local, strlen((string) $base)), DIRECTORY_SEPARATOR);
  $name = sanitize_file_name(wp_basename($local)) ?: 'file';
  $entry['package_path'] = 'media/' . $attachment_id . '/' . $name;
  $entry['size_bytes'] = (int) filesize($local);
  $entry['sha256'] = hash_file('sha256', $local);
  $media[$key] = $entry;
}

function bsi_event_export_add_url(string $url, array &$media, array &$refs, array &$errors): void
{
  $url = rtrim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), '.,;:)');
  if ($url === '') {
    return;
  }

  $attachment_id = attachment_url_to_postid($url);
  if ($attachment_id > 0) {
    bsi_event_export_add_attachment($attachment_id, $media, $refs, $errors);
    return;
  }

  $uploads = wp_get_upload_dir();
  $uploads_path = trailingslashit((string) wp_parse_url((string) $uploads['baseurl'], PHP_URL_PATH));
  $path = (string) wp_parse_url($url, PHP_URL_PATH);
  if ($uploads_path === '/' || !str_starts_with($path, $uploads_path)) {
    return;
  }

  $relative = rawurldecode(substr($path, strlen($uploads_path)));
  if ($relative === '' || str_contains($relative, '..')) {
    return;
  }

  $key = 'url_' . hash('sha256', $url);
  $refs[$key] = true;
  if (isset($media[$key])) {
    return;
  }

  $entry = [
    'key' => $key,
    'kind' => 'uploads_url',
    'attachment_id' => null,
    'source_url' => $url,
    'source_path' => null,
    'package_path' => null,
    'file_name' => wp_basename($relative),
    'mime_type' => null,
    'title' => '',
    'alt' => '',
    'caption' => '',
    'description' => '',
    'post_status' => '',
    'post_parent' => 0,
    'uploaded_at' => '',
    'attachment_metadata' => null,
    'meta' => new stdClass(),
    'size_bytes' => null,
    'sha256' => null,
  ];

  $local = bsi_event_export_local_file(trailingslashit((string) $uploads['basedir']) . $relative);
  if ($local === null) {
    $message = 'Файл из ссылки не найден в локальном каталоге uploads.';
    $entry['error'] = $message;
    bsi_event_export_error($errors, $key, $url, $message);
    $media[$key] = $entry;
    return;
  }

  $base = realpath((string) $uploads['basedir']);
  $entry['source_path'] = ltrim(substr($local, strlen((string) $base)), DIRECTORY_SEPARATOR);
  $name = sanitize_file_name(wp_basename($local)) ?: 'file';
  $entry['package_path'] = 'media/url/' . hash('sha256', $url) . '-' . $name;
  $entry['mime_type'] = wp_check_filetype($local)['type'] ?? null;
  $entry['size_bytes'] = (int) filesize($local);
  $entry['sha256'] = hash_file('sha256', $local);
  $media[$key] = $entry;
}

function bsi_event_export_collect_media(
  $value,
  string $field_key,
  array &$media,
  array &$refs,
  array &$errors
): void {
  if (is_object($value)) {
    $value = get_object_vars($value);
  }
  if (is_array($value)) {
    foreach ($value as $item) {
      bsi_event_export_collect_media($item, $field_key, $media, $refs, $errors);
    }
    return;
  }
  if (!is_string($value) && !is_int($value)) {
    return;
  }

  $is_attachment_field = $field_key === '_thumbnail_id'
    || (bool) preg_match('~(?:^|_)(?:event_hero_cover(?:_mobile)?|venue_scheme|date_scheme|tour_gallery)$~', $field_key);
  if ($is_attachment_field && is_numeric($value)) {
    bsi_event_export_add_attachment((int) $value, $media, $refs, $errors);
  }

  if (!is_string($value) || !str_contains($value, 'http')) {
    return;
  }
  preg_match_all('~https?://[^\s<>"\']+~iu', $value, $matches);
  foreach ($matches[0] ?? [] as $url) {
    bsi_event_export_add_url($url, $media, $refs, $errors);
  }
}

function bsi_event_export_term(WP_Term $term): array
{
  $meta = [];
  foreach (get_term_meta($term->term_id) as $key => $values) {
    $normalized = array_map(static fn($value) => maybe_unserialize($value), $values);
    $meta[$key] = count($normalized) === 1 ? reset($normalized) : $normalized;
  }

  return [
    'term_id' => (int) $term->term_id,
    'taxonomy' => $term->taxonomy,
    'name' => $term->name,
    'slug' => $term->slug,
    'description' => $term->description,
    'parent' => (int) $term->parent,
    'count' => (int) $term->count,
    'meta' => $meta,
  ];
}

function bsi_event_export_country($value): ?array
{
  if ($value instanceof WP_Post) {
    $value = $value->ID;
  } elseif (is_array($value)) {
    $value = reset($value);
  }
  $country_id = (int) $value;
  if (!$country_id || get_post_type($country_id) !== 'country') {
    return null;
  }

  return [
    'id' => $country_id,
    'title' => get_the_title($country_id),
    'slug' => (string) get_post_field('post_name', $country_id),
    'status' => (string) get_post_field('post_status', $country_id),
    'permalink' => get_permalink($country_id),
  ];
}

function bsi_event_export_samo(int $post_id, array $fields): array
{
  $main_url = trim((string) ($fields['tour_booking_url'] ?? get_post_meta($post_id, 'tour_booking_url', true)));
  $tours = [];
  if ($main_url !== '') {
    $tours[] = [
      'source' => 'main',
      'concert_date' => null,
      'url' => $main_url,
      'ref' => function_exists('bsi_crosstour_ref_from_url') ? bsi_crosstour_ref_from_url($main_url) : null,
    ];
  }
  foreach ((array) ($fields['tour_booking_urls_more'] ?? []) as $row) {
    $url = trim((string) ($row['url'] ?? ''));
    if ($url === '') {
      continue;
    }
    $tours[] = [
      'source' => 'additional',
      'concert_date' => (string) ($row['concert_date'] ?? ''),
      'url' => $url,
      'ref' => function_exists('bsi_crosstour_ref_from_url') ? bsi_crosstour_ref_from_url($url) : null,
    ];
  }

  return [
    'data_source' => (string) ($fields['event_data_source'] ?? get_post_meta($post_id, 'event_data_source', true)),
    'binding_slugs' => function_exists('bsi_crosstour_event_slugs') ? bsi_crosstour_event_slugs($post_id) : [],
    'tours' => $tours,
  ];
}

function bsi_event_export_event(int $post_id, array &$media, array &$terms, array &$errors): array
{
  $post = get_post($post_id);
  $fields = function_exists('get_fields') ? (array) get_fields($post_id) : [];
  $meta = bsi_event_export_meta($post_id);
  $refs = [];
  bsi_event_export_collect_media($post->to_array(), 'post', $media, $refs, $errors);
  foreach ($fields as $key => $value) {
    bsi_event_export_collect_media($value, (string) $key, $media, $refs, $errors);
  }
  foreach ($meta as $key => $value) {
    bsi_event_export_collect_media($value, (string) $key, $media, $refs, $errors);
  }

  $taxonomies = [];
  foreach (get_object_taxonomies('event') as $taxonomy) {
    $direct = wp_get_post_terms($post_id, $taxonomy);
    if (is_wp_error($direct) || empty($direct)) {
      continue;
    }
    $taxonomies[$taxonomy] = array_map('intval', wp_list_pluck($direct, 'term_id'));
    foreach ($direct as $term) {
      $related = array_merge([$term->term_id], get_ancestors($term->term_id, $taxonomy, 'taxonomy'));
      foreach ($related as $term_id) {
        $item = get_term($term_id, $taxonomy);
        if ($item instanceof WP_Term) {
          $terms[$taxonomy . ':' . $term_id] = bsi_event_export_term($item);
        }
      }
    }
  }

  return [
    'id' => $post_id,
    'permalink' => get_permalink($post_id),
    'post' => $post->to_array(),
    'fields' => $fields,
    'meta' => $meta,
    'taxonomies' => $taxonomies,
    'country' => bsi_event_export_country($fields['tour_country'] ?? get_post_meta($post_id, 'tour_country', true)),
    'media' => array_values(array_keys($refs)),
    'samo' => bsi_event_export_samo($post_id, $fields),
    'computed' => [
      'status' => function_exists('bsi_event_status') ? bsi_event_status($post_id) : null,
      'last_date' => function_exists('bsi_event_last_date') ? bsi_event_last_date($post_id) : null,
    ],
  ];
}

function bsi_event_export_payload(): array
{
  $events = [];
  $media = [];
  $terms = [];
  $errors = [];
  $ids = bsi_event_export_ids();
  foreach ($ids as $post_id) {
    $events[] = bsi_event_export_event($post_id, $media, $terms, $errors);
  }
  $settings = function_exists('get_fields') ? (array) get_fields('bsi_event_settings') : [];
  $settings_refs = [];
  bsi_event_export_collect_media($settings, 'event_settings', $media, $settings_refs, $errors);

  return [
    'format' => 'bsi-event-tours',
    'version' => 1,
    'generated_at' => wp_date('c'),
    'site' => [
      'home_url' => home_url('/'),
      'site_url' => site_url('/'),
      'wordpress_version' => get_bloginfo('version'),
      'timezone' => wp_timezone_string(),
      'locale' => get_locale(),
    ],
    'post_type' => 'event',
    'settings' => $settings,
    'counts' => [
      'events' => count($events),
      'terms' => count($terms),
      'media' => count($media),
      'errors' => count($errors),
    ],
    'events' => $events,
    'terms' => array_values($terms),
    'media' => $media,
    'errors' => $errors,
  ];
}

function bsi_event_export_send(): void
{
  if (!current_user_can(BSI_EVENT_EXPORT_CAP)) {
    wp_die('Недостаточно прав.');
  }
  check_admin_referer('bsi_event_export');
  if (!class_exists('ZipArchive')) {
    wp_die('Для экспорта нужен PHP ZipArchive.');
  }

  $payload = bsi_event_export_payload();
  $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    wp_die('Не удалось сформировать JSON экспорта.');
  }

  $path = wp_tempnam('bsi-event-tours.zip');
  $zip = new ZipArchive();
  if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    wp_die('Не удалось создать ZIP-архив.');
  }
  $zip->addFromString('events.json', $json);
  $uploads = wp_get_upload_dir();
  foreach ($payload['media'] as $item) {
    if (empty($item['package_path']) || empty($item['source_path'])) {
      continue;
    }
    $local = bsi_event_export_local_file(trailingslashit((string) $uploads['basedir']) . $item['source_path']);
    if ($local === null || !$zip->addFile($local, $item['package_path'])) {
      $zip->close();
      unlink($path);
      wp_die('Не удалось добавить медиафайл в архив: ' . esc_html($item['key']));
    }
  }
  if (!$zip->close()) {
    unlink($path);
    wp_die('Не удалось завершить ZIP-архив.');
  }

  $filename = 'bsi-event-tours-' . wp_date('Ymd-His') . '.zip';
  $download_token = preg_replace('/\D/', '', (string) ($_POST['download_token'] ?? ''));
  if ($download_token !== '') {
    setcookie('bsi_event_export_done', $download_token, [
      'expires' => time() + 10 * MINUTE_IN_SECONDS,
      'path' => '/',
      'secure' => is_ssl(),
      'httponly' => false,
      'samesite' => 'Lax',
    ]);
  }
  nocache_headers();
  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Content-Length: ' . (string) filesize($path));
  readfile($path);
  unlink($path);
  exit;
}

add_action('admin_post_bsi_event_export', 'bsi_event_export_send');

add_action('admin_menu', static function (): void {
  add_submenu_page(
    'edit.php?post_type=event',
    'Экспорт событийных туров',
    'Экспорт',
    BSI_EVENT_EXPORT_CAP,
    'bsi-event-export',
    'bsi_event_export_page'
  );
});

function bsi_event_export_page(): void
{
  if (!current_user_can(BSI_EVENT_EXPORT_CAP)) {
    wp_die('Недостаточно прав.');
  }
  $count = count(bsi_event_export_ids());
  $zip_available = class_exists('ZipArchive');
?>
  <div class="wrap">
    <h1>Экспорт событийных туров</h1>
    <p>Скачивается ZIP-архив с файлом <code>events.json</code> и каталогом <code>media/</code>.</p>
    <ul>
      <li>Все события: опубликованные, запланированные, черновики, ожидающие, личные и записи в корзине.</li>
      <li>Поля ACF, исходные meta, таксономии, общие настройки и FAQ, связи со странами и ссылки SamoTour.</li>
      <li>Оригиналы изображений из обложек, галерей, схем и контента, с SHA-256 и путями для импорта.</li>
      <li>Динамические цены и наличие SamoTour не копируются: новый сервис получает их по экспортированным связям.</li>
    </ul>
    <p>Найдено событий: <strong><?= (int) $count ?></strong></p>

    <?php if (!$zip_available): ?>
      <div class="notice notice-error"><p>На сервере не установлен PHP ZipArchive. Экспорт недоступен.</p></div>
    <?php endif; ?>

    <form id="bsi-event-export-form" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
      <input type="hidden" name="action" value="bsi_event_export">
      <input type="hidden" name="download_token" value="">
      <?php wp_nonce_field('bsi_event_export'); ?>
      <p>
        <button type="submit" class="button button-primary"<?= $zip_available ? '' : ' disabled' ?>>Скачать ZIP с событиями и изображениями</button>
      </p>
      <p id="bsi-event-export-status" hidden></p>
    </form>
  </div>
  <?php if ($zip_available): ?>
    <script>
      (() => {
        const form = document.getElementById("bsi-event-export-form");
        if (!form) return;
        const button = form.querySelector("button[type=submit]");
        const status = document.getElementById("bsi-event-export-status");
        const tokenField = form.querySelector("input[name=download_token]");
        const buttonLabel = button.textContent;
        const waitingText = "Формируем архив. При большом количестве событий это может занять около минуты. Не закрывайте страницу.";

        form.addEventListener("submit", (event) => {
          if (form.dataset.running === "1") {
            event.preventDefault();
            return;
          }
          form.dataset.running = "1";
          const token = String(Date.now());
          tokenField.value = token;
          button.disabled = true;
          button.textContent = "Формируем архив…";
          status.textContent = waitingText;
          status.hidden = false;
          const started = Date.now();
          const timer = window.setInterval(() => {
            const cookie = document.cookie.split("; ").find((row) => row.startsWith("bsi_event_export_done="));
            if (cookie && cookie.split("=")[1] === token) {
              window.clearInterval(timer);
              document.cookie = "bsi_event_export_done=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
              status.textContent = "Архив сформирован. Скачивание началось.";
              button.disabled = false;
              button.textContent = buttonLabel;
              delete form.dataset.running;
              return;
            }
            if (Date.now() - started >= 180000) {
              window.clearInterval(timer);
              status.textContent = "Скачивание ещё не началось. Обновите страницу и попробуйте ещё раз.";
              button.disabled = false;
              button.textContent = buttonLabel;
              delete form.dataset.running;
            }
          }, 1000);
        });
      })();
    </script>
  <?php endif; ?>
<?php
}
