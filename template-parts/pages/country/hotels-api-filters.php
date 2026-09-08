<?php
/**
 * Колонка фильтров каталога: курорты, звёзды, удобства, вид объекта, пляж.
 *
 * Форма настоящая: без JS она отправляется обычным GET и отбор делает сервер.
 * С JS отправка перехватывается и меняются только список с картой.
 *
 * Групп немного нарочно: у хаба удобства разложены по четырнадцати категориям,
 * и каждая отдельной шторкой превращает панель в частокол заголовков.
 *
 * @var array  $args['filters']     разобранные фильтры текущего запроса
 * @var string $args['action']      адрес каталога с учётом курорта
 * @var string $args['catalog_url'] адрес каталога страны
 * @var array  $args['resorts']     курорты хаба
 * @var string $args['resort']      выбранный курорт
 * @var int    $args['total']       сколько отелей нашлось
 */

$filters = $args['filters'] ?? bsi_hotels_api_catalog_filters();
$action = (string) ($args['action'] ?? '');
$catalog_url = (string) ($args['catalog_url'] ?? $action);
$resorts = is_array($args['resorts'] ?? null) ? $args['resorts'] : [];
$resort = (string) ($args['resort'] ?? '');
$total = (int) ($args['total'] ?? 0);

$types = bsi_hotels_api_hotel_types();
$beach_options = bsi_hotels_api_beach_options();
$flag_options = bsi_hotels_api_flag_options();
$active = bsi_hotels_api_filters_active($filters);

// Удобства одним списком: сначала выбранные и популярные, остальные под «ещё».
$amenities = [];
foreach (bsi_hotels_api_amenity_groups() as $group) {
  foreach ($group as $amenity) {
    $amenities[] = $amenity;
  }
}

usort($amenities, static function (array $a, array $b) use ($filters) {
  $weight = static fn(array $x) => (in_array($x['slug'], $filters['amenities'], true) ? 2 : 0)
    + ($x['popular'] ? 1 : 0);

  return [$weight($b), $b['hotels']] <=> [$weight($a), $a['hotels']];
});

$amenities_head = array_slice($amenities, 0, 6);
$amenities_tail = array_slice($amenities, 6);

usort($resorts, static fn($a, $b) => (int) ($b['hotels'] ?? 0) <=> (int) ($a['hotels'] ?? 0));
$resorts_head = array_slice($resorts, 0, 6);
$resorts_tail = array_slice($resorts, 6);

$render_resort = static function (array $city) use ($resort, $catalog_url) {
  $slug = (string) ($city['slug'] ?? '');
  if ($slug === '') {
    return;
  }
  printf(
    '<a class="hotels-filters__resort%s" href="%s">%s <i>%d</i></a>',
    $resort === $slug ? ' is-active' : '',
    esc_url(bsi_hotels_api_resort_url($catalog_url, $slug)),
    esc_html((string) ($city['name'] ?? $slug)),
    (int) ($city['hotels'] ?? 0)
  );
};

$render_amenity = static function (array $amenity) use ($filters) { ?>
  <label class="ui-checkbox">
    <input type="checkbox" class="ui-checkbox__input" name="amenities[]" value="<?= esc_attr($amenity['slug']); ?>"
      <?php checked(in_array($amenity['slug'], $filters['amenities'], true)); ?>>
    <span class="ui-checkbox__mark"></span>
    <span class="ui-checkbox__text">
      <?php if ($amenity['icon']): ?>
        <img class="hotels-filters__icon" src="<?= esc_url($amenity['icon']); ?>" alt="" loading="lazy">
      <?php endif; ?>
      <?= esc_html($amenity['name']); ?>
      <?php if ($amenity['hotels']): ?><i class="hotels-filters__count"><?= (int) $amenity['hotels']; ?></i><?php endif; ?>
    </span>
  </label>
<?php };
?>

<aside class="hotels-filters js-hotels-filters-panel">
  <form class="hotels-filters__form js-hotels-filters" id="hotels-filters-form" action="<?= esc_url($action); ?>" method="get">
    <div class="hotels-filters__head">
      <b class="hotels-filters__head-title">Фильтры</b>

      <?php if ($active): ?>
        <a class="hotels-filters__reset" href="<?= esc_url($action); ?>">Сбросить</a>
      <?php endif; ?>

      <button class="hotels-filters__close js-hotels-filters-close" type="button" aria-label="Закрыть фильтры">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <label class="hotels-filters__search">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= esc_attr($filters['q']); ?>" placeholder="Название отеля">
    </label>

    <?php if (count($resorts) > 1): ?>
      <div class="hotels-filters__group js-hotels-resorts">
        <p class="hotels-filters__group-title">Курорт</p>

        <?php /* Курортов бывает под сотню. Поиск отбирает список на месте, без
                 запроса: курорты уже все в разметке, и выдачу он не меняет —
                 её меняет клик по найденному курорту. Поле не в форме, чтобы
                 не попасть в адрес фильтров, поэтому без name. */ ?>
        <?php if (count($resorts) > 8): ?>
          <label class="hotels-filters__find">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" class="js-hotels-resort-find" placeholder="Поиск курорта" autocomplete="off">
          </label>
        <?php endif; ?>

        <div class="hotels-filters__group-body">
          <a class="hotels-filters__resort<?= $resort === '' ? ' is-active' : ''; ?>"
             href="<?= esc_url($catalog_url); ?>">Все курорты</a>

          <?php array_map($render_resort, $resorts_head); ?>

          <?php if ($resorts_tail): ?>
            <details class="hotels-filters__more" data-key="resorts">
              <summary>
                <span class="hotels-filters__more-open">Ещё курорты (<?= count($resorts_tail); ?>)</span>
                <span class="hotels-filters__more-close">Свернуть</span>
              </summary>
              <div class="hotels-filters__group-body">
                <?php array_map($render_resort, $resorts_tail); ?>
              </div>
            </details>
          <?php endif; ?>

          <p class="hotels-filters__empty js-hotels-resorts-empty" hidden>Курорт не найден</p>
        </div>
      </div>
    <?php endif; ?>

    <div class="hotels-filters__group">
      <p class="hotels-filters__group-title">Звёзды</p>
      <?php /* Набором, а не столбцом галочек: пять строк на пять цифр — перерасход
               высоты в узкой панели, и выбор «4 и 5» читается одним взглядом. */ ?>
      <div class="hotels-filters__stars">
        <?php for ($star = 1; $star <= 5; $star++): ?>
          <label class="hotels-filters__chip">
            <input type="checkbox" name="stars[]" value="<?= $star; ?>"
              <?php checked(in_array($star, $filters['stars'], true)); ?>>
            <span>
              <?= $star; ?>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>
            </span>
          </label>
        <?php endfor; ?>
      </div>
    </div>

    <?php if ($amenities): ?>
      <div class="hotels-filters__group">
        <p class="hotels-filters__group-title">Удобства</p>
        <div class="hotels-filters__group-body">
          <?php array_map($render_amenity, $amenities_head); ?>

          <?php if ($amenities_tail): ?>
            <details class="hotels-filters__more" data-key="amenities" <?= array_intersect(array_column($amenities_tail, 'slug'), $filters['amenities']) ? 'open' : ''; ?>>
              <summary>
                <span class="hotels-filters__more-open">Ещё удобства (<?= count($amenities_tail); ?>)</span>
                <span class="hotels-filters__more-close">Свернуть</span>
              </summary>
              <div class="hotels-filters__group-body">
                <?php array_map($render_amenity, $amenities_tail); ?>
              </div>
            </details>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($types): ?>
      <div class="hotels-filters__group">
        <p class="hotels-filters__group-title">Вид объекта</p>
        <div class="hotels-filters__group-body">
          <?php /* Виды объектов набором: «вилла или апарт-отель» — обычный запрос,
                   а хаб принимает несколько значений списком. */ ?>
          <?php foreach ($types as $type): ?>
            <label class="ui-checkbox">
              <input type="checkbox" class="ui-checkbox__input" name="type[]" value="<?= esc_attr($type['slug']); ?>"
                <?php checked(in_array($type['slug'], $filters['type'], true)); ?>>
              <span class="ui-checkbox__mark"></span>
              <span class="ui-checkbox__text">
                <?= esc_html($type['name']); ?>
                <?php if ($type['hotels']): ?><i class="hotels-filters__count"><?= (int) $type['hotels']; ?></i><?php endif; ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="hotels-filters__group">
      <p class="hotels-filters__group-title">Пляж</p>
      <div class="hotels-filters__group-body">
        <label class="ui-checkbox">
          <input type="radio" class="ui-checkbox__input" name="beach_line" value="" <?php checked($filters['beach_line'], 0); ?>>
          <span class="ui-checkbox__mark"></span>
          <span class="ui-checkbox__text">Неважно</span>
        </label>

        <?php foreach ($beach_options as $line => $label): ?>
          <label class="ui-checkbox">
            <input type="radio" class="ui-checkbox__input" name="beach_line" value="<?= (int) $line; ?>"
              <?php checked($filters['beach_line'], $line); ?>>
            <span class="ui-checkbox__mark"></span>
            <span class="ui-checkbox__text"><?= esc_html($label); ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="hotels-filters__group hotels-filters__group--last">
      <p class="hotels-filters__group-title">Показывать</p>
      <div class="hotels-filters__group-body">
        <?php foreach ($flag_options as $flag => $label): ?>
          <label class="ui-checkbox">
            <input type="checkbox" class="ui-checkbox__input" name="<?= esc_attr($flag); ?>" value="1"
              <?php checked(in_array($flag, $filters['flags'], true)); ?>>
            <span class="ui-checkbox__mark"></span>
            <span class="ui-checkbox__text"><?= esc_html($label); ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <?php /* Отбор применяется сразу при выборе; кнопка нужна там, где JS нет. */ ?>
    <noscript>
      <button class="btn btn-accent sm" type="submit">Показать<?= $total ? ' (' . $total . ')' : ''; ?></button>
    </noscript>
  </form>
</aside>
