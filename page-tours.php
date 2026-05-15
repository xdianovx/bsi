<?php
/**
 * Template Name: Туры
 *
 * Шаблон страницы для каталога экскурсионных туров с фильтрами и поиском
 */

get_header();

// Получаем таксономии
$region_terms = get_terms([
  'taxonomy' => 'region',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
]);

$resort_terms = get_terms([
  'taxonomy' => 'resort',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
]);

$tour_type_terms = get_terms([
  'taxonomy' => 'tour_type',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
]);

// Получаем полный список стран с турами в стабильном RU-порядке.
$countries = function_exists('bsi_get_tour_countries_sorted')
  ? bsi_get_tour_countries_sorted()
  : [];

$paged = 1;

// Получаем номер страницы из различных источников
if (isset($_GET['page'])) {
  $paged = max(1, (int) $_GET['page']);
} elseif (isset($_GET['paged'])) {
  $paged = max(1, (int) $_GET['paged']);
} elseif (get_query_var('paged')) {
  $paged = (int) get_query_var('paged');
}


// Начальный запрос: без schedule JOIN; срок показа в PHP; сортировка по цене по ID.
$per_page_initial = 12;

$candidate_ids = get_posts([
  'post_type'              => 'tour',
  'post_status'            => 'publish',
  'posts_per_page'         => -1,
  'fields'                 => 'ids',
  'no_found_rows'          => true,
  'bsi_skip_schedule'      => true,
  'update_post_meta_cache' => false,
  'update_post_term_cache' => false,
]);
$candidate_ids = array_values(array_unique(array_filter(array_map('intval', $candidate_ids))));

$active_tour_ids = function_exists('bsi_schedule_filter_post__in_ids')
  ? bsi_schedule_filter_post__in_ids($candidate_ids)
  : $candidate_ids;

if ($active_tour_ids !== []) {
  usort($active_tour_ids, function ($a, $b) {
    $price_a = function_exists('bsi_get_tour_sort_price') ? bsi_get_tour_sort_price((int) $a) : null;
    $price_b = function_exists('bsi_get_tour_sort_price') ? bsi_get_tour_sort_price((int) $b) : null;

    if (function_exists('bsi_compare_price_values')) {
      return bsi_compare_price_values($price_a, $price_b, 'price_asc');
    }

    return ((int) $price_a) <=> ((int) $price_b);
  });

  $total_initial = count($active_tour_ids);
  $max_pages_initial = $total_initial > 0 ? (int) ceil($total_initial / $per_page_initial) : 0;
  if ($max_pages_initial > 0) {
    $paged = min(max(1, $paged), $max_pages_initial);
  }

  $offset_initial = ($paged - 1) * $per_page_initial;
  $paginated_ids_initial = array_values(array_slice($active_tour_ids, $offset_initial, $per_page_initial));

  $initial_query = new WP_Query([
    'post_type'              => 'tour',
    'post_status'            => 'publish',
    'posts_per_page'         => $paginated_ids_initial !== [] ? count($paginated_ids_initial) : 1,
    'post__in'               => $paginated_ids_initial !== [] ? $paginated_ids_initial : [0],
    'orderby'                => 'post__in',
    'no_found_rows'          => true,
    'bsi_skip_schedule'      => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);
  $initial_query->found_posts = $total_initial;
  $initial_query->max_num_pages = $max_pages_initial;
} else {
  $initial_query = new WP_Query([
    'post_type'              => 'tour',
    'post_status'            => 'publish',
    'posts_per_page'         => 1,
    'post__in'               => [0],
    'no_found_rows'          => true,
    'bsi_skip_schedule'      => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);
  $initial_query->found_posts = 0;
  $initial_query->max_num_pages = 0;
}
?>

<?php if (function_exists('yoast_breadcrumb')): ?>
  <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
<?php endif; ?>

<section class="tours-page js-tours-page"
         data-total-pages="<?php echo esc_attr($initial_query->max_num_pages); ?>"
         data-current-page="1">
  <div class="container">
    <div class="title-wrap">
      <div class="">
        <h1 class="h1"><?php the_title(); ?></h1>
        <?php if (has_excerpt()): ?>
          <div class="tours-page__title-description">
            <?php the_excerpt(); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (have_posts()): ?>
      <?php while (have_posts()):
        the_post(); ?>
        <?php if (get_the_content()): ?>
          <div class="page-content">
            <?php the_content(); ?>
          </div>
        <?php endif; ?>
      <?php endwhile; ?>
    <?php endif; ?>

    <form class="tours-filter js-tours-filter"
          data-tours-form>
      <div class="tours-filter__row">
        <div class="tours-filter__field">
          <div class="tours-filter__label">Страна</div>
          <select class="tours-filter__select"
                  name="country"
                  data-choice="single">
            <option value="">Все страны</option>
            <?php if (!empty($countries)): ?>
              <?php foreach ($countries as $country): ?>
                <option value="<?php echo esc_attr($country->ID); ?>"><?php echo esc_html($country->post_title); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="tours-filter__field">
          <div class="tours-filter__label">Регион</div>
          <select class="tours-filter__select"
                  name="region"
                  data-choice="single">
            <option value="">Все регионы</option>
            <?php if (!is_wp_error($region_terms) && !empty($region_terms)): ?>
              <?php foreach ($region_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="tours-filter__field">
          <div class="tours-filter__label">Курорт</div>
          <select class="tours-filter__select"
                  name="resort"
                  data-choice="single">
            <option value="">Все курорты</option>
            <?php if (!is_wp_error($resort_terms) && !empty($resort_terms)): ?>
              <?php foreach ($resort_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="tours-filter__field">
          <div class="tours-filter__label">Тип тура</div>
          <select class="tours-filter__select"
                  name="tour_type"
                  data-choice="single">
            <option value="">Все типы</option>
            <?php if (!is_wp_error($tour_type_terms) && !empty($tour_type_terms)): ?>
              <?php foreach ($tour_type_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="tours-filter__field">
          <div class="tours-filter__label">Поиск</div>
          <input type="text"
                 class="tours-filter__input"
                 name="search"
                 placeholder="Поиск по названию или маршруту">
        </div>

        <div class="tours-filter__field">
          <div class="tours-filter__label">Диапазон цены</div>
          <div class="tours-filter__price-range">
            <input type="number"
                   class="tours-filter__input"
                   name="price_min"
                   placeholder="От"
                   min="0">
            <span class="tours-filter__range-separator">—</span>
            <input type="number"
                   class="tours-filter__input"
                   name="price_max"
                   placeholder="До"
                   min="0">
          </div>
        </div>

        <div class="tours-filter__field">
          <div class="tours-filter__label">Даты заезда</div>
          <input type="text"
                 class="tours-filter__input tours-filter__datepicker"
                 name="date_range"
                 placeholder="Выберите даты"
                 readonly>
          <input type="hidden"
                 name="date_from"
                 value="">
          <input type="hidden"
                 name="date_to"
                 value="">
        </div>
      </div>
    </form>

    <div class="tours-page__controls">
      <div class="tours-page__counter-wrap">
        <div class="tours-page__counter js-tours-counter">
          Найдено: <?php echo (int) $initial_query->found_posts; ?>
        </div>

        <button type="button"
                class="tours-page__reset-btn js-tours-reset"
                style="display: none;">
          Сбросить фильтры
        </button>
      </div>

      <div class="tours-page__controls-right">

        <!-- <div class="tours-page__per-page js-dropdown">
          <button type="button"
                  class="js-dropdown-trigger tours-page__per-page-trigger">
            <span class="tours-page__per-page-text">Показать: 12</span>
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="20"
                 height="20"
                 viewBox="0 0 20 20"
                 fill="none">
              <path d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                    stroke="black"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
          </button>
          <div class="js-dropdown-panel tours-page__per-page-panel">
            <div class="tours-page__per-page-options">
              <button type="button"
                      class="tours-page__per-page-option"
                      data-value="12">12</button>
              <button type="button"
                      class="tours-page__per-page-option"
                      data-value="24">24</button>
              <button type="button"
                      class="tours-page__per-page-option"
                      data-value="48">48</button>
            </div>
          </div>
        </div> -->

        <div class="tours-page__sort js-dropdown">
          <button type="button"
                  class="js-dropdown-trigger tours-page__sort-trigger">
            <span class="tours-page__sort-text">По цене (возрастание)</span>
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="20"
                 height="20"
                 viewBox="0 0 20 20"
                 fill="none">
              <path d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                    stroke="black"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
          </button>
          <div class="js-dropdown-panel tours-page__sort-panel">
            <div class="tours-page__sort-options">
              <button type="button"
                      class="tours-page__sort-option"
                      data-value="title_asc">По названию (А-Я)</button>
              <button type="button"
                      class="tours-page__sort-option"
                      data-value="title_desc">По названию (Я-А)</button>
              <button type="button"
                      class="tours-page__sort-option"
                      data-value="price_asc">По цене (возрастание)</button>
              <button type="button"
                      class="tours-page__sort-option"
                      data-value="price_desc">По цене (убывание)</button>
            </div>
          </div>
        </div>

        <div class="tours-page__view-toggle js-tours-view-toggle">
          <button type="button"
                  class="tours-page__view-btn is-active"
                  data-view="grid"
                  title="Плитки">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="20"
                 height="20"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round">
              <rect x="3"
                    y="3"
                    width="7"
                    height="7"></rect>
              <rect x="14"
                    y="3"
                    width="7"
                    height="7"></rect>
              <rect x="14"
                    y="14"
                    width="7"
                    height="7"></rect>
              <rect x="3"
                    y="14"
                    width="7"
                    height="7"></rect>
            </svg>
          </button>
          <button type="button"
                  class="tours-page__view-btn"
                  data-view="list"
                  title="Список">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="20"
                 height="20"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round">
              <line x1="8"
                    y1="6"
                    x2="21"
                    y2="6"></line>
              <line x1="8"
                    y1="12"
                    x2="21"
                    y2="12"></line>
              <line x1="8"
                    y1="18"
                    x2="21"
                    y2="18"></line>
              <circle cx="4"
                      cy="6"
                      r="1"></circle>
              <circle cx="4"
                      cy="12"
                      r="1"></circle>
              <circle cx="4"
                      cy="18"
                      r="1"></circle>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div class="tours-page__list js-tours-list">
      <?php if ($initial_query->have_posts()): ?>
        <?php
        $items = [];
        while ($initial_query->have_posts()):
          $initial_query->the_post();
          $tour_id = (int) get_the_ID();
          $tour_data = function_exists('bsi_get_tour_card_query_var')
            ? bsi_get_tour_card_query_var($tour_id)
            : [];
          if (empty($tour_data)) {
            continue;
          }
          $items[] = $tour_data;
        endwhile;
        wp_reset_postdata();

        foreach ($items as $item):
          ?>
          <div class="tours-page__item">
            <?php
            set_query_var('tour', $item);
            get_template_part('template-parts/tour/card');
            ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="tours-page__empty">
          Туры не найдены.
        </div>
      <?php endif; ?>
    </div>

    <div class="tours-pagination js-tours-pagination">
      <?php if ($initial_query->max_num_pages > 1): ?>
        <?php
        echo paginate_links([
          'total' => $initial_query->max_num_pages,
          'current' => $paged,
          'prev_text' => '&larr; Назад',
          'next_text' => 'Вперед &rarr;',
          'mid_size' => 2,
        ]);
        ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php
get_footer();
