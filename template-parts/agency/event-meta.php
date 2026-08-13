<?php
/**
 * Мета мероприятия: дата, время, место.
 *
 * @var array $args {
 *   @type string $date_label
 *   @type string $time
 *   @type string $place
 * }
 */

$date_label = (string) ($args['date_label'] ?? '');
$time = (string) ($args['time'] ?? '');
$place = (string) ($args['place'] ?? '');

if ($date_label === '' && $time === '' && $place === '') {
  return;
}
?>

<div class="agency-event-single__meta">
  <?php if ($date_label !== ''): ?>
    <div class="agency-event-single__meta-item agency-event-single__meta-item--date">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M6.66667 1.66797V5.0013M13.3333 1.66797V5.0013M2.5 8.33463H17.5M6.66667 11.668H6.675M10 11.668H10.0083M13.3333 11.668H13.3417M6.66667 15.0013H6.675M10 15.0013H10.0083M13.3333 15.0013H13.3417M4.16667 3.33464H15.8333C16.7538 3.33464 17.5 4.08083 17.5 5.0013V16.668C17.5 17.5884 16.7538 18.3346 15.8333 18.3346H4.16667C3.24619 18.3346 2.5 17.5884 2.5 16.668V5.0013C2.5 4.08083 3.24619 3.33464 4.16667 3.33464Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <div class="agency-event-single__meta-value"><?php echo esc_html($date_label); ?></div>
    </div>
  <?php endif; ?>
  <?php if ($time !== ''): ?>
    <div class="agency-event-single__meta-item agency-event-single__meta-item--time">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M9.99984 5.0013V10.0013L13.3332 11.668M18.3332 10.0013C18.3332 14.6037 14.6022 18.3346 9.99984 18.3346C5.39746 18.3346 1.6665 14.6037 1.6665 10.0013C1.6665 5.39893 5.39746 1.66797 9.99984 1.66797C14.6022 1.66797 18.3332 5.39893 18.3332 10.0013Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <div class="agency-event-single__meta-value"><?php echo esc_html($time); ?></div>
    </div>
  <?php endif; ?>
  <?php if ($place !== ''): ?>
    <div class="agency-event-single__meta-item agency-event-single__meta-item--place">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M16.6663 8.33464C16.6663 12.4955 12.0505 16.8288 10.5005 18.1671C10.3561 18.2757 10.1803 18.3344 9.99967 18.3344C9.81901 18.3344 9.64324 18.2757 9.49884 18.1671C7.94884 16.8288 3.33301 12.4955 3.33301 8.33464C3.33301 6.56653 4.03539 4.87083 5.28563 3.62059C6.53587 2.37035 8.23156 1.66797 9.99967 1.66797C11.7678 1.66797 13.4635 2.37035 14.7137 3.62059C15.964 4.87083 16.6663 6.56653 16.6663 8.33464Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        <path
          d="M9.99967 10.8346C11.3804 10.8346 12.4997 9.71535 12.4997 8.33464C12.4997 6.95392 11.3804 5.83464 9.99967 5.83464C8.61896 5.83464 7.49967 6.95392 7.49967 8.33464C7.49967 9.71535 8.61896 10.8346 9.99967 10.8346Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <div class="agency-event-single__meta-value"><?php echo esc_html($place); ?></div>
    </div>
  <?php endif; ?>
</div>
