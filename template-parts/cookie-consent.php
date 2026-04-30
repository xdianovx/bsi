<?php
/**
 * Баннер согласия на cookie (первый визит до выбора).
 *
 * @package bsi
 */

defined('ABSPATH') || exit;
?>

<div id="bsi-cookie-consent" class="cookie-consent" role="dialog" aria-modal="false" aria-label="Cookie"
  aria-describedby="cookie-consent-desc">
  <div class="cookie-consent__inner">
    <div class="cookie-consent__text">
      <p id="cookie-consent-desc" class="cookie-consent__descr">
        Мы используем cookie. Это помогает нам делать сайт удобнее.
      </p>
    </div>
    <div class="cookie-consent__actions">
      <button type="button" class="btn btn-black sm cookie-consent__btn" data-cookie-accept>
        Принять
      </button>
    </div>
  </div>
</div>
