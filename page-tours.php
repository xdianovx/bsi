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

// Получаем все туры для определения стран, у которых есть туры
$all_tours = get_posts([
  'post_type' => 'tour',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'fields' => 'ids',
]);

// Собираем уникальные ID стран из туров
$country_ids = [];

if (!empty($all_tours) && function_exists('get_field')) {
  foreach ($all_tours as $tour_id) {
    $c = get_field('tour_country', $tour_id);
    if ($c instanceof WP_Post) {
      $c = (int) $c->ID;
    } elseif (is_array($c)) {
      $c = (int) reset($c);
    } else {
      $c = (int) $c;
    }

    if ($c > 0) {
      $country_ids[] = $c;
    }
  }
}

$country_ids = array_values(array_unique(array_filter($country_ids)));

// Получаем только те страны, у которых есть туры
$countries = [];
if (!empty($country_ids)) {
  $countries = get_posts([
    'post_type' => 'country',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'post_parent' => 0,
    'post__in' => $country_ids,
    'orderby' => 'title',
    'order' => 'ASC',
  ]);
}

$paged = 1;

// Получаем номер страницы из различных источников
if (isset($_GET['page'])) {
  $paged = max(1, (int) $_GET['page']);
} elseif (isset($_GET['paged'])) {
  $paged = max(1, (int) $_GET['paged']);
} elseif (get_query_var('paged')) {
  $paged = (int) get_query_var('paged');
}

// Начальный запрос - показываем все туры с сортировкой по цене (возрастание)
$per_page_initial = 12;
$all_tours_for_sort = new WP_Query([
  'post_type' => 'tour',
  'post_status' => 'publish',
  'posts_per_page' => -1,
]);

if ($all_tours_for_sort->have_posts()) {
  $all_sorted_posts = $all_tours_for_sort->posts;

  usort($all_sorted_posts, function ($a, $b) {
    $price_a = function_exists('get_field') ? get_field('price_from', $a->ID) : '';
    $price_b = function_exists('get_field') ? get_field('price_from', $b->ID) : '';
    preg_match('/[\d\s]+/', (string) $price_a, $matches_a);
    preg_match('/[\d\s]+/', (string) $price_b, $matches_b);
    $num_a = isset($matches_a[0]) ? (int) str_replace(' ', '', $matches_a[0]) : 0;
    $num_b = isset($matches_b[0]) ? (int) str_replace(' ', '', $matches_b[0]) : 0;
    return $num_a <=> $num_b;
  });

  $total_initial = count($all_sorted_posts);
  $max_pages_initial = (int) ceil($total_initial / $per_page_initial);
  $offset_initial = ($paged - 1) * $per_page_initial;
  $paginated_initial = array_slice($all_sorted_posts, $offset_initial, $per_page_initial);
  $paginated_ids_initial = !empty($paginated_initial) ? array_map(fn($p) => $p->ID, $paginated_initial) : [0];

  $initial_query = new WP_Query([
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => $per_page_initial,
    'post__in' => $paginated_ids_initial,
    'orderby' => 'post__in',
  ]);
  $initial_query->found_posts = $total_initial;
  $initial_query->max_num_pages = $max_pages_initial;
} else {
  $initial_query = new WP_Query([
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => $per_page_initial,
    'paged' => $paged,
  ]);
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

          $country_id = 0;
          if (function_exists('get_field')) {
            $country_val = get_field('tour_country', $tour_id);
            if ($country_val instanceof WP_Post) {
              $country_id = (int) $country_val->ID;
            } elseif (is_array($country_val)) {
              $country_id = (int) reset($country_val);
            } else {
              $country_id = (int) $country_val;
            }
          }

          $country_title = $country_id ? (string) get_the_title($country_id) : '';

          $flag_url = '';
          if ($country_id && function_exists('get_field')) {
            $flag_field = get_field('flag', $country_id);
            if ($flag_field) {
              if (is_array($flag_field) && !empty($flag_field['url'])) {
                $flag_url = (string) $flag_field['url'];
              } elseif (is_string($flag_field)) {
                $flag_url = (string) $flag_field;
              }
            }
          }

          $items[] = [
            'id' => $tour_id,
            'url' => get_permalink($tour_id),
            'title' => get_the_title($tour_id),
            'flag' => $flag_url,
            'country_title' => $country_title,
            'country_id' => $country_id,
          ];
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
