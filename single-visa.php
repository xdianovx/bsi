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

        <?php /* Секция информации о визе */ ?>
        <?php
        $processing_time = get_field('visa_processing_time');
        $validity_period = get_field('visa_validity_period');
        $consular_fee = get_field('visa_consular_fee');
        $support_fee = get_field('visa_support_fee');

        if ($processing_time || $validity_period || $consular_fee || $support_fee):
          ?>
          <div class="visa-page__info-section">
            <div class="visa-info-item__wrap">
              <?php if ($processing_time): ?>
                <div class="visa-info-item">
                  <div class="visa-info-item__title">
                    <p class="visa-info-item__key">Срок оформления</p>
                  </div>
                  <p class="visa-info-item__value"><?= esc_html($processing_time); ?></p>
                </div>
              <?php endif; ?>

              <?php if ($validity_period): ?>
                <div class="visa-info-item">
                  <div class="visa-info-item__title">
                    <p class="visa-info-item__key">Срок действия</p>
                  </div>
                  <p class="visa-info-item__value"><?= esc_html($validity_period); ?></p>
                </div>
              <?php endif; ?>

              <?php if ($consular_fee): ?>
                <div class="visa-info-item">
                  <div class="visa-info-item__title">
                    <p class="visa-info-item__key">Консульский и сервисный сборы</p>
                  </div>
                  <p class="visa-info-item__value"><?= esc_html($consular_fee); ?></p>
                </div>
              <?php endif; ?>

              <?php if ($support_fee): ?>
                <div class="visa-info-item">
                  <div class="visa-info-item__title">
                    <p class="visa-info-item__key">Визовая поддержка и запись на подачу документов</p>
                  </div>
                  <p class="visa-info-item__value"><?= esc_html($support_fee); ?></p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php /* Секция контактов посольства */ ?>
        <?php
        $embassy_phone = get_field('visa_embassy_phone');
        $embassy_address = get_field('visa_embassy_address');
        $embassy_website = get_field('visa_embassy_website');

        if ($embassy_phone || $embassy_address || $embassy_website):
          ?>
          <div class="visa-embassy__section">
            <h2 class="visa-embassy__title">Адрес посольства</h2>

            <div class="visa-embassy__contacts">
              <?php if ($embassy_phone): ?>
                <?php
                $phone_tel = preg_replace('/[^0-9\+]/', '', $embassy_phone);
                ?>
                <a href="<?= esc_url('tel:' . $phone_tel); ?>" class="visa-embassy__contact-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-phone-call-icon lucide-phone-call">
                    <path d="M13 2a9 9 0 0 1 9 9" />
                    <path d="M13 6a5 5 0 0 1 5 5" />
                    <path
                      d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />
                  </svg>
                  <span><?= esc_html($embassy_phone); ?></span>
                </a>
              <?php endif; ?>

              <?php if ($embassy_address): ?>
                <div class="visa-embassy__contact-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-map-pin-house-icon lucide-map-pin-house">
                    <path
                      d="M15 22a1 1 0 0 1-1-1v-4a1 1 0 0 1 .445-.832l3-2a1 1 0 0 1 1.11 0l3 2A1 1 0 0 1 22 17v4a1 1 0 0 1-1 1z" />
                    <path d="M18 10a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 .601.2" />
                    <path d="M18 22v-3" />
                    <circle cx="10" cy="10" r="3" />
                  </svg>
                  <span><?= esc_html($embassy_address); ?></span>
                </div>
              <?php endif; ?>

              <?php if ($embassy_website): ?>
                <div class="visa-embassy__contact-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-globe-icon lucide-globe">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                    <path d="M2 12h20" />
                  </svg>
                  <a href="<?= esc_url($embassy_website); ?>" target="_blank" rel="noopener noreferrer">Сайт посольства</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>



        <?php /* Контент из редактора */ ?>
        <div class="editor-content page-country__editor-content">
          <?php the_content(); ?>
        </div>

        <?php
        $callout_text = get_field('visa_callout_text');
        if ($callout_text):
          ?>
          <div class="callout callout-neutral">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-check-circle-icon lucide-check-circle">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            <p>
              <?= esc_html($callout_text); ?>
            </p>
          </div>
        <?php endif; ?>

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