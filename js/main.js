import { Fancybox } from "@fancyapps/ui";
import { gtmSearch } from "./modules/gtm-search";
import { fitForm } from "./modules/forms/fit-form";
import { sliders } from "./modules/sliders";
import { phoneMask } from "./modules/forms/phone-mask";
import MicroModal from "micromodal";
import { tabs } from "./modules/tabs";
import header, { initCurrency } from "./modules/currency";
import { initNewsFilter } from "./modules/ajax/news-sort";
import { promoPageAjax } from "./modules/ajax/promo-sort";
import { burger, mobileNavAccordion } from "./modules/burger";
import { initResortHotelsAjax } from "./modules/ajax/resort-hotels";
import { initHotelMap } from "./modules/maps";

window.addEventListener("DOMContentLoaded", () => {
  burger();
  mobileNavAccordion();
  MicroModal.init();
  Fancybox.bind("[data-fancybox]", {});
  const datepick = document.querySelector('input[name="daterange"]');

  if (datepick) {
  }

  initCurrency();
  tabs(".tabs", ".tab-button", ".tab-content__item");
  sliders();
  fitForm();
  gtmSearch();
  phoneMask();
  initNewsFilter();
  promoPageAjax();
  initResortHotelsAjax();
  initHotelMap();
});
