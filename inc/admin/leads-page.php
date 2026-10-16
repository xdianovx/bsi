<?php

/**
 * Админка → «Заявки»: список из журнала bsi_leads (inc/services/leads.php).
 * Только для manage_options (администраторы). Пока без статусов — просмотр, фильтр, сортировка.
 */

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
  require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BSI_Leads_List_Table extends WP_List_Table
{
  public function __construct()
  {
    parent::__construct(['singular' => 'lead', 'plural' => 'leads', 'ajax' => false]);
  }

  public function get_columns()
  {
    return [
      'created_at' => 'Дата',
      'form' => 'Форма',
      'title' => 'Что / страница',
      'name' => 'Имя',
      'phone' => 'Телефон',
      'email' => 'Email',
      'utm' => 'UTM',
    ];
  }

  protected function get_sortable_columns()
  {
    return [
      'created_at' => ['created_at', true],
      'form' => ['form', false],
      'title' => ['title', false],
    ];
  }

  protected function extra_tablenav($which)
  {
    if ($which !== 'top') {
      return;
    }
    $current = isset($_GET['form']) ? sanitize_key(wp_unslash($_GET['form'])) : '';
    echo '<div class="alignleft actions"><select name="form"><option value="">Все формы</option>';
    foreach (bsi_leads_form_labels() as $key => $label) {
      printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($current, $key, false), esc_html($label));
    }
    echo '</select>';
    submit_button('Фильтр', '', 'filter_action', false);
    echo '</div>';
  }

  /** @var array Период из bsi_leads_period(): from / to (Y-m-d H:i:s). */
  public $period = [];

  public function prepare_items()
  {
    global $wpdb;

    $table = bsi_leads_table_name();
    $per_page = 50;
    $paged = max(1, (int) ($_GET['paged'] ?? 1));

    $where = ['1=1'];
    $args = [];

    if ($this->period) {
      $where[] = 'created_at >= %s AND created_at < %s';
      array_push($args, $this->period['from'], $this->period['to']);
    }

    $form = isset($_GET['form']) ? sanitize_key(wp_unslash($_GET['form'])) : '';
    if ($form !== '') {
      $where[] = 'form = %s';
      $args[] = $form;
    }

    $search = isset($_GET['s']) ? trim(sanitize_text_field(wp_unslash($_GET['s']))) : '';
    if ($search !== '') {
      $like = '%' . $wpdb->esc_like($search) . '%';
      $where[] = '(name LIKE %s OR phone LIKE %s OR email LIKE %s OR title LIKE %s)';
      array_push($args, $like, $like, $like, $like);
    }

    $orderby_allowed = ['created_at', 'form', 'title'];
    $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], $orderby_allowed, true) ? $_GET['orderby'] : 'created_at';
    $order = isset($_GET['order']) && strtolower((string) $_GET['order']) === 'asc' ? 'ASC' : 'DESC';

    $where_sql = implode(' AND ', $where);
    $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
    $total = (int) ($args ? $wpdb->get_var($wpdb->prepare($count_sql, $args)) : $wpdb->get_var($count_sql));

    $list_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}, id DESC LIMIT %d OFFSET %d";
    $this->items = $wpdb->get_results($wpdb->prepare($list_sql, array_merge($args, [$per_page, ($paged - 1) * $per_page])), ARRAY_A);

    $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    $this->set_pagination_args(['total_items' => $total, 'per_page' => $per_page]);
  }

  public function no_items()
  {
    echo 'За этот период заявок нет.';
  }

  protected function column_default($item, $column_name)
  {
    $value = $item[$column_name] ?? '';
    if ($value === null || $value === '') {
      return $item['anonymized_at'] && in_array($column_name, ['name', 'phone', 'email'], true) ? '<em>обезличено</em>' : '—';
    }
    return esc_html($value);
  }

  protected function column_created_at($item)
  {
    $view_url = add_query_arg(['page' => 'bsi-leads', 'lead' => (int) $item['id']], admin_url('admin.php'));
    return sprintf(
      '<a href="%s"><strong>%s</strong></a><div class="row-actions"><a href="%s">Подробнее</a></div>',
      esc_url($view_url),
      esc_html(mysql2date('d.m.Y H:i', $item['created_at'])),
      esc_url($view_url)
    );
  }

  protected function column_form($item)
  {
    $labels = bsi_leads_form_labels();
    return esc_html($labels[$item['form']] ?? $item['form']);
  }

  protected function column_title($item)
  {
    $title = $item['title'] !== '' ? $item['title'] : $item['page_url'];
    if ($item['page_url'] === '') {
      return esc_html($title ?: '—');
    }
    return sprintf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($item['page_url']), esc_html($title));
  }

  protected function column_utm($item)
  {
    $parts = array_filter([$item['utm_source'], $item['utm_medium'], $item['utm_campaign']]);
    return $parts ? esc_html(implode(' / ', $parts)) : '—';
  }
}

add_action('admin_menu', function () {
  add_menu_page('Заявки', 'Заявки', 'manage_options', 'bsi-leads', 'bsi_leads_render_page', 'dashicons-email-alt', 3);
});

function bsi_leads_render_page(): void
{
  if (!current_user_can('manage_options')) {
    wp_die('Недостаточно прав.');
  }

  $lead_id = isset($_GET['lead']) ? (int) $_GET['lead'] : 0;
  if ($lead_id) {
    bsi_leads_render_single($lead_id);
    return;
  }

  $period = bsi_leads_period();

  $table = new BSI_Leads_List_Table();
  $table->period = $period;
  $table->prepare_items();
  ?>
  <div class="wrap bsi-leads">
    <h1 class="wp-heading-inline">Заявки <span class="bsi-leads__loader js-bsi-leads-loader"></span></h1>
    <div class="bsi-leads__body js-bsi-leads-body">
    <?php bsi_leads_render_dashboard($period); ?>
    <h2 class="bsi-leads__list-title">Список заявок</h2>
    <form method="get">
      <input type="hidden" name="page" value="bsi-leads">
      <input type="hidden" name="range" value="<?php echo esc_attr($period['range']); ?>">
      <?php if ($period['range'] === 'custom'): ?>
        <input type="hidden" name="from" value="<?php echo esc_attr($period['from_input']); ?>">
        <input type="hidden" name="to" value="<?php echo esc_attr($period['to_input']); ?>">
      <?php endif; ?>
      <?php $table->search_box('Найти', 'bsi-leads'); ?>
      <?php $table->display(); ?>
    </form>
    </div>
  </div>
  <?php
}

function bsi_leads_render_single(int $lead_id): void
{
  global $wpdb;

  $lead = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . bsi_leads_table_name() . ' WHERE id = %d', $lead_id), ARRAY_A);
  $back = admin_url('admin.php?page=bsi-leads');
  ?>
  <div class="wrap">
    <h1>Заявка #<?php echo (int) $lead_id; ?></h1>
    <p><a href="<?php echo esc_url($back); ?>">← Все заявки</a></p>
    <?php if (!$lead): ?>
      <p>Заявка не найдена.</p>
    <?php else:
      $labels = bsi_leads_form_labels();
      $rows = [
        'Дата' => mysql2date('d.m.Y H:i', $lead['created_at']),
        'Форма' => $labels[$lead['form']] ?? $lead['form'],
        'Что' => $lead['title'],
        'Страница' => $lead['page_url'],
        'Раздел' => $lead['section'],
        'Имя' => $lead['name'],
        'Телефон' => $lead['phone'],
        'Email' => $lead['email'],
        'UTM' => implode(' / ', array_filter([$lead['utm_source'], $lead['utm_medium'], $lead['utm_campaign']])),
      ];
      $payload = $lead['payload'] ? (array) json_decode($lead['payload'], true) : [];
      foreach ($payload as $key => $value) {
        $rows[$key] = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
      }
      ?>
      <table class="widefat striped" style="max-width:900px">
        <tbody>
          <?php foreach ($rows as $label => $value): ?>
            <tr>
              <th style="width:220px"><?php echo esc_html($label); ?></th>
              <td><?php echo $value !== '' && $value !== null ? nl2br(esc_html($value)) : '—'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <?php
}

/**
 * Период дашборда из GET: range = 7 | 30 | 90 | 365 | all | custom (+ from, to в Y-m-d).
 * to — граница «до» (не включительно), prev_* — такой же период перед текущим.
 */
function bsi_leads_period(): array
{
  $presets = ['7' => '7 дней', '30' => '30 дней', '90' => '90 дней', '365' => 'Год', 'all' => 'Всё время'];
  $range = isset($_GET['range']) ? sanitize_key(wp_unslash($_GET['range'])) : '30';

  $today = current_datetime()->setTime(0, 0);
  $tomorrow = $today->modify('+1 day');

  if ($range === 'custom') {
    $from_in = isset($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : '';
    $to_in = isset($_GET['to']) ? sanitize_text_field(wp_unslash($_GET['to'])) : '';
    $from = DateTimeImmutable::createFromFormat('!Y-m-d', $from_in, wp_timezone());
    $to = DateTimeImmutable::createFromFormat('!Y-m-d', $to_in, wp_timezone());
    if (!$from || !$to) {
      $range = '30';
    } else {
      if ($from > $to) {
        [$from, $to] = [$to, $from];
      }
      $start = $from;
      $end = $to->modify('+1 day');
    }
  }

  if ($range === 'all') {
    global $wpdb;
    $first = $wpdb->get_var('SELECT MIN(created_at) FROM ' . bsi_leads_table_name());
    $start = $first ? (new DateTimeImmutable($first, wp_timezone()))->setTime(0, 0) : $today;
    $end = $tomorrow;
  } elseif ($range !== 'custom') {
    if (!isset($presets[$range])) {
      $range = '30';
    }
    $start = $tomorrow->modify('-' . (int) $range . ' days');
    $end = $tomorrow;
  }

  $days = max(1, (int) round(($end->getTimestamp() - $start->getTimestamp()) / DAY_IN_SECONDS));
  $prev_start = $start->modify('-' . $days . ' days');

  return [
    'range' => $range,
    'presets' => $presets,
    'start' => $start,
    'end' => $end,
    'from' => $start->format('Y-m-d H:i:s'),
    'to' => $end->format('Y-m-d H:i:s'),
    'prev_from' => $prev_start->format('Y-m-d H:i:s'),
    'prev_to' => $start->format('Y-m-d H:i:s'),
    'days' => $days,
    'from_input' => $start->format('Y-m-d'),
    'to_input' => $end->modify('-1 day')->format('Y-m-d'),
  ];
}

/** Ссылка на эту же страницу с периодом и доп. параметрами. */
function bsi_leads_url(array $period, array $extra = []): string
{
  $args = ['page' => 'bsi-leads', 'range' => $period['range']];
  if ($period['range'] === 'custom') {
    $args['from'] = $period['from_input'];
    $args['to'] = $period['to_input'];
  }
  return add_query_arg(array_merge($args, $extra), admin_url('admin.php'));
}

function bsi_leads_render_dashboard(array $period): void
{
  global $wpdb;

  $t = bsi_leads_table_name();
  $in = $wpdb->prepare('created_at >= %s AND created_at < %s', $period['from'], $period['to']);
  $labels = bsi_leads_form_labels();

  $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE {$in}");
  $prev = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE created_at >= %s AND created_at < %s", $period['prev_from'], $period['prev_to']));
  $with_utm = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE {$in} AND utm_source <> ''");

  $by_form = $wpdb->get_results("SELECT form, COUNT(*) c FROM {$t} WHERE {$in} GROUP BY form ORDER BY c DESC", ARRAY_A);
  $top_pages = $wpdb->get_results("SELECT title, SUBSTRING_INDEX(page_url, '?', 1) url, COUNT(*) c FROM {$t} WHERE {$in} AND page_url <> '' GROUP BY title, url ORDER BY c DESC LIMIT 7", ARRAY_A);
  $sources = $wpdb->get_results("SELECT IF(utm_source = '', 'без метки', utm_source) src, COUNT(*) c FROM {$t} WHERE {$in} GROUP BY src ORDER BY c DESC LIMIT 7", ARRAY_A);

  // График: по дням до 92 дней, дальше — по месяцам.
  $by_month = $period['days'] > 92;
  $fmt = $by_month ? '%Y-%m' : '%Y-%m-%d';
  $rows = $wpdb->get_results("SELECT DATE_FORMAT(created_at, '{$fmt}') b, COUNT(*) c FROM {$t} WHERE {$in} GROUP BY b", ARRAY_A);
  $counts = array_column($rows, 'c', 'b');
  $buckets = [];
  $cursor = $by_month ? $period['start']->modify('first day of this month') : $period['start'];
  while ($cursor < $period['end']) {
    $key = $cursor->format($by_month ? 'Y-m' : 'Y-m-d');
    $buckets[$key] = (int) ($counts[$key] ?? 0);
    $cursor = $cursor->modify($by_month ? '+1 month' : '+1 day');
  }

  $delta = $prev > 0 ? (int) round(($total - $prev) / $prev * 100) : null;
  $top_form = $by_form[0] ?? null;
  ?>
  <div class="bsi-leads__periods">
    <?php foreach ($period['presets'] as $key => $label): ?>
      <a class="bsi-leads__period<?php echo $period['range'] === (string) $key ? ' is-active' : ''; ?>"
        href="<?php echo esc_url(add_query_arg(['page' => 'bsi-leads', 'range' => $key], admin_url('admin.php'))); ?>"><?php echo esc_html($label); ?></a>
    <?php endforeach; ?>
    <form method="get" class="bsi-leads__custom<?php echo $period['range'] === 'custom' ? ' is-active' : ''; ?>">
      <input type="hidden" name="page" value="bsi-leads">
      <input type="hidden" name="range" value="custom">
      <input type="date" name="from" value="<?php echo esc_attr($period['from_input']); ?>">
      <span>—</span>
      <input type="date" name="to" value="<?php echo esc_attr($period['to_input']); ?>">
      <button type="submit" class="button">Показать</button>
    </form>
  </div>

  <div class="bsi-leads__kpis">
    <div class="bsi-leads__kpi">
      <div class="bsi-leads__kpi-label">Заявок</div>
      <div class="bsi-leads__kpi-value"><?php echo esc_html(number_format_i18n($total)); ?></div>
      <div class="bsi-leads__kpi-note">
        <?php if ($period['range'] === 'all'): ?>
          за всё время
        <?php elseif ($delta === null): ?>
          в прошлом периоде заявок не было
        <?php else: ?>
          <span class="<?php echo $delta >= 0 ? 'is-up' : 'is-down'; ?>"><?php echo $delta >= 0 ? '↑' : '↓'; ?> <?php echo esc_html(abs($delta)); ?>%</span>
          к прошлым <?php echo esc_html($period['days']); ?> дн. (<?php echo esc_html($prev); ?>)
        <?php endif; ?>
      </div>
    </div>
    <div class="bsi-leads__kpi">
      <div class="bsi-leads__kpi-label">В среднем в день</div>
      <div class="bsi-leads__kpi-value"><?php echo esc_html(number_format_i18n($total / $period['days'], 1)); ?></div>
      <div class="bsi-leads__kpi-note">за <?php echo esc_html($period['days']); ?> дн.</div>
    </div>
    <div class="bsi-leads__kpi">
      <div class="bsi-leads__kpi-label">С рекламной меткой</div>
      <div class="bsi-leads__kpi-value"><?php echo $total ? esc_html(round($with_utm / $total * 100)) . '%' : '—'; ?></div>
      <div class="bsi-leads__kpi-note"><?php echo esc_html($with_utm); ?> с UTM</div>
    </div>
    <div class="bsi-leads__kpi">
      <div class="bsi-leads__kpi-label">Чаще всего</div>
      <div class="bsi-leads__kpi-value bsi-leads__kpi-value--text"><?php echo $top_form ? esc_html($labels[$top_form['form']] ?? $top_form['form']) : '—'; ?></div>
      <div class="bsi-leads__kpi-note"><?php echo $top_form ? esc_html($top_form['c']) . ' заявок' : ''; ?></div>
    </div>
  </div>

  <div class="bsi-leads__panel">
    <div class="bsi-leads__panel-title">Заявки <?php echo $by_month ? 'по месяцам' : 'по дням'; ?></div>
    <?php bsi_leads_render_chart($buckets, $by_month); ?>
  </div>

  <div class="bsi-leads__grid">
    <?php
    $form_rows = array_map(static function ($r) use ($labels, $period) {
      return ['label' => $labels[$r['form']] ?? $r['form'], 'c' => (int) $r['c'], 'url' => bsi_leads_url($period, ['form' => $r['form']])];
    }, $by_form);
    $page_rows = array_map(static function ($r) {
      return ['label' => $r['title'] !== '' ? $r['title'] : $r['url'], 'c' => (int) $r['c'], 'url' => $r['url'], 'external' => true];
    }, $top_pages);
    $source_rows = array_map(static function ($r) {
      return ['label' => $r['src'], 'c' => (int) $r['c']];
    }, $sources);
    bsi_leads_render_bars('По формам', $form_rows);
    bsi_leads_render_bars('Страницы с заявками', $page_rows);
    bsi_leads_render_bars('Источники (utm_source)', $source_rows);
    ?>
  </div>
  <?php
}

function bsi_leads_render_bars(string $title, array $rows): void
{
  $max = $rows ? max(array_column($rows, 'c')) : 0;
  ?>
  <div class="bsi-leads__panel">
    <div class="bsi-leads__panel-title"><?php echo esc_html($title); ?></div>
    <?php if (!$rows): ?>
      <p class="bsi-leads__empty">Нет данных за период.</p>
    <?php else: ?>
      <ul class="bsi-leads__bars">
        <?php foreach ($rows as $row):
          $width = $max ? max(2, round($row['c'] / $max * 100)) : 0; ?>
          <li class="bsi-leads__bar">
            <span class="bsi-leads__bar-label">
              <?php if (!empty($row['url'])): ?>
                <a href="<?php echo esc_url($row['url']); ?>"<?php echo !empty($row['external']) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($row['label']); ?></a>
              <?php else: ?>
                <?php echo esc_html($row['label']); ?>
              <?php endif; ?>
            </span>
            <span class="bsi-leads__bar-track"><span class="bsi-leads__bar-fill" style="width:<?php echo (int) $width; ?>%"></span></span>
            <span class="bsi-leads__bar-value"><?php echo esc_html($row['c']); ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <?php
}

/** Столбчатый график на SVG: без библиотек, подсказка с датой и числом — в <title>. */
function bsi_leads_render_chart(array $buckets, bool $by_month): void
{
  $n = count($buckets);
  if (!$n) {
    return;
  }
  $max = max(1, max($buckets));
  $w = 1000;
  $h = 180;
  $slot = $w / $n;
  $bar = max(1, $slot * 0.7);
  $months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
  $label = static function (string $key) use ($by_month, $months): string {
    $parts = explode('-', $key);
    return $by_month
      ? $months[(int) $parts[1] - 1] . ' ' . $parts[0]
      : (int) $parts[2] . ' ' . $months[(int) $parts[1] - 1];
  };
  $keys = array_keys($buckets);
  ?>
  <div class="bsi-leads__chart">
    <div class="bsi-leads__chart-max">макс. <?php echo esc_html($max); ?></div>
    <svg viewBox="0 0 <?php echo $w; ?> <?php echo $h; ?>" preserveAspectRatio="none">
      <?php $i = 0;
      foreach ($buckets as $key => $c):
        $bh = $c ? max(3, $c / $max * ($h - 4)) : 0; ?>
        <g>
          <title><?php echo esc_html($label($key) . ': ' . $c); ?></title>
          <rect x="<?php echo round($i * $slot, 2); ?>" y="0" width="<?php echo round($slot, 2); ?>" height="<?php echo $h; ?>" fill="transparent"></rect>
          <rect class="bsi-leads__chart-bar" x="<?php echo round($i * $slot + ($slot - $bar) / 2, 2); ?>" y="<?php echo round($h - $bh, 2); ?>" width="<?php echo round($bar, 2); ?>" height="<?php echo round($bh, 2); ?>" rx="2"></rect>
        </g>
      <?php $i++;
      endforeach; ?>
    </svg>
    <div class="bsi-leads__chart-axis">
      <span><?php echo esc_html($label($keys[0])); ?></span>
      <?php if ($n > 2): ?><span><?php echo esc_html($label($keys[(int) floor($n / 2)])); ?></span><?php endif; ?>
      <span><?php echo esc_html($label($keys[$n - 1])); ?></span>
    </div>
  </div>
  <?php
}

add_action('admin_head', function () {
  if (($_GET['page'] ?? '') !== 'bsi-leads') {
    return;
  }
  ?>
  <style>
    .bsi-leads__periods{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:16px 0 20px}
    .bsi-leads__period{padding:6px 14px;border-radius:6px;background:#fff;color:#1d2327;text-decoration:none;font-size:13px;line-height:140%}
    .bsi-leads__period:hover{color:#2271b1}
    .bsi-leads__period.is-active{background:#2271b1;color:#fff}
    .bsi-leads__custom{display:flex;align-items:center;gap:6px;margin-left:8px;padding:2px 6px;border-radius:6px}
    .bsi-leads__custom.is-active{background:#fff}
    .bsi-leads__custom input[type=date]{min-height:30px}
    .bsi-leads__custom .button{min-height:30px;line-height:140%}
    .bsi-leads__kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
    .bsi-leads__kpi,.bsi-leads__panel{background:#fff;border-radius:8px;padding:16px 20px}
    .bsi-leads__kpi-label,.bsi-leads__panel-title{font-size:13px;color:#50575e;line-height:140%}
    .bsi-leads__panel-title{font-weight:600;color:#1d2327;margin-bottom:12px}
    .bsi-leads__kpi-value{font-size:32px;font-weight:600;line-height:120%;margin:6px 0 4px;color:#1d2327}
    .bsi-leads__kpi-value--text{font-size:22px;line-height:140%}
    .bsi-leads__kpi-note{font-size:12px;color:#646970;line-height:140%}
    .bsi-leads__kpi-note .is-up{color:#008a20;font-weight:600}
    .bsi-leads__kpi-note .is-down{color:#b32d2e;font-weight:600}
    .bsi-leads__chart{position:relative}
    .bsi-leads__chart svg{display:block;width:100%;height:180px}
    .bsi-leads__chart-bar{fill:#2271b1}
    .bsi-leads__chart g:hover .bsi-leads__chart-bar{fill:#135e96}
    .bsi-leads__chart-max{position:absolute;top:-28px;right:0;font-size:12px;color:#646970}
    .bsi-leads__chart-axis{display:flex;justify-content:space-between;font-size:12px;color:#646970;margin-top:6px}
    .bsi-leads__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:16px}
    .bsi-leads__bars{margin:0;padding:0;list-style:none}
    .bsi-leads__bar{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr) 32px;align-items:center;gap:10px;margin:0 0 8px;font-size:13px;line-height:140%}
    .bsi-leads__bar-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .bsi-leads__bar-label a{text-decoration:none}
    .bsi-leads__bar-track{height:8px;background:#f0f0f1;border-radius:4px;overflow:hidden}
    .bsi-leads__bar-fill{display:block;height:100%;background:#2271b1;border-radius:4px}
    .bsi-leads__bar-value{text-align:right;font-weight:600;color:#1d2327}
    .bsi-leads__empty{font-size:12px;color:#646970;margin:8px 0 0}
    .bsi-leads__list-title{margin:28px 0 0}
    .bsi-leads__loader{display:inline-block;width:18px;height:18px;margin-left:10px;vertical-align:-2px;border:2px solid #c3c4c7;border-top-color:#2271b1;border-radius:50%;opacity:0;transition:opacity .15s}
    .bsi-leads.is-loading .bsi-leads__loader{opacity:1;animation:bsi-leads-spin .7s linear infinite}
    .bsi-leads__body{transition:opacity .15s}
    .bsi-leads.is-loading .bsi-leads__body{opacity:.45;pointer-events:none}
    @keyframes bsi-leads-spin{to{transform:rotate(360deg)}}
    @media (prefers-reduced-motion:reduce){.bsi-leads.is-loading .bsi-leads__loader{animation-duration:2s}}
    @media (max-width:1100px){.bsi-leads__kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.bsi-leads__grid{grid-template-columns:1fr}}
    @media (max-width:600px){.bsi-leads__kpis{grid-template-columns:1fr}.bsi-leads__custom{margin-left:0;flex-wrap:wrap;width:100%;padding:0}.bsi-leads__custom input[type=date]{flex:1 1 0;min-width:0}}
  </style>
  <?php
});

/*
 * Без перезагрузки: период, фильтр, поиск, сортировка и пагинация подгружают эту же
 * страницу через fetch (те же куки админа — отдельного эндпоинта нет) и подменяют
 * .js-bsi-leads-body. Карточка заявки и внешние ссылки открываются обычным переходом.
 */
add_action('admin_footer', function () {
  if (($_GET['page'] ?? '') !== 'bsi-leads' || isset($_GET['lead'])) {
    return;
  }
  ?>
  <script>
    (function () {
      var root = document.querySelector('.bsi-leads');
      if (!root || !window.fetch || !window.DOMParser) return;
      var controller = null;

      function isLeadsUrl(url) {
        return url.origin === location.origin && url.pathname === location.pathname &&
          url.searchParams.get('page') === 'bsi-leads' && !url.searchParams.has('lead');
      }

      function load(href, push) {
        if (controller) controller.abort();
        controller = new AbortController();
        root.classList.add('is-loading');
        fetch(href, { credentials: 'same-origin', signal: controller.signal })
          .then(function (res) {
            if (!res.ok || res.redirected) throw new Error('bad response');
            return res.text();
          })
          .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var fresh = doc.querySelector('.js-bsi-leads-body');
            if (!fresh) throw new Error('no body');
            root.querySelector('.js-bsi-leads-body').replaceWith(fresh);
            if (push) history.pushState({ bsiLeads: true }, '', href);
            root.classList.remove('is-loading');
          })
          .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            location.href = href;
          });
      }

      root.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (!a || a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        var url = new URL(a.href, location.href);
        if (!isLeadsUrl(url)) return;
        e.preventDefault();
        load(url.href, true);
      });

      root.addEventListener('submit', function (e) {
        var form = e.target;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;
        e.preventDefault();
        var url = new URL(form.getAttribute('action') || location.pathname, location.href);
        var data = new FormData(form);
        if (e.submitter && e.submitter.name) data.append(e.submitter.name, e.submitter.value);
        url.search = '';
        data.forEach(function (value, key) {
          if (value !== '' && ['paged', 'filter_action', '_wpnonce', '_wp_http_referer'].indexOf(key) === -1) url.searchParams.append(key, value);
        });
        load(url.href, true);
      });

      window.addEventListener('popstate', function () {
        load(location.href, false);
      });
    })();
  </script>
  <?php
});
