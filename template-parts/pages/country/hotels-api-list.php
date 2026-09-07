<?php
/**
 * Содержимое каталога отелей страны: счётчик, фильтр курортов, сетка и пагинация.
 *
 * Тот же файл рисует и первый заход, и AJAX-подгрузку страницы или курорта
 * (inc/requests/ajax-hotels-api-catalog.php), поэтому разметка одна на оба пути.
 *
 * @var WP_Post $args['country']
 * @var array   $args['catalog'] результат bsi_hotels_api_catalog_query()
 * @var int     $args['paged']
 */

$country = $args['country'] ?? null;
$catalog = $args['catalog'] ?? null;

if (!$country instanceof WP_Post || !is_array($catalog)) {
  return;
}

$catalog_url = bsi_hotels_api_catalog_url($country);

$list = $catalog['list'];
$resorts = $catalog['resorts'];
$error = $catalog['error'];
$resort = $catalog['resort'];
$paged = max(1, (int) ($args['paged'] ?? 1));

$resort_name = '';
foreach ($resorts as $city) {
  if (($city['slug'] ?? '') === $resort) {
    $resort_name = (string) ($city['name'] ?? '');
    break;
  }
}
?>

  <?php if (count($resorts) > 1): ?>
    <?php
    // Крупные курорты сразу, длинный хвост — под раскрытие. Ссылки в разметке
    // есть в обоих случаях, поэтому поисковик видит все курорты страны.
    $visible_resorts = 11;
    $sorted_resorts = $resorts;
    usort($sorted_resorts, static fn($a, $b) => (int) ($b['hotels'] ?? 0) <=> (int) ($a['hotels'] ?? 0));

    $head = array_slice($sorted_resorts, 0, $visible_resorts);
    $tail = array_slice($sorted_resorts, $visible_resorts);

    $render_resort = static function (array $city) use ($resort, $catalog_url) {
      $city_slug = (string) ($city['slug'] ?? '');
      if ($city_slug === '') {
        return;
      }
      printf(
        '<a class="country-resorts-filter__item%s" href="%s">%s <span class="country-resorts-filter__count">%d</span></a>',
        $resort === $city_slug ? ' is-active' : '',
        esc_url(bsi_hotels_api_resort_url($catalog_url, $city_slug)),
        esc_html((string) ($city['name'] ?? $city_slug)),
        (int) ($city['hotels'] ?? 0)
      );
    };
    ?>

    <nav class="country-resorts-filter" aria-label="Курорты">
      <a class="country-resorts-filter__item<?= $resort === '' ? ' is-active' : ''; ?>"
         href="<?= esc_url($catalog_url); ?>">Все курорты</a>

      <?php array_map($render_resort, $head); ?>

      <?php if ($tail): ?>
        <details class="country-resorts-filter__more">
          <summary class="country-resorts-filter__item">Ещё курорты (<?= count($tail); ?>)</summary>
          <div class="country-resorts-filter__tail">
            <?php array_map($render_resort, $tail); ?>
          </div>
        </details>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

<?php if ($error !== ''): ?>
  <div class="country-hotels__message">
    <p>Каталог отелей сейчас недоступен. Подберём отель по запросу — напишите нам.</p>
    <?php if (current_user_can('manage_options')): ?>
      <p class="country-hotels__message-debug">Хаб отелей: <?= esc_html($error); ?></p>
    <?php endif; ?>
  </div>

<?php elseif (empty($list['items'])): ?>
  <div class="country-hotels__message">
    <p>
      <?php if ($resort_name !== ''): ?>
        В курорте «<?= esc_html($resort_name); ?>» отелей пока нет.
        <a href="<?= esc_url($catalog_url); ?>">Показать все отели</a>.
      <?php else: ?>
        Каталог отелей для этого направления пока пуст — подберём вариант по запросу.
      <?php endif; ?>
    </p>
  </div>

<?php else: ?>

  <div class="country-hotels__counter">
    Нашли отелей: <?= (int) $list['total']; ?>
  </div>

  <div class="country-hotels__grid">
    <?php foreach ($list['items'] as $hotel): ?>
      <?php get_template_part('template-parts/hotels/api-card', null, [
        'hotel' => $hotel,
        'country_url' => $catalog_url,
      ]); ?>
    <?php endforeach; ?>
  </div>

  <?php if ((int) $list['pages'] > 1): ?>
    <nav class="ui-pagination country-hotels__pagination" aria-label="Навигация по страницам каталога отелей">
      <?php
      echo paginate_links([
        /* %_% → format: первая страница остаётся без /page/1/. */
        'base' => ($resort !== '' ? bsi_hotels_api_resort_url($catalog_url, $resort) : $catalog_url) . '%_%',
        'format' => 'page/%#%/',
        'total' => (int) $list['pages'],
        'current' => $paged,
        'prev_text' => 'Назад',
        'next_text' => 'Вперёд',
        'mid_size' => 2,
      ]);
      ?>
    </nav>
  <?php endif; ?>

<?php endif; ?>
