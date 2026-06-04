<section class="subscribe-section">
  <div class="container">
    <div class="subscribe-section__wrap">
      <h2 class="h2 subscribe-section__h2">
        Подпишитесь на рассылку
      </h2>

      <p class="subscribe-section__subtitle">
        Получайте первыми самую актуальную информацию о наших планах, новинках, спецпредложениях, акциях и
        мероприятиях.
      </p>



      <div class="subscribe-section-wrap-form">
        <div class="subscribe-section__form_wrap">
          <?php
          $unisender_action = 'https://cp.unisender.com/ru/subscribe?hash=6w9zi9a93r6q6k7gd77osrtx7sehuo9kqhaysuxekseaxnnymabsy';
          $unisender_default_list_id = 20097402;

          ?>
          <form method="POST"
                action="<?= esc_url($unisender_action) ?>"
                class="subscribe-section__form"
                name="subscribtion_form"
                us_mode="embed"
                accept-charset="UTF-8">
            <input type="email"
                   name="email"
                   required
                   class="input"
                   placeholder="Ваш email"
                   autocomplete="email">

            <button class="btn btn-accent"
                    type="submit">Подписаться</button>

            <?php
            if (function_exists('bsi_render_privacy_consent_checkbox')) {
              bsi_render_privacy_consent_checkbox([
                'variant' => 'input-item',
                'checkbox_id' => 'subscribe-privacy',
                'wrapper_class' => 'subscribe-section__privacy-consent',
                'html_required' => true,
              ]);
            }
            ?>

            <input type="hidden"
                   name="charset"
                   value="UTF-8">
            <input type="hidden"
                   name="default_list_id"
                   value="<?= esc_attr((string) $unisender_default_list_id) ?>">
            <?php foreach ($unisender_list_ids as $list_id): ?>
              <input type="hidden"
                     name="list_ids[]"
                     value="<?= esc_attr((string) $list_id) ?>">
            <?php endforeach; ?>
            <input type="hidden"
                   name="overwrite"
                   value="2">
            <input type="hidden"
                   name="is_v5"
                   value="1">
          </form>

        </div>

        <div class="subscribe-section-wrap-socials">
          <p class="subscribe-section__socials-text">Следите за нами в социальных сетях <br>
            и получайте лучшие предложения первыми</p>
          <?= get_template_part('template-parts/ui/socials') ?>
        </div>
      </div>
    </div>
  </div>
</section>