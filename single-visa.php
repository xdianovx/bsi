<?php
get_header();
?>
<?php if (function_exists('yoast_breadcrumb')) {
  yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
} ?>
<section>
  <div class="container">
    <div class="coutry-page__wrap">

      <?php /* Aside меню страны */ ?>
      <aside class="coutry-page__aside">
        <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
      </aside>

      <?php ?>
      <div class="page-country__content">
        <?php /* Заголовок + краткое описание */ ?>

        <div class="page-country__title">
          <h1 class="h1"><?php the_title(); ?></h1>

        </div>

        <div class="visa-page__poster">
          <img src="<?= get_the_post_thumbnail_url() ?>" alt="">
        </div>

        <?php /* Контент из редактора */ ?>
        <div class="editor-content page-country__editor-content">
          <?php the_content(); ?>
        </div>

        <?php /* Секция файлов */ ?>
        <?php
        $visa_files = get_field('visa_files');
        if ($visa_files && is_array($visa_files) && count($visa_files) > 0):
          /**
           * Функция для определения расширения файла
           */
          function get_file_extension($file)
          {
            if (!is_array($file)) {
              return '';
            }

            // Пробуем получить расширение из разных источников
            $ext = '';

            // Из filename
            if (!empty($file['filename'])) {
              $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
            }

            // Если не получилось, пробуем из URL
            if (empty($ext) && !empty($file['url'])) {
              $ext = strtolower(pathinfo($file['url'], PATHINFO_EXTENSION));
            }

            // Если не получилось, пробуем из mime_type
            if (empty($ext) && !empty($file['mime_type'])) {
              $mime_parts = explode('/', $file['mime_type']);
              if (!empty($mime_parts[1])) {
                // Маппинг некоторых MIME типов к расширениям
                $mime_to_ext = [
                  'pdf' => 'pdf',
                  'msword' => 'doc',
                  'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                  'vnd.ms-excel' => 'xls',
                  'vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                  'vnd.ms-powerpoint' => 'ppt',
                  'vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                  'zip' => 'zip',
                  'rar' => 'rar',
                ];

                if (isset($mime_to_ext[$mime_parts[1]])) {
                  $ext = $mime_to_ext[$mime_parts[1]];
                } else {
                  $ext = $mime_parts[1];
                }
              }
            }

            return $ext ? strtoupper($ext) : 'FILE';
          }
          ?>
          <div class="visa-files__section">
            <h2 class="h2">Документы</h2>
            <div class="visa-files__list">
              <?php foreach ($visa_files as $file_item): ?>
                <?php
                $file = $file_item['file'] ?? null;
                $file_name = $file_item['name'] ?? '';

                if (!$file || !is_array($file) || empty($file['url'])) {
                  continue;
                }

                $file_url = esc_url($file['url']);
                $file_filename = !empty($file_name) ? esc_html($file_name) : esc_html($file['filename'] ?? basename($file_url));
                $file_ext = get_file_extension($file);
                ?>
                <a href="<?= $file_url; ?>" class="visa-file-item" target="_blank" rel="noopener noreferrer" download>
                  <span class="visa-file-item__icon"><?= esc_html($file_ext); ?></span>
                  <span class="visa-file-item__name"><?= $file_filename; ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>


<?php
get_footer();