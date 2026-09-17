<?php

/**
 * Текстовые секции отеля: описание, расположение, трансфер и т.п.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$sections = $view['sections'] ?? [];

if (!$sections) {
  return;
}
?>

<?php foreach ($sections as $section): ?>
  <section class="hp-section" id="<?= esc_attr($section['id']); ?>">
    <div class="container">
      <h2 class="h2 hp-section__title"><?= esc_html($section['title']); ?></h2>
      <div class="editor-content hp-section__body"><?= $section['html']; ?></div>
    </div>
  </section>
<?php endforeach; ?>
