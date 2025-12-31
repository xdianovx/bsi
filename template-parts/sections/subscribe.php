<section class="subscribe-section">
  <div class="container">
    <div class="subscribe-section__wrap">

      <div class="subscribe-section__info">
        <h2 class="h2 subscribe-section__h2">
          Подпишитесь на рассылку
        </h2>

        <p class="subscribe-section__subtitle">
          Получайте первыми самую актуальную информацию о наших планах, новинках, спецпредложениях, акциях и
          мероприятиях.
        </p>


      </div>
      <div class="subscribe-section__form_wrap">
        <?php
        $unisender_action = 'https://cp.unisender.com/ru/subscribe?hash=6w9zi9a93r6q6k7gd77osrtx7sehuo9kqhaysuxekseaxnnymabsy';
        $unisender_default_list_id = 13090573;
        $unisender_list_ids = [
          13090573,
          14461337,
          14535845,
          14535865,
          14581141,
          15190609,
          15219353,
          15219365,
          15272045,
          15307585,
          15406749,
          15406769,
          15504837,
          15730017,
          15730025,
          15908093,
          16317485,
          16549873,
          16549893,
          16570957,
          16643049,
          16698513,
          16727325,
          16797437,
          16830145,
          16878473,
          16984725,
          17777741,
          18539849,
          18539865,
          18539881,
          18540017,
          18791769,
          18866809,
          18905429,
          19528937,
          19815725,
          19863645,
          19968575,
          20096967,
          20096968,
          20096969,
          20096970,
          20097076,
          20097309,
          20097313,
          20097353,
          20097354,
          20097356,
          20097357,
          20097361,
          20097362,
          20097363,
          20097364,
          20097365,
          20097366,
          20097367,
          20097368,
        ];
        ?>
        <form method="POST" action="<?= esc_url($unisender_action) ?>" class="subscribe-section__form"
          name="subscribtion_form" us_mode="embed" accept-charset="UTF-8">
          <input type="email" name="email" required class="input" placeholder="Ваш email" autocomplete="email">

          <button class="btn btn-accent" type="submit">Подписаться</button>

          <input type="hidden" name="charset" value="UTF-8">
          <input type="hidden" name="default_list_id" value="<?= esc_attr((string) $unisender_default_list_id) ?>">
          <?php foreach ($unisender_list_ids as $list_id): ?>
            <input type="hidden" name="list_ids[]" value="<?= esc_attr((string) $list_id) ?>">
          <?php endforeach; ?>
          <input type="hidden" name="overwrite" value="2">
          <input type="hidden" name="is_v5" value="1">
        </form>

        <p class="form-policy subscribe-form__policy">
          Нажимая на кнопку "Отправить", вы соглашаетесь с <br> <a href="<?= esc_url(get_permalink(47)) ?>"
            class="policy-link">
            нашей политикой обработки персональных данных
          </a>
        </p>
      </div>
    </div>
  </div>
</section>