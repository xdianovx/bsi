<?php
/**
 * Каталог отелей страны: фильтры слева, список в центре, карта справа.
 *
 * Тот же файл рисует и первый заход, и AJAX-подгрузку (inc/requests/ajax-hotels-api-catalog.php),
 * поэтому разметка одна на оба пути. Каркас — фильтры и карта — стоит всегда:
 * пустая выдача убирает только карточки, чтобы было чем поправить отбор.
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

$filters = $catalog['filters'] ?? bsi_hotels_api_catalog_filters();
$filters_query = bsi_hotels_api_filters_to_query($filters);
$base_url = $resort !== '' ? bsi_hotels_api_resort_url($catalog_url, $resort) : $catalog_url;

$resort_name = '';
foreach ($resorts as $city) {
  if (($city['slug'] ?? '') === $resort) {
    $resort_name = (string) ($city['name'] ?? '');
    break;
  }
}

// Карта показывает ту же выборку, что и список.
$map = bsi_hotels_api_map_points($country, $resort, $list['items'], $filters);
$has_more = $paged < (int) $list['pages'];
?>

<?php
/* Карточки со статусом `updating` ждут хаб: их цены обновляются прямо сейчас.
   js/modules/ajax/hotels-prices.js собирает их id и спрашивает только цены. */
?>
<div class="hotels-catalog<?= $map['points'] ? '' : ' hotels-catalog--no-map'; ?>"
     data-hotels-prices
     data-instant="<?= in_array('instant', $filters['flags'] ?? [], true) ? '1' : ''; ?>">
  <div class="hotels-catalog__inner">

    <?php get_template_part('template-parts/pages/country/hotels-api-filters', null, [
      'filters' => $filters,
      'action' => $base_url,
      'catalog_url' => $catalog_url,
      'resorts' => $resorts,
      'resort' => $resort,
      'total' => (int) ($list['total'] ?? 0),
    ]); ?>

    <div class="hotels-catalog__list">
      <div class="hotels-catalog__head">
        <div class="hotels-catalog__counter">
          <?php if ($error !== ''): ?>
            Подбираем отели…
          <?php else: ?>
            Нашли отелей: <?= (int) $list['total']; ?>
          <?php endif; ?>
        </div>

        <?php if (bsi_hotels_api_filters_active($filters)): ?>
          <?php /* Сброс стоит напротив счётчика: строка «нашли 106» и есть тот
                   результат, который сбрасывают. В панели он дублируется только
                   на телефоне, где панель занимает весь экран. */ ?>
          <a class="hotels-catalog__reset" href="<?= esc_url($base_url); ?>">Сбросить фильтры</a>
        <?php endif; ?>

        <button class="btn btn-gray sm hotels-catalog__filters-open js-hotels-filters-toggle" type="button">
          Фильтры
        </button>
      </div>

      <?php if ($error !== ''): ?>
        <?php /* Хаб не ответил в отведённые секунды. Страницу не держим: показываем
                 заглушки и просим каталог ещё раз через AJAX, где ждать не жалко.
                 Разметку повтора видит js/modules/ajax/hotels-api-catalog.js. */ ?>
        <div class="country-hotels__pending js-hotels-retry"
             data-page="<?= (int) $paged; ?>"
             data-resort="<?= esc_attr($resort); ?>">
          <div class="hotels-catalog__rows">
            <?php for ($i = 0; $i < 5; $i++): ?>
              <div class="hotels-catalog__skeleton"></div>
            <?php endfor; ?>
          </div>

          <noscript>
            <p>Каталог отелей сейчас недоступен. Подберём отель по запросу — напишите нам.</p>
          </noscript>

          <?php if (current_user_can('manage_options')): ?>
            <p class="country-hotels__message-debug">Хаб отелей: <?= esc_html($error); ?></p>
          <?php endif; ?>
        </div>

      <?php elseif (empty($list['items'])): ?>
        <div class="hotels-catalog__empty">
          <?php if (bsi_hotels_api_filters_active($filters)): ?>
            <p>По выбранным условиям отелей нет.</p>
            <a class="btn btn-gray sm" href="<?= esc_url($base_url); ?>">Сбросить фильтры</a>
          <?php elseif ($resort_name !== ''): ?>
            <p>В курорте «<?= esc_html($resort_name); ?>» отелей пока нет.</p>
            <a class="btn btn-gray sm" href="<?= esc_url($catalog_url); ?>">Показать все отели</a>
          <?php else: ?>
            <p>Каталог отелей для этого направления пока пуст — подберём вариант по запросу.</p>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <div class="hotels-catalog__rows js-hotels-rows">
          <?php foreach ($list['items'] as $hotel): ?>
            <?php get_template_part('template-parts/hotels/api-row', null, [
              'hotel' => $hotel,
              'country_url' => $catalog_url,
            ]); ?>
          <?php endforeach; ?>
        </div>

        <?php /* Постраничных адресов у каталога нет: следующая порция
                 догружается по номеру страницы, адрес остаётся одним. */ ?>
        <?php if ($has_more): ?>
          <div class="hotels-catalog__more js-hotels-more" data-next-page="<?= (int) ($paged + 1); ?>">
            <button type="button" class="btn btn-gray hotels-catalog__more-btn">Показать ещё</button>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>

  <?php if ($map['points']): ?>
    <aside class="hotels-catalog__map-side">
      <div class="country-hotels__map js-hotels-map"></div>
      <script type="application/json" class="js-hotels-map-data"><?= wp_json_encode($map['points']); ?></script>
    </aside>

    <?php /* Кнопка мобильной карты нужна только когда карте есть что показать:
             у отелей без координат она открывала бы пустоту. */ ?>
    <button class="btn btn-accent hotels-catalog__map-open js-hotels-map-open" type="button">На карте</button>
  <?php endif; ?>
</div>
