<?php

/**
 * Файлы отеля: фактшиты, программы заездов, концепции питания.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$files = $view['files'] ?? [];

if (!$files) {
  return;
}
?>

<section class="hp-section hp-files" id="hotel-files">
  <div class="container">
    <h2 class="h2 hp-section__title">Материалы для скачивания</h2>

    <ul class="hp-files__list">
      <?php foreach ($files as $file): ?>
        <li>
          <a class="hp-file" href="<?= esc_url($file['url']); ?>" target="_blank" rel="noopener" download>
            <span class="hp-file__icon">
              <?= bsi_lucide_icon('file-text', [
                'width' => '20',
                'height' => '20',
                'stroke' => 'currentColor',
              ]); ?>
            </span>
            <span class="hp-file__title"><?= esc_html($file['title']); ?></span>
            <?php if ($file['meta'] !== ''): ?>
              <span class="hp-file__meta numfont"><?= esc_html($file['meta']); ?></span>
            <?php endif; ?>
            <span class="hp-file__download">Скачать</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
