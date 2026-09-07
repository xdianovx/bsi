<?php
/**
 * Колонка фильтров каталога: курорты, звёзды, вид объекта, удобства, пляж.
 *
 * Форма настоящая: без JS она отправляется обычным GET и отбор делает сервер.
 * С JS отправка перехватывается и меняются только список с картой.
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

$amenity_groups = bsi_hotels_api_amenity_groups();
$types = bsi_hotels_api_hotel_types();
$beach_options = bsi_hotels_api_beach_options();
$flag_options = bsi_hotels_api_flag_options();

$active = bsi_hotels_api_filters_active($filters);

// Крупные курорты сразу, длинный хвост — под раскрытие. Ссылки есть в обоих
// случаях, поэтому поисковик видит все курорты страны.
usort($resorts, static fn($a, $b) => (int) ($b['hotels'] ?? 0) <=> (int) ($a['hotels'] ?? 0));
$resorts_head = array_slice($resorts, 0, 8);
$resorts_tail = array_slice($resorts, 8);
?>

<aside class="hotels-filters js-hotels-filters-panel">
  <form class="hotels-filters__form js-hotels-filters" id="hotels-filters-form" action="<?= esc_url($action); ?>" method="get">
    <div class="hotels-filters__head">
      <b>Фильтры</b>
      <button class="hotels-filters__close js-hotels-filters-close" type="button" aria-label="Закрыть фильтры">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <label class="hotels-filters__search">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="<?= esc_attr($filters['q']); ?>" placeholder="Название отеля">
    </label>

    <?php if (count($resorts) > 1): ?>
      <?php
      /* Хаб отбирает по одному городу, поэтому выбранным остаётся один курорт:
         клик по другому снимает прежний (js/modules/ajax/hotels-filters.js). */
      $render_resort = static function (array $city) use ($resort) {
        $slug = (string) ($city['slug'] ?? '');
        if ($slug === '') {
          return;
        }
        ?>
        <label class="ui-checkbox hotels-filters__resort">
          <input type="checkbox" class="ui-checkbox__input js-hotels-city" name="city[]" value="<?= esc_attr($slug); ?>"
            <?php checked($resort, $slug); ?>>
          <span class="ui-checkbox__mark"></span>
          <span class="ui-checkbox__text">
            <?= esc_html((string) ($city['name'] ?? $slug)); ?>
            <i class="hotels-filters__count"><?= (int) ($city['hotels'] ?? 0); ?></i>
          </span>
        </label>
        <?php
      };
      ?>

      <details class="hotels-filters__group" open>
        <summary>Курорт</summary>
        <div class="hotels-filters__group-body">
          <?php array_map($render_resort, $resorts_head); ?>

          <?php if ($resorts_tail): ?>
            <details class="hotels-filters__resorts-more">
              <summary>Ещё курорты (<?= count($resorts_tail); ?>)</summary>
              <div class="hotels-filters__group-body">
                <?php array_map($render_resort, $resorts_tail); ?>
              </div>
            </details>
          <?php endif; ?>
        </div>
      </details>
    <?php endif; ?>

    <details class="hotels-filters__group" open>
      <summary>Звёзды</summary>
      <div class="hotels-filters__group-body">
        <?php for ($star = 5; $star >= 1; $star--): ?>
          <label class="ui-checkbox">
            <input type="checkbox" class="ui-checkbox__input" name="stars[]" value="<?= $star; ?>"
              <?php checked(in_array($star, $filters['stars'], true)); ?>>
            <span class="ui-checkbox__mark"></span>
            <span class="ui-checkbox__text"><?= $star; ?> звёзд<?= $star === 1 ? 'а' : ''; ?></span>
          </label>
        <?php endfor; ?>
      </div>
    </details>

    <?php if ($types): ?>
      <details class="hotels-filters__group" <?= $filters['type'] !== '' ? 'open' : ''; ?>>
        <summary>Вид объекта</summary>
        <div class="hotels-filters__group-body">
          <label class="ui-checkbox">
            <input type="radio" class="ui-checkbox__input" name="type" value="" <?php checked($filters['type'], ''); ?>>
            <span class="ui-checkbox__mark"></span>
            <span class="ui-checkbox__text">Любой</span>
          </label>

          <?php foreach ($types as $type): ?>
            <label class="ui-checkbox">
              <input type="radio" class="ui-checkbox__input" name="type" value="<?= esc_attr($type['slug']); ?>"
                <?php checked($filters['type'], $type['slug']); ?>>
              <span class="ui-checkbox__mark"></span>
              <span class="ui-checkbox__text">
                <?= esc_html($type['name']); ?>
                <?php if ($type['hotels']): ?><i class="hotels-filters__count"><?= (int) $type['hotels']; ?></i><?php endif; ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endif; ?>

    <?php foreach ($amenity_groups as $group => $amenities): ?>
      <?php $group_active = (bool) array_intersect(array_column($amenities, 'slug'), $filters['amenities']); ?>
      <details class="hotels-filters__group" <?= $group_active ? 'open' : ''; ?>>
        <summary><?= esc_html($group); ?></summary>
        <div class="hotels-filters__group-body">
          <?php foreach ($amenities as $amenity): ?>
            <label class="ui-checkbox">
              <input type="checkbox" class="ui-checkbox__input" name="amenities[]" value="<?= esc_attr($amenity['slug']); ?>"
                <?php checked(in_array($amenity['slug'], $filters['amenities'], true)); ?>>
              <span class="ui-checkbox__mark"></span>
              <span class="ui-checkbox__text">
                <?php if ($amenity['icon']): ?>
                  <img class="hotels-filters__icon" src="<?= esc_url($amenity['icon']); ?>" alt="" loading="lazy">
                <?php endif; ?>
                <?= esc_html($amenity['name']); ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>

    <details class="hotels-filters__group" <?= $filters['beach_line'] ? 'open' : ''; ?>>
      <summary>Пляж</summary>
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
    </details>

    <details class="hotels-filters__group" open>
      <summary>Показывать</summary>
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
    </details>

    <div class="hotels-filters__actions">
      <?php /* Отбор применяется сразу при выборе; кнопка нужна там, где JS нет. */ ?>
      <noscript>
        <button class="btn btn-accent sm" type="submit">Показать<?= $total ? ' (' . $total . ')' : ''; ?></button>
      </noscript>

      <?php if ($active): ?>
        <a class="hotels-filters__reset" href="<?= esc_url($action); ?>">Сбросить фильтры</a>
      <?php endif; ?>
    </div>
  </form>
</aside>
