<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Новое «сегодня» в часовом поясе сайта.
 */
function bsi_now_for_maintenance_modal(): DateTimeImmutable
{
  return new DateTimeImmutable('now', wp_timezone());
}

/**
 * Парсит дату/время окончания показа (ACF или строка PHP).
 *
 * Возвращает null, если строка некорректна — считаем «без ограничения».
 */
function bsi_parse_maintenance_modal_until($value): ?DateTimeImmutable
{
  if ($value === null || $value === false || $value === '') {
    return null;
  }

  $tz = wp_timezone();

  if ($value instanceof DateTimeInterface) {
    return (new DateTimeImmutable('@' . $value->getTimestamp()))->setTimezone($tz);
  }

  $str = trim((string) $value);
  if ($str === '') {
    return null;
  }

  $end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $str, $tz);
  if ($end instanceof DateTimeImmutable) {
    return $end;
  }

  $end = DateTimeImmutable::createFromFormat('d/m/Y g:i a', $str, $tz);
  if ($end instanceof DateTimeImmutable) {
    return $end;
  }

  $ts = strtotime($str);
  if ($ts === false) {
    return null;
  }

  return (new DateTimeImmutable('@' . $ts))->setTimezone($tz);
}

/**
 * Модалка «предупреждение»: включено, есть текст, дедлайн не прошёл (или не задан).
 */
function bsi_maintenance_modal_is_visible(): bool
{
  if (!function_exists('get_field')) {
    return false;
  }

  if (!(bool) get_field('maintenance_modal_enabled', 'option')) {
    return false;
  }

  $message = trim((string) get_field('maintenance_modal_message', 'option'));
  if ($message === '') {
    return false;
  }

  $until_end = bsi_parse_maintenance_modal_until(get_field('maintenance_modal_active_until', 'option'));
  if ($until_end === null) {
    return true;
  }

  return bsi_now_for_maintenance_modal() <= $until_end;
}

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  if (!function_exists('acf_add_options_page')) {
    return;
  }

  // Создаем отдельную страницу настроек для модального окна
  acf_add_options_page([
    'page_title' => 'Предупреждение на сайте',
    'menu_title' => 'Предупреждение на сайте',
    'menu_slug' => 'maintenance-modal-settings',
    'capability' => 'manage_options',
    'icon_url' => 'dashicons-warning',
    'position' => 31,
  ]);

  acf_add_local_field_group([
    'key' => 'group_maintenance_modal',
    'title' => 'Предупреждение на сайте',
    'fields' => [
      [
        'key' => 'field_maintenance_modal_enabled',
        'label' => 'Включить модальное окно предупреждения',
        'name' => 'maintenance_modal_enabled',
        'type' => 'true_false',
        'instructions' => 'Включить или выключить показ модального окна предупреждения для пользователей',
        'ui' => 1,
        'default_value' => 0,
      ],
      [
        'key' => 'field_maintenance_modal_message',
        'label' => 'Текст сообщения',
        'name' => 'maintenance_modal_message',
        'type' => 'text',
        'instructions' => 'Текст сообщения, которое будет отображаться в модальном окне',
        'required' => 0,
        'conditional_logic' => [
          [
            [
              'field' => 'field_maintenance_modal_enabled',
              'operator' => '==',
              'value' => '1',
            ],
          ],
        ],
        'default_value' => '',
        'placeholder' => 'Например: На сайте ведутся технические работы',
      ],
      [
        'key' => 'field_maintenance_modal_active_until',
        'label' => 'Показывать до',
        'name' => 'maintenance_modal_active_until',
        'type' => 'date_time_picker',
        'instructions' => 'После этой даты и времени (часовой пояс сайта) модалка не показывается посетителям. Переключатель «Включить» можно не трогать. Оставьте пустым, если нужен только ручной вкл/выкл без дедлайна.',
        'display_format' => 'd.m.Y H:i',
        'return_format' => 'Y-m-d H:i:s',
        'required' => 0,
        'conditional_logic' => [
          [
            [
              'field' => 'field_maintenance_modal_enabled',
              'operator' => '==',
              'value' => '1',
            ],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'options_page',
          'operator' => '==',
          'value' => 'maintenance-modal-settings',
        ],
      ],
    ],
  ]);
});
