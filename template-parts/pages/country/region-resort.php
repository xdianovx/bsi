<?php
$regions = $args['regions'] ?? [];
?>
<?php if (!empty($regions)): ?>
  <div class="country-regions__list">
    <?php foreach ($regions as $region): ?>

      <?php
      $resorts = get_terms([
        'taxonomy' => 'resort',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'meta_query' => [
          [
            'key' => 'resort_region',
            'value' => $region->term_id,
            'compare' => '=',
          ],
        ],
      ]);

      if (empty($resorts) || is_wp_error($resorts)) {
        $resorts = [];
      }
      ?>

      <div class="country-regions__item">

        <!-- У региона нет страницы, поэтому это НЕ ссылка -->
        <div class="country-regions__link">
          <?= esc_html($region->name); ?>
        </div>

        <?php if (!empty($resorts)): ?>
          <div class="country-regions__resorts">
            <?php foreach ($resorts as $resort): ?>
              <a class="country-regions__resort"
                 href="<?= esc_url(get_term_link($resort)); ?>">
                <?= esc_html($resort->name); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>

    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p>Пока нет регионов и курортов для этой страны.</p>
<?php endif; ?>