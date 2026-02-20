import { Fancybox } from "@fancyapps/ui";
import { gtmSearch } from "./modules/gtm-search";
import { fitForm } from "./modules/forms/fit-form";
import { visaForm } from "./modules/forms/visa-form";
import { initInsuranceForm } from "./modules/forms/insurance-form";
import { sliders } from "./modules/sliders";
import { phoneMask } from "./modules/forms/phone-mask";
import MicroModal from "micromodal";
import { tabs } from "./modules/tabs";
import header, { initCurrency } from "./modules/currency";
import { initNewsFilter } from "./modules/ajax/news-sort";
import { promoPageAjax } from "./modules/ajax/promo-sort";
import { burger, mobileNavAccordion } from "./modules/burger";
import { initResortHotelsAjax } from "./modules/ajax/resort-hotels";
import { initAccordion } from "./modules/accordition";
import { initCountryToursFilters } from "./modules/ajax/country-tours";
import { initEventToursFilters } from "./modules/ajax/event-tours";
import { initCountryAside } from "./modules/country-aside";
import { initPopularHotelsSlider } from "./modules/popular-hotels-section";
import { initPopularToursSlider } from "./modules/popular-tours-section";
import { initPopularEducationSlider } from "./modules/popular-education-section";
import { archiveProjects } from "./modules/ajax/archive-projects";
import { tourPrices } from "./modules/tour-prices";
import { initEducationFilter } from "./modules/ajax/education-filter";
import { initCountryEducationFilters } from "./modules/ajax/country-education";
import { initSingleEducationPrograms } from "./modules/ajax/single-education-programs";
import { initEducationProgramForm } from "./modules/forms/education-program-form";
import { initEventTicketForm } from "./modules/forms/event-ticket-form";
import { initMaintenanceModal } from "./modules/maintenance-modal";
import { initBonusMarquee } from "./modules/bonus-marquee";


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
  visaForm();
  initInsuranceForm();
  gtmSearch();
  phoneMask();
  initNewsFilter();
  promoPageAjax();
  initResortHotelsAjax();
  initCountryToursFilters();
  initEventToursFilters();
  initCountryAside();
  initPopularHotelsSlider();
  initPopularToursSlider();
  initPopularEducationSlider();
  archiveProjects();
  tourPrices();
  initEducationFilter();
  initCountryEducationFilters();
  initSingleEducationPrograms();
  initEducationProgramForm();
  initEventTicketForm();
  initBonusMarquee();
  
  
  // Инициализация модального окна предупреждения
  if (window.maintenanceModal) {
    initMaintenanceModal(window.maintenanceModal);
  }
});
