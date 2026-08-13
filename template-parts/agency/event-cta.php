<?php
/**
 * CTA блок мероприятия: цена + кнопка регистрации.
 *
 * @var array $args {
 *   @type int    $post_id
 *   @type string $title
 *   @type string $kind_label
 *   @type string $price               Готовая к выводу строка цены.
 *   @type string $registration_url    Внешняя ссылка регистрации.
 *   @type bool   $registration_closed Запись закрыта или мероприятие прошло.
 *   @type string $modifier            Доп. класс блока (например, для повтора внизу).
 * }
 */

$post_id = (int) ($args['post_id'] ?? get_the_ID());
$title = (string) ($args['title'] ?? get_the_title($post_id));
$kind_label = (string) ($args['kind_label'] ?? '');
$price = (string) ($args['price'] ?? '');
$registration_url = (string) ($args['registration_url'] ?? '');
$registration_closed = (bool) ($args['registration_closed'] ?? false);
$modifier = (string) ($args['modifier'] ?? '');
?>

<div class="agency-education-card__bottom <?php echo esc_attr($modifier); ?>">
  <?php if ($price !== ''): ?>
    <div class="agency-education-card__price numfont"><?php echo esc_html($price); ?></div>
  <?php endif; ?>
  <?php if ($registration_closed): ?>
    <button type="button" class="btn btn-gray agency-education-card__cta" disabled>Запись закрыта</button>
  <?php elseif ($registration_url !== ''): ?>
    <a href="<?php echo esc_url($registration_url); ?>" target="_blank" rel="noopener"
      class="btn sm btn-accent agency-education-card__cta">Регистрация</a>
  <?php else: ?>
    <button type="button" class="btn sm btn-accent agency-education-card__cta js-agency-event-reg-btn"
      data-event-id="<?php echo esc_attr($post_id); ?>" data-event-title="<?php echo esc_attr($title); ?>"
      data-event-kind="<?php echo esc_attr($kind_label); ?>">
      Регистрация
    </button>
  <?php endif; ?>
</div>
