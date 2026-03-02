<?php
$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
$current_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
$current_path = untrailingslashit($current_path);

$items = [
  [
    'label' => 'Документы',
    'url' => home_url('/agentstvam/dokumenty/'),
  ],
  [
    'label' => 'Сотрудничество',
    'url' => home_url('/agentstvam/'),
  ],
  [
    'label' => 'Обучение',
    'url' => home_url('/agentstvam/obuchenie/'),
  ],
  [
    'label' => 'Вебинары',
    'url' => home_url('/agentstvam/vebinary/'),
  ],
  [
    'label' => 'Мероприятия',
    'url' => home_url('/agentstvam/meropriyatiya/'),
  ],
  [
    'label' => 'Рекламные туры',
    'url' => home_url('/agentstvam/reklamnye-tury/'),
  ],
];
?>

<nav class="agency-sidebar" aria-label="Разделы агентствам">
  <ul class="agency-sidebar__list">
    <?php foreach ($items as $item): ?>
      <?php
      $item_path = (string) wp_parse_url($item['url'], PHP_URL_PATH);
      $item_path = untrailingslashit($item_path);
      $is_active = $item_path === $current_path;
      ?>
      <li class="agency-sidebar__item">
        <a href="<?php echo esc_url($item['url']); ?>"
           class="agency-sidebar__link <?php echo $is_active ? 'is-active' : ''; ?>">
          <?php echo esc_html($item['label']); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
