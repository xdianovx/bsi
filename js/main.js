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
import { initAccordion } from "./modules/accordition";
import { initCountryToursFilters } from "./modules/ajax/country-tours";
import { initCountryAside } from "./modules/country-aside";
import { initPopularHotelsSlider } from "./modules/popular-hotels-section";
import { archiveProjects } from "./modules/ajax/archive-projects";
import { tourPrices } from "./modules/tour-prices";
import { initEducationFilter } from "./modules/ajax/education-filter";
import { initCountryEducationFilters } from "./modules/ajax/country-education";
import { initSingleEducationPrograms } from "./modules/ajax/single-education-programs";

window.addEventListener("DOMContentLoaded", () => {
  burger();
  mobileNavAccordion();
  MicroModal.init();
  Fancybox.bind("[data-fancybox]", {});
  const datepick = document.querySelector('input[name="daterange"]');

  if (datepick) {
  }
  initAccordion();
  initCurrency();
  tabs(".tabs", ".tab-button", ".tab-content__item");
  sliders();
  fitForm();
  gtmSearch();
  phoneMask();
  initNewsFilter();
  promoPageAjax();
  initResortHotelsAjax();
  initCountryToursFilters();
  initCountryAside();
  initPopularHotelsSlider();
  archiveProjects();
  tourPrices();
  initEducationFilter();
  initCountryEducationFilters();
  initSingleEducationPrograms();
  // initHotelMap();
});
