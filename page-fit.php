<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package asd
 */

get_header();
$countries = get_posts([
  'post_type' => 'country',
  'post_status' => 'publish',
  'numberposts' => -1,
  'orderby' => 'title',
  'order' => 'ASC',
  'post_parent' => 0, // только «родительские» страны
]);
$selected_country_id = isset($_GET['country']) ? (int) $_GET['country'] : 0;

?>

<main id="primary"
      class="site-main">

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section>
    <div class="container">
      <?php the_title('<h1 class="h1 fit-page__title">', '</h1>'); ?>

      <p class="fit-page__description"><?= get_the_excerpt() ?></p>
    </div>
  </section>

  <section class="fit-form__section">
    <div class="container">
      <div class="fit-form__wrap">

        <!-- <?php print_r($countries) ?> -->
        <!-- <h2 class="h2">Оставьте заявку</h2> -->
        <form id="simple-form"
              class="fit-form">

          <div class="form-group">
            <p class="form-group__title">Контактные данные:</p>

            <div class="form-row form-row-2">
              <div class="input-item">
                <label for="name">Имя</label>
                <input type="text"
                       name="name"
                       id="name"
                       placeholder="Имя">

                <div class="error-message"
                     data-field="name">
                </div>
              </div>

              <div class="select-item">
                <select name=""
                        name="user_position"
                        class="user_position_select"
                        id="">
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>

              <div class="input-item">
                <label for="email">email *</label>
                <input type="email"
                       name="email"
                       id="email"
                       placeholder="Почта">

                <div class="error-message"
                     data-field="email">
                </div>
              </div>

              <div class="input-item">
                <label for="phone">Телефон *</label>
                <input type="tel"
                       name="phone"
                       id="phone"
                       placeholder="+7 (___) ___-__-__">

                <div class="error-message"
                     data-field="phone">
                </div>
              </div>


            </div>
          </div>

          <div class="form-group">
            <p class="form-group__title">Страна:</p>

            <div class="form-row form-row-2">

              <div class="select-item">
                <select name=""
                        name="country-select"
                        class="fit-form__country-select"
                        id="">
                  <?php if (!empty($countries)): ?>
                    <?php foreach ($countries as $country_item): ?>
                      <option value="<?= esc_attr($country_item->ID); ?>"
                              <?= selected($selected_country_id, $country_item->ID, false); ?>>
                        <?= esc_html($country_item->post_title); ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>
          </div>


          <div class="form-group">
            <p class="form-group__title">Количество человек:</p>

            <div class="form-row form-row-2">

              <div class="select-item">
                Тут надо сделать туда сюда
              </div>
            </div>
          </div>

          <div class="form-group">
            <p class="form-group__title">Комментарии:</p>

            <div class="form-row ">

              <div class="input-item">
                <textarea type="text"
                          name="text"
                          cols="6"
                          placeholder="Сообщение..."></textarea>

                <div class="error-message"
                     data-field="name">
                </div>
              </div>

            </div>
          </div>

          <div class="checkbox-item">
            <input type="checkbox"
                   name="check"
                   id="html">

            <label for="html">Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor provident excepturi
              cupiditate, dolores doloremque libero quia ipsum nesciunt perferendis, esse maiores culpa possimus quam
              consequuntur et in aut minus modi.</label>
          </div>

          <div class="fit-form__bottom">
            <div id="form-status"></div>
            <button type="submit"
                    class="btn btn-accent fit-form__btn-submit">
              Отправить
            </button>

            <p class="form-policy fit-form__policy">
              Нажимая на кнопку "Отправить", вы соглашаетесь с <a href="<?= get_permalink(47) ?>"
                 class="policy-link">
                нашей политикой обработки персональных данных
              </a>
            </p>
          </div>
        </form>




      </div>
    </div>
  </section>


</main><!-- #main -->

<?php
// get_sidebar();
get_footer();
