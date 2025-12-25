"use strict";

let resortsSearchList = [],
  hotelsSearchList = [],
  swiperSimple;
const e2rt = {
  F: "А",
  "<": "Б",
  D: "В",
  U: "Г",
  L: "Д",
  T: "Е",
  "~": "Ё",
  ":": "Ж",
  P: "З",
  B: "И",
  Q: "Й",
  R: "К",
  K: "Л",
  V: "М",
  Y: "Н",
  J: "О",
  G: "П",
  H: "Р",
  C: "С",
  N: "Т",
  E: "У",
  A: "Ф",
  "{": "Х",
  W: "Ц",
  X: "Ч",
  I: "Ш",
  O: "Щ",
  "}": "Ъ",
  S: "Ы",
  M: "Ь",
  '"': "Э",
  ">": "Ю",
  Z: "Я",
  f: "а",
  ",": "б",
  d: "в",
  u: "г",
  l: "д",
  t: "е",
  "`": "ё",
  ";": "ж",
  p: "з",
  b: "и",
  q: "й",
  r: "к",
  k: "л",
  v: "м",
  y: "н",
  j: "о",
  g: "п",
  h: "р",
  c: "с",
  n: "т",
  e: "у",
  a: "ф",
  "[": "х",
  w: "ц",
  x: "ч",
  i: "ш",
  o: "щ",
  "]": "ъ",
  s: "ы",
  m: "ь",
  "'": "э",
  ".": "ю",
  z: "я",
};

window.addEventListener("DOMContentLoaded", () => {
  const TABLET_WIDTH = 740;
  const DESKTOP_WIDTH = 1000;
  const DESKTOP_WIDTH_MD = 1240;
  const DESKTOP_WIDTH_LG = 1400;
  const PAGE_CLASS = "page";
  const HIDDEN_CLASS = "visually-hidden";
  const hiddenClass = "visually-hidden";
  const menuBtnClassActive = "active";
  const pageHeaderClass = "page__header";
  const headerMenuBtnClass = "header__menu-btn";
  const headerMainClassActive = "active";
  const headerMainClass = "header__main";
  const siteNoticeClass = "site-notice";

  let WINDOW_WIDTH;
  let WINDOW_WIDTH_INNER;
  let windowHeight;
  let pageHeaderHeight;
  let pageHeaderFormHeight;
  let headerMain = document.querySelector(`.${headerMainClass}`);
  let headerMenuBtn = document.querySelector(`.${headerMenuBtnClass}`);
  let siteNotice = document.querySelector(`.${siteNoticeClass}`);
  let siteNoticeIsHidden = siteNotice ? siteNotice.classList.contains(hiddenClass) : false;

  function setPageMinHeight() {
    document.documentElement.style.setProperty("--re-page-min-height", `${window.innerHeight}px`);
  }

  let x;
  let big;
  function widthDevice() {
    x = document.documentElement.clientWidth;
    WINDOW_WIDTH = document.documentElement.clientWidth;
    WINDOW_WIDTH_INNER = window.innerWidth;
    setPageMinHeight();
    windowHeight = window.innerHeight;
    if (x >= 980) big = true;
    if (x < 980 && big) {
      delSideItemsCLass(".drop-country__item");
      big = false;
    }
  }

  widthDevice();

  function delSideItemsCLass(itemName) {
    const items = document.querySelectorAll(itemName);

    if (items.length > 0) {
      for (const item of items) {
        item.removeAttribute("style");
      }
    }
  }

  let close720 = x >= 720 ? true : false;
  let close980 = x >= 980 ? true : false;

  window.addEventListener("resize", () => {
    widthDevice();
    if (x < 720 && close720) {
      close720 = false;
      closePopups();
    }

    if (x >= 720 && !close720) {
      close720 = true;
      closePopups();
    }

    if (x < 980 && close980) {
      close980 = false;
      closePopups();
    }
    if (x >= 980 && !close980) {
      close980 = true;
      closePopups();
    }
  });

  /* notice-promo click */
  document.querySelector(".notice-promo")?.addEventListener("click", (e) => {
    const block = e.target.closest(".notice-promo");
    const link = block.querySelector(".notice-promo__text-box");
    const close = block.querySelector(".notice-promo__btn");
    const transitionEnd = throttle((e) => {
      block.classList.remove("_in-transition");
      block.removeEventListener("transitionend", transitionEnd);
    });

    closePopups();

    if (link && link.contains(e.target)) {
      block.classList.add("_in-transition");
      block.classList.toggle("active");
    }

    if (close && close.contains(e.target)) {
      block.classList.add("_in-transition");
      block.classList.remove("active");
    }

    block.addEventListener("transitionend", transitionEnd);

    e.preventDefault();
  });

  /* notice-promo scroll */
  const noticePromoScroll = (e) => {
    const block = document.querySelector(".notice-promo");
    if (!block) return window.removeEventListener("scroll", noticePromoScroll);

    if (!block.classList.contains("active") || block.classList.contains("_in-transition")) return;

    block.classList.remove("active");
    closePopups();
  };

  window.addEventListener("scroll", noticePromoScroll);

  /* notice-promo marquee */
  const notice_marquee0 = $(".notice-promo__bg-wrap").each(function (i, el) {
    if (window.innerWidth < TABLET_WIDTH) return;
    $(el).marquee({
      delayBeforeStart: 0,
      pauseOnHover: false,
      speed: window.innerWidth < TABLET_WIDTH ? 2 : 10 * windowHeight * 0.0005,
      direction: i % 2 === 0 ? "down" : "up",
      startVisible: true,
      duplicated: true,
      gap: 20,
    });
  });

  /* Бегущая строка */

  const marquee = $(".ticker__text-wrap").marquee({
    delayBeforeStart: 2000,
    pauseOnHover: true,
    speed: 150,
    startVisible: true,
  });

  /* Закрыть уведомление в шапке сайта */
  if (siteNotice && !siteNoticeIsHidden) {
    let siteNoticeBtn = siteNotice.querySelector(`.${siteNoticeClass}__btn`);

    siteNoticeBtn.addEventListener("click", () => {
      siteNotice.classList.add(hiddenClass);
      checkPageHeaderHeight();
      let expires = new Date();
      expires.setTime(expires.getTime() + parseInt(siteNotice.getAttribute("data-timeout")) * 24 * 60 * 60 * 1000);
      document.cookie = "informer_" + (siteNotice.getAttribute("data-id") || "") + "=1; expires=" + expires.toUTCString() + "; path=/";

      // headerFormBlockTop();
    });
  }

  function checkPageHeaderHeight() {
    const header = document.querySelector(`.${pageHeaderClass}`);
    if (!header) return;
    const newHeight = parseFloat(window.getComputedStyle(header).height);
    if (pageHeaderHeight === newHeight) return;
    pageHeaderHeight = newHeight;
    document.documentElement.style.setProperty("--re-page-header-height", `${pageHeaderHeight}px`);
  }

  function checkPageHeaderFormHeight() {
    const headerForm = document.querySelector(`.page__header-form`);
    if (!headerForm) return;
    const newFormHeight = parseFloat(window.getComputedStyle(headerForm).height);
    if (pageHeaderFormHeight === newFormHeight) return;
    pageHeaderFormHeight = newFormHeight;
    document.documentElement.style.setProperty("--re-page-header-form-height", `${pageHeaderFormHeight}px`);
  }

  checkPageHeaderHeight();
  checkPageHeaderFormHeight();

  /* New Header Handlers */
  const newHeaderHandlers = (() => {
    const BODY = document.querySelector("body");
    const header = BODY.querySelector(".header");
    if (!header) return;

    const CLASS_ACTIVE = "_active";
    const WIDTH_TABLET = 740;
    const WIDTH_DESKTOP_SM = 1000;
    const WIDTH_DESKTOP_MD = 1240;
    const WIDTH_DESKTOP_LG = 1400;
    const WIDTH_DESKTOP_XL = 1600;

    const headerMain = header.querySelector(".header__main");
    const headerForm = document.querySelector(".page__header-form");
    const hasForm = headerForm !== null;
    const formcloseBtn = document.querySelector(".header-form__formclose-btn");

    let WINDOW_WIDTH = window.innerWidth;
    let WINDOW_WIDTH_CURRENT = WINDOW_WIDTH;
    let lastScrollTop = 0;

    const recalcHeaderHeight = () => {
      const headerHeight = header.offsetHeight;
      headerMain.style.top = `${headerHeight}px`;
      headerMain.style.maxHeight = `calc(100dvh - ${headerHeight}px`;
      return headerHeight;
    };

    const headerReplaceItemsHandler = () => {
      const config = [
        {
          e: ".header__defs",
          c1: ".header__main-part--info",
          c2: ".header__row1 .header__right",
          c: WINDOW_WIDTH >= WIDTH_TABLET,
          c1_p: "append",
          c2_p: "prepend",
          c1_hide: false,
        },
        {
          e: ".header__lk",
          c1: ".header__main-part--lk",
          c2: ".header__row1 .header__btns",
          c: WINDOW_WIDTH >= WIDTH_DESKTOP_SM,
          c1_p: "append",
          c2_p: "prepend",
          c1_hide: true,
        },
        {
          e: ".header__currency-wrap",
          c1: ".header__main-part--info",
          c2: ".header__row1 .header__left",
          c: WINDOW_WIDTH >= WIDTH_DESKTOP_MD,
          c1_p: "prepend",
          c2_p: "append",
          c1_hide: true,
        },
        {
          e: ".header__nav-wrap",
          c1: ".header__main-part--nav",
          c2: ".header__row2 .header__inner",
          c: WINDOW_WIDTH >= WIDTH_DESKTOP_LG,
          c1_p: "append",
          c2_p: "append",
          c1_hide: true,
        },
      ];

      const hideBtn = (condition = false) => {
        header.querySelector(".header__menubtn-wrap").classList.toggle("hidden", condition);
      };

      const replace = (
        elem,
        container1,
        container2,
        condition,
        container1_place = "append",
        container2_place = "append",
        container1_hide = false
      ) => {
        const el = header.querySelector(elem);
        const cont1 = header.querySelector(container1);
        const cont2 = header.querySelector(container2);
        const cond = condition;
        if (!el || !cont1 || !cont2 || cond === undefined) return;

        if (cond && !cont2.contains(el)) {
          moveElement(el, cont2, container2_place);
          if (container1_hide) cont1.classList.add("hidden");
        } else if (!cond && !cont1.contains(el)) {
          moveElement(el, cont1, container1_place);
          if (container1_hide) cont1.classList.remove("hidden");
        }
      };

      const run = () => {
        let allInC2 = true;
        config.forEach(({ e, c1, c2, c, c1_p, c2_p, c1_hide }, i) => {
          replace(e, c1, c2, c, c1_p, c2_p, c1_hide);
          const el = header.querySelector(e);
          const cont2 = header.querySelector(c2);

          if (el && !cont2?.contains(el)) {
            allInC2 = false;
          }
        });
        hideBtn(allInC2);
      };

      run();
    };

    const headerMobileMenuHandler = () => {
      function layoutToggle(btn, layout, cb, layoutClickClose = false) {
        const menuBtn = document.querySelector(btn);
        const menuLayout = document.querySelector(layout);

        if (!menuBtn || !menuLayout) return;

        menuBtn.addEventListener("click", toggleMenuBtn);

        if (layoutClickClose) menuLayout.addEventListener("click", toggleMenuBtn, false);

        function toggleMenuBtn(e) {
          if (e.target !== menuLayout && !menuBtn.contains(e.target)) return;

          const isActive = menuLayout.classList.contains(CLASS_ACTIVE);
          const scrollWidth = window.innerWidth - BODY.offsetWidth;

          if (isActive) {
            menuBtn.classList.remove(CLASS_ACTIVE);
            menuLayout.classList.remove(CLASS_ACTIVE);
            setTimeout(() => {
              if (!menuLayout.classList.contains(CLASS_ACTIVE)) {
                BODY.style.width = "";
                BODY.style.marginRight = "";
                BODY.style.overflow = "unset";
              }
            }, 300);
          } else {
            closePopups();
            BODY.style.width = getComputedStyle(BODY).width;
            BODY.style.marginRight = `${scrollWidth}px`;
            BODY.style.overflow = "hidden";
            menuBtn.classList.add(CLASS_ACTIVE);
            menuLayout.classList.add(CLASS_ACTIVE);
          }

          if (cb && typeof cb === "function") cb();
        }
      }

      layoutToggle(`.${headerMenuBtnClass}`, `.${headerMainClass}`, recalcHeaderHeight, false);
    };

    const changeScrollClasses = () => {
      var currentScrollTop = window.scrollY;
      var isMinScroll = currentScrollTop > 30;
      var isAfterHeader = currentScrollTop >= pageHeaderHeight;
      var isScrollingDown = lastScrollTop < currentScrollTop && currentScrollTop >= 700;
      var isScrollingUp = lastScrollTop >= currentScrollTop && currentScrollTop >= 700;

      header.classList.toggle("_scroll", isMinScroll);
      header.classList.toggle("_scroll-up", isScrollingUp);
      header.classList.toggle("_scroll-down", isScrollingDown);

      if (hasForm) {
        headerForm.classList.toggle("_scroll", isAfterHeader);
        headerForm.classList.toggle("_scroll-down", isScrollingDown);
        headerForm.classList.toggle("_scroll-up", isScrollingUp);
      }

      lastScrollTop = currentScrollTop;
    };

    /* events */
    headerReplaceItemsHandler();
    headerMobileMenuHandler();

    const ON_LOAD_EVENTS = () => {};
    const ON_RESIZE_EVENTS = () => {
      if (window.innerWidth !== WINDOW_WIDTH) {
        WINDOW_WIDTH = window.innerWidth;
      }
    };
    const ON_RESIZE_EVENTS_DELAYED = throttle(() => {
      if (WINDOW_WIDTH_CURRENT !== WINDOW_WIDTH) {
        BODY.style.width = "";
        BODY.style.marginRight = "";
        BODY.style.overflow = "unset";
        header.querySelectorAll("._active").forEach((e) => e.classList.remove("_active"));
        headerMain.style.widht = "";
        headerMain.style.marginRight = "";
        headerReplaceItemsHandler();
        changeScrollClasses();
        checkPageHeaderHeight();
        if (formcloseBtn?.classList.contains("active")) {
          formcloseBtn.click();
          setTimeout(() => {
            checkPageHeaderFormHeight();
          }, 400);
        } else {
          checkPageHeaderFormHeight();
        }
        WINDOW_WIDTH_CURRENT = WINDOW_WIDTH;
      }
    }, 300);
    const ON_SCROLL_EVENTS = () => {
      changeScrollClasses();
    };
    const ON_SCROLL_EVENTS_DELAYED = throttle(() => {}, 300);

    window.addEventListener("load", ON_LOAD_EVENTS);
    window.addEventListener("resize", () => {
      ON_RESIZE_EVENTS();
      ON_RESIZE_EVENTS_DELAYED();
    });
    window.addEventListener("scroll", () => {
      ON_SCROLL_EVENTS();
      ON_SCROLL_EVENTS_DELAYED();
    });

    return {
      recalcHeaderHeight,
    };
  })();

  /* Подменю шапки */
  const navItemClass = "nav-item";
  const navItemClassActive = `active`;
  const navItemBtnClass = `${navItemClass}__btn`;
  const navItemBodyClass = `${navItemClass}__body`;
  const navItemBodyInnerClass = `${navItemClass}__body-inner`;
  const navItems = document.querySelectorAll(`.${navItemClass}`);
  const navItemBtns = document.querySelectorAll(`.${navItemBtnClass}`);
  const navItemBodys = document.querySelectorAll(`.${navItemBodyClass}`);

  if (navItemBtns.length > 0) {
    const dur = 300;

    function navItemBodyHidden(item, elem, height = 0) {
      elem.style.height = height;
      setTimeout(() => {
        elem.style.height = "0";
        item.classList.remove(navItemClassActive);
      }, 0);
    }

    function navItemBodyVisible(item, elem, height) {
      elem.style.height = height;
      item.classList.add(navItemClassActive);
      setTimeout(() => {
        item.classList.contains(navItemClassActive) ? (elem.style.height = "auto") : (elem.style.height = "0");
      }, dur);
    }

    function changeNavItemActive() {
      let item = this.closest(`.${navItemClass}`);
      let itemBody = item.querySelector(`.${navItemBodyClass}`);
      let isItemActive = item.classList.contains(navItemClassActive);
      let itemBodyInner = item.querySelector(`.${navItemBodyInnerClass}`);
      let itemBodyNeedyHeight = window.getComputedStyle(itemBodyInner).height;

      if (!isItemActive) {
        navItems.forEach((item) => {
          if (!item.classList.contains(navItemClassActive)) return;
          item.querySelector(`.${navItemBtnClass}`).click();
        });
        navItemBodyVisible(item, itemBody, itemBodyNeedyHeight);
      } else {
        navItemBodyHidden(item, itemBody, itemBodyNeedyHeight);
      }
    }

    function checkLayersPosition() {
      const layers = navItemBodys;
      const bodyW = WINDOW_WIDTH_INNER;
      if (!layers[0].closest(".header__row2")) return;

      const padding = 15;

      layers.forEach((layer) => {
        layer.style.right = "";
        layer.style.left = "";
        layer.style.maxWidth = "";
        layer.style.transform = "";

        const { right, left } = layer.getBoundingClientRect();
        const layerW = bodyW - padding * 2;

        const excessR = right - bodyW + padding;
        const overlapW = layerW + left - right;

        if (overlapW < 0) {
          layer.style.right = "auto";
          layer.style.left = `0`;
          layer.style.maxWidth = `${layerW}px`;
        } else if (right >= bodyW - padding) {
          layer.style.left = `-${excessR}px`;
        }
      });
    }

    navItemBodys.forEach((body) => {
      const item = body.closest(`.${navItemClass}`);
      const btn = item.querySelector(`.${navItemBtnClass}`);
      navItemBodyHidden(item, body);
      btn.addEventListener("click", changeNavItemActive);
    });
    setTimeout(() => checkLayersPosition(), 600);

    window.addEventListener("resize", throttle(checkLayersPosition, 600));
  }

  // hero-swiper
  const swiperHero = new Swiper(".hero__swiper", {
    wrapperClass: "hero__swiper-wrapper",
    slideClass: "hero__slide",
    speed: 400,
    spaceBetween: 0,
    loop: true,

    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },

    pagination: {
      el: ".slider-pagination",
      type: "bullets",
    },
    navigation: {
      nextEl: ".hero .slider-btn--next",
      prevEl: ".hero .slider-btn--prev",
    },
  });

  // hero2-swiper
  if (document.querySelectorAll(".hero2__slide").length > 1) {
    const swiperHero2 = new Swiper(".hero2__swiper", {
      wrapperClass: "hero2__swiper-wrapper",
      slideClass: "hero2__slide",
      speed: 400,
      spaceBetween: 0,
      loop: true,

      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },

      pagination: {
        el: ".hero2 .slider-pagination",
        type: "bullets",
        clickable: true,
      },

      navigation: {
        nextEl: ".hero2 .slider-btn2--next",
        prevEl: ".hero2 .slider-btn2--prev",
      },
    });
  }

  initSwiperSimple();

  document.querySelectorAll(".sale__swiper").forEach((block, i) => {
    const slidesLength = block.querySelectorAll(".sale__slide").length;

    if (slidesLength < 2) return;

    const initMod = `_${i + 1}`;

    block.classList.add(initMod);

    const swiperSale = new Swiper(`.sale__swiper.${initMod}`, {
      wrapperClass: "sale__wrapper",
      slideClass: "sale__slide",
      speed: 400,
      spaceBetween: 15,
      loop: true,

      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },

      navigation: {
        nextEl: ".sale .slider-btn--next",
        prevEl: ".sale .slider-btn--prev",
      },

      pagination: {
        el: ".slider-pagination",
        type: "bullets",
      },
    });
  });

  const sliderToursTop = (() => {
    const mainBlock = document.querySelector(".tours-top");
    if (!mainBlock) return;
    const slides = mainBlock.querySelectorAll(".tours-top__slide");
    if (slides.length < 2) return;

    return new Swiper(".tours-top__slider", {
      wrapperClass: "tours-top__slider-list",
      slideClass: "tours-top__slide",
      speed: 400,
      spaceBetween: 0,
      loop: true,

      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      watchOverflow: true,

      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      },

      navigation: {
        nextEl: ".tours-top__slider .slider-btn2--next",
        prevEl: ".tours-top__slider .slider-btn2--prev",
      },

      on: {
        init: (slider) => {
          mainBlock.classList.add("tours-top_slider");
        },
      },
    });
  })();

  const sliderVolForm = (() => {
    const mainBlock = document.querySelector(".vol-form");
    if (!mainBlock) return;
    const slides = mainBlock.querySelectorAll(".vol-form__slide");
    if (slides.length < 2) return;

    return new Swiper(".vol-form__slider", {
      wrapperClass: "vol-form__slider-list",
      slideClass: "vol-form__slide",
      speed: 400,
      spaceBetween: 0,
      loop: true,

      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      watchOverflow: true,

      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      },

      navigation: {
        nextEl: ".vol-form__slider .slider-btn2--next",
        prevEl: ".vol-form__slider .slider-btn2--prev",
      },

      pagination: {
        el: ".vol-form .slider-pagination",
        type: "bullets",
        clickable: true,
        dynamicBullets: slides.length > 5 ? true : false,
      },

      // on: {
      //     init: (slider) => {
      //         mainBlock.classList.add('vol-form_slider');
      //     }
      // }
    });
  })();

  const dropCountry2 = (() => {
    const classes = {
      block: "drop-country2",
      mods: {
        alpha: "drop-country2_alpha",
        region: "drop-country2_region",
        switches: "drop-country2_switches",
      },
      items: "drop-country2__item",
      bodys: "drop-country2__groups",
      bodyHidden: "hidden",
      mainList: "drop-country2__group",
      searchForm: "search-input",
      searchInput: "search-input__search",
      mainEmpty: "drop-country2__main-empty",
      mainEmptyHidden: "hidden",
      switcher: {
        zone: "drop-country2__btns",
        btn: "btn",
        btnActive: "active",
        groups: {
          alpha: "by-alpha",
          region: "by-region",
        },
      },
      filter: {
        btns: "drop-country2__btns-filters",
        btn: "btn",
        btnActive: "active",
      },
    };

    const searchInputs = document.querySelectorAll(`.${classes.block} .${classes.searchInput}`);
    const searchForms = document.querySelectorAll(`.${classes.block} .${classes.searchForm}`);

    const modGroupMap = {
      alpha: {
        btnSort: classes.switcher.groups.alpha,
        blockMod: classes.mods.alpha,
      },
      region: {
        btnSort: classes.switcher.groups.region,
        blockMod: classes.mods.region,
      },
    };

    let currentVisaFilter = "visa-all";

    const switchGroups = (target, block) => {
      const bodysHidden = block.querySelectorAll(`.${classes.bodys}:not(.${classes.bodyHidden})`);
      const targetData = target.dataset.sort;

      bodysHidden.forEach((body) => {
        body.classList.add(classes.bodyHidden);
      });

      block.querySelector(`.${classes.bodys}[data-sort="${targetData}"]`)?.classList.remove(classes.bodyHidden);
    };

    const switchBtns = (target, block) => {
      const zone = block.querySelector(`.${classes.switcher.zone}`);
      if (!zone) return;

      const activeBtn = zone.querySelector(`.${classes.switcher.btn}.${classes.switcher.btnActive}`);

      if (activeBtn && activeBtn !== target) {
        activeBtn.disabled = false;
        activeBtn.classList.remove(classes.switcher.btnActive);
      }

      target.disabled = true;
      target.classList.add(classes.switcher.btnActive);
    };

    const switchBlockMod = (target, block) => {
      if (target.classList.contains(classes.switcher.btnActive)) return;

      const sort = target.dataset.sort;
      if (!sort) return;

      const modEntry = Object.entries(modGroupMap).find(([, val]) => val.btnSort === sort);
      if (!modEntry) return;

      const [, modObj] = modEntry;

      Object.values(modGroupMap).forEach(({ blockMod }) => {
        if (block.classList.contains(blockMod)) {
          block.classList.remove(blockMod);
        }
      });

      block.classList.add(modObj.blockMod);
    };

    const switchList = (e) => {
      const target = e.target.closest(`.${classes.switcher.btn}`);
      if (!target) return;

      if (target.closest(`.${classes.filter.btns}`)) return;

      const block = target.closest(`.${classes.block}`);
      if (!block) return;

      if (!block.classList.contains(classes.mods.switches)) return;

      switchBlockMod(target, block);
      switchGroups(target, block);
      switchBtns(target, block);
    };

    document.addEventListener("click", switchList);

    const filterItems = (block, query, visaFilter) => {
      const groups = block.querySelectorAll(`.${classes.mainList}`);
      const emptyBlock = block.querySelector(`.${classes.mainEmpty}`);
      const bodies = block.querySelectorAll(`.${classes.bodys}`);

      groups.forEach((group) => {
        const items = group.querySelectorAll(`.${classes.items}`);

        if (query[0].length < 2 && visaFilter === "visa-all") {
          items.forEach((item) => {
            item.style.display = "";
          });
          group.style.display = "";
          return;
        }

        items.forEach((item) => {
          const text = item.textContent.toLowerCase();
          const visa = item.dataset.visa || "no";

          const textMatch = text.includes(query[0]) || (query[1] && text.includes(query[1]));
          const visaMatch = visaFilter === "visa-all" || visa === visaFilter.replace("visa-", "");

          item.style.display = textMatch && visaMatch ? "" : "none";
        });

        const allHidden = Array.from(items).every((item) => item.style.display === "none");
        group.style.display = allHidden ? "none" : "";
      });

      const allGroupsHidden = Array.from(groups).every((group) => group.style.display === "none");

      if (allGroupsHidden) {
        emptyBlock?.classList.remove(classes.mainEmptyHidden);
        bodies.forEach((body) => (body.style.display = "none"));
      } else {
        emptyBlock?.classList.add(classes.mainEmptyHidden);
        bodies.forEach((body) => (body.style.display = ""));
      }
    };

    const onVisaFilterClick = (e) => {
      const target = e.target.closest(`.${classes.filter.btn}`);
      if (!target) return;

      const block = target.closest(`.${classes.block}`);
      if (!block) return;

      const btnsContainer = block.querySelector(`.${classes.filter.btns}`);
      if (!btnsContainer) return;

      const activeBtn = btnsContainer.querySelector(`.${classes.filter.btn}.${classes.filter.btnActive}`);
      if (activeBtn && activeBtn !== target) {
        activeBtn.disabled = false;
        activeBtn.classList.remove(classes.filter.btnActive);
      }

      target.disabled = true;
      target.classList.add(classes.filter.btnActive);

      currentVisaFilter = target.dataset.filter || "visa-all";

      const input = block.querySelector(`.${classes.searchInput}`);
      const query = input ? multiStr(input.value.toLowerCase().trim()) : [""];

      filterItems(block, query, currentVisaFilter);
    };

    document.addEventListener("click", (e) => {
      if (e.target.closest(`.${classes.filter.btns}`)) {
        onVisaFilterClick(e);
      }
    });

    searchInputs.forEach((input) => {
      const block = input.closest(`.${classes.block}`);
      if (!block) return;
      input.value = "";
      const groups = block.querySelectorAll(`.${classes.mainList}`);
      const emptyBlock = block.querySelector(`.${classes.mainEmpty}`);
      const bodies = block.querySelectorAll(`.${classes.bodys}`);

      input.addEventListener("input", () => {
        const query = multiStr(input.value.toLowerCase().trim());

        const visaFilterContainer = block.querySelector(`.${classes.filter.btns}`);
        if (visaFilterContainer) {
          filterItems(block, query, currentVisaFilter);
        } else {
          groups.forEach((group) => {
            const items = group.querySelectorAll(`.${classes.items}`);

            if (query[0].length < 2) {
              items.forEach((item) => {
                item.style.display = "";
              });
              group.style.display = "";
              return;
            }

            items.forEach((item) => {
              const text = item.textContent.toLowerCase();

              item.style.display = text.includes(query[0]) || (query[1] && text.includes(query[1])) ? "" : "none";
            });

            const allHidden = Array.from(items).every((item) => item.style.display === "none");
            group.style.display = allHidden ? "none" : "";
          });

          const allGroupsHidden = Array.from(groups).every((group) => group.style.display === "none");

          if (allGroupsHidden) {
            emptyBlock?.classList.remove(classes.mainEmptyHidden);
            bodies.forEach((body) => (body.style.display = "none"));
          } else {
            emptyBlock?.classList.add(classes.mainEmptyHidden);
            bodies.forEach((body) => (body.style.display = ""));
          }
        }
      });

      input.addEventListener("blur", () => {
        const allGroupsHidden = Array.from(groups).every((group) => group.style.display === "none");

        if (!allGroupsHidden) return;

        emptyBlock?.classList.add(classes.mainEmptyHidden);
        bodies.forEach((body) => (body.style.display = ""));
        input.value = "";
        input.dispatchEvent(new Event("input"));
      });
    });

    searchForms.forEach((form) => {
      form.setAttribute("novalidate", true);
      form.querySelector("[type=submit]").setAttribute("tabindex", "-1");
      form.addEventListener("submit", (e) => {
        e.preventDefault();
      });
    });
  })();

  if ($(".slider__swiper").length > 0) {
    //some-slider-wrap-in
    let swiperInstances = [];
    $(".slider__swiper").each(function (index, element) {
      //some-slider-wrap-in
      const $this = $(this);
      $this.addClass("instance-" + index); //instance need to be unique (ex: some-slider)
      $this
        .parent()
        .find(".slider-btn--prev")
        .addClass("prev-" + index); //prev must be unique (ex: some-slider-prev)
      $this
        .parent()
        .find(".slider-btn--next")
        .addClass("next-" + index); //next must be unique (ex: some-slider-next)
      swiperInstances[index] = new Swiper(".instance-" + index, {
        //instance need to be unique (ex: some-slider)
        wrapperClass: "slider__list",
        slideClass: "slider__item",
        speed: 400,
        spaceBetween: 10,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 10,
        watchOverflow: true,
        loop: false,

        navigation: {
          prevEl: ".prev-" + index, //prev must be unique (ex: some-slider-prev)
          nextEl: ".next-" + index, //next must be unique (ex: some-slider-next)
        },
        breakpoints: {
          720: {
            slidesPerView: 3,
            spaceBetween: 20,
          },
        },
      });
    });
  }

  if ($(".slider2__swiper").length > 0) {
    //some-slider-wrap-in
    let swiperInstances = [];
    $(".slider2__swiper").each(function (index, element) {
      //some-slider-wrap-in
      const $this = $(this);
      $this.addClass("instance2-" + index); //instance need to be unique (ex: some-slider)
      $this
        .parent()
        .find(".slider-btn--prev")
        .addClass("prev-" + index); //prev must be unique (ex: some-slider-prev)
      $this
        .parent()
        .find(".slider-btn--next")
        .addClass("next-" + index); //next must be unique (ex: some-slider-next)
      swiperInstances[index] = new Swiper(".instance2-" + index, {
        //instance need to be unique (ex: some-slider)
        wrapperClass: "slider2__list",
        slideClass: "slider2__item",
        speed: 400,
        spaceBetween: 10,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 10,
        watchOverflow: true,

        navigation: {
          prevEl: ".prev-" + index, //prev must be unique (ex: some-slider-prev)
          nextEl: ".next-" + index, //next must be unique (ex: some-slider-next)
        },
        breakpoints: {
          1200: {
            slidesPerView: "auto",
            spaceBetween: 20,
          },

          1400: {
            slidesPerView: 4,
            spaceBetween: 20,
          },
        },
      });
    });
  }

  if ($(".slider3__swiper").length > 0) {
    //some-slider-wrap-in
    let swiperInstances = [];
    $(".slider3__swiper").each(function (index, element) {
      //some-slider-wrap-in
      const $this = $(this);
      $this.addClass("instance3-" + index); //instance need to be unique (ex: some-slider)
      $this
        .parent()
        .find(".slider-btn2--prev")
        .addClass("prev-" + index); //prev must be unique (ex: some-slider-prev)
      $this
        .parent()
        .find(".slider-btn2--next")
        .addClass("next-" + index); //next must be unique (ex: some-slider-next)
      swiperInstances[index] = new Swiper(".instance3-" + index, {
        //instance need to be unique (ex: some-slider)
        wrapperClass: "slider3__list",
        slideClass: "slider3__item",
        speed: 400,
        spaceBetween: 20,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        watchOverflow: true,

        navigation: {
          prevEl: ".prev-" + index, //prev must be unique (ex: some-slider-prev)
          nextEl: ".next-" + index, //next must be unique (ex: some-slider-next)
        },
      });
    });
  }

  if ($(".section-slider__list-wrap").length > 0) {
    let swiperInstances = [];
    $(".section-slider__list-wrap").each(function (index, element) {
      const $this = $(this);
      $this.addClass("section-slider___" + index);
      $this
        .parent()
        .find(".slider-btn2--prev")
        .addClass("prev-" + index);
      $this
        .parent()
        .find(".slider-btn2--next")
        .addClass("next-" + index);
      swiperInstances[index] = new Swiper(".section-slider___" + index, {
        wrapperClass: "section-slider__list",
        slideClass: "section-slider__item",
        speed: 400,
        spaceBetween: 20,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: 1,
        watchOverflow: true,
        loop: true,

        navigation: {
          prevEl: ".prev-" + index,
          nextEl: ".next-" + index,
        },

        on: {
          init: () => {
            replaceBtns($this);
            window.addEventListener(
              "resize",
              throttle(() => replaceBtns($this))
            );
          },
        },
      });
    });

    function replaceBtns($this) {
      const $prev = $this.closest(".section-slider").prev();
      if (!$prev.hasClass("section-slider-header")) return;

      const $buttons = $prev.parent().find(".slider-btns2");
      if (!$buttons.length) return;

      if (WINDOW_WIDTH_INNER < TABLET_WIDTH && $prev.has($buttons).length) $buttons.appendTo($this);
      else if (WINDOW_WIDTH_INNER >= TABLET_WIDTH && $this.has($buttons).length) $buttons.appendTo($prev);
    }
  }

  // maestro slider
  if (document.querySelectorAll(".maestro__slide").length > 1) {
    const swiperHero2 = new Swiper(".maestro__slider", {
      wrapperClass: "maestro__slider-list",
      slideClass: "maestro__slide",
      speed: 400,
      spaceBetween: 0,
      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      watchOverflow: true,
      slidesPerView: "auto",

      navigation: {
        nextEl: ".maestro .slider-btn2--next",
        prevEl: ".maestro .slider-btn2--prev",
      },

      breakpoints: {
        740: {
          spaceBetween: 20,
        },
      },
    });
  }

  // projects-merch
  if (document.querySelectorAll(".projects-merch__slide").length > 1) {
    const swiperHero2 = new Swiper(".projects-merch__swiper", {
      wrapperClass: "projects-merch__swiper-wrapper",
      slideClass: "projects-merch__slide",
      speed: 400,
      spaceBetween: 0,
      loop: true,

      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },

      navigation: {
        nextEl: ".projects-merch .slider-btn2--next",
        prevEl: ".projects-merch .slider-btn2--prev",
      },
    });
  }

  if ($(".tour-calendar__list-wrap").length > 0) {
    let swiperInstances = [];
    $(".tour-calendar__list-wrap").each(function (index, element) {
      const $this = $(this);
      $this.addClass("tour-calendar___" + index);
      $this
        .parent()
        .find(".slider-btn2--prev")
        .addClass("prev-" + index);
      $this
        .parent()
        .find(".slider-btn2--next")
        .addClass("next-" + index);
      swiperInstances[index] = new Swiper(".tour-calendar___" + index, {
        wrapperClass: "tour-calendar__months",
        slideClass: "tour-calendar__month",
        speed: 300,
        spaceBetween: 1,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        watchOverflow: true,
        freeMode: true,

        navigation: {
          prevEl: ".prev-" + index,
          nextEl: ".next-" + index,
        },

        on: {
          init: () => {
            replaceBtns($this);
            window.addEventListener(
              "resize",
              throttle(() => replaceBtns($this))
            );
          },
        },
      });
    });

    function replaceBtns($this) {
      const $prev = $this.closest(".tour-calendar").prev();
      if (!$prev.hasClass("tour-calendar-header")) return;

      const $buttons = $prev.parent().find(".slider-btns2");
      if (!$buttons.length) return;

      $buttons.appendTo($prev);
      $buttons.removeClass("hidden");
    }
  }

  /* slider-posts__swiper */
  let initSliderPostsSwiper = function () {
    if ($(".slider-posts__swiper").length === 0) return;

    let swiperInstances = [];

    $(".slider-posts__swiper").each(function (index, element) {
      //some-slider-wrap-in
      const $this = $(this);
      $this.addClass("posts-slider-" + index); //instance need to be unique (ex: some-slider)
      $this
        .parent()
        .find(".slider-btn--prev")
        .addClass("prev-" + index); //prev must be unique (ex: some-slider-prev)
      $this
        .parent()
        .find(".slider-btn--next")
        .addClass("next-" + index); //next must be unique (ex: some-slider-next)
      swiperInstances[index] = new Swiper(".posts-slider-" + index, {
        //instance need to be unique (ex: some-slider)
        wrapperClass: "slider-posts__list",
        slideClass: "slider-posts__item",
        speed: 400,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 20,
        watchOverflow: true,
        // loop: true,

        navigation: {
          prevEl: ".prev-" + index, //prev must be unique (ex: some-slider-prev)
          nextEl: ".next-" + index, //next must be unique (ex: some-slider-next)
        },
        breakpoints: {
          720: {
            slidesPerView: 2,
          },
          980: {
            slidesPerView: 3,
          },
        },
      });
    });
  };

  initSliderPostsSwiper();

  const sliderTourGallery = (() => {
    const classes = {
        block: "tour-gallery",
        mods: {
          slider: "tour-gallery_slider",
          _3: "tour-gallery_grid",
          _5: "tour-gallery_grid_5",
        },
        slider: "tour-gallery__slider",
        list: "tour-gallery__slider-list",
        item: "tour-gallery__slide",
      },
      btns = {
        btnNext: ".tour-gallery__slider .slider-btn2--next",
        btnPrev: ".tour-gallery__slider .slider-btn2--prev",
      },
      mainBlock = document.querySelector(`.${classes.block}`);

    if (!mainBlock) return;
    const slides = mainBlock.querySelectorAll(`.${classes.item}`);
    const count = slides.length;
    if (count < 2) return removeLoader();
    if (count > 2) mainBlock.classList.add(classes.mods._3);
    if (count > 3) slides[2].dataset.count = count - 3;
    if (count > 4) mainBlock.classList.add(classes.mods._5);
    if (count > 5) slides[4].dataset.count = count - 5;

    const list = mainBlock.querySelector(`.${classes.list}`),
      items = mainBlock.querySelectorAll(`.${classes.item}`),
      sliderSettings = {
        wrapperClass: classes.list,
        slideClass: classes.item,
        speed: 400,
        spaceBetween: 0,

        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        watchOverflow: true,

        followFinger: false,

        navigation: {
          nextEl: btns.btnNext,
          prevEl: btns.btnPrev,
        },

        on: {
          init: (slider) => {
            mainBlock.classList.add(classes.mods.slider);
          },
        },
      };

    let gridGallery = undefined;

    listUpdate();
    removeLoader();
    window.addEventListener("resize", throttle(listUpdate, 500));

    function removeLoader() {
      const loader = mainBlock.querySelector("._sceleton");
      if (loader) loader.classList.add("_loaded");
    }

    function listUpdate() {
      let screenWidth = window.innerWidth;
      let offClass = mainBlock.classList.contains(classes.mods._3);
      if ((screenWidth < TABLET_WIDTH || !offClass) && gridGallery == undefined) {
        gridGallery = new Swiper("." + classes.slider, sliderSettings);
      } else if (screenWidth >= TABLET_WIDTH && offClass && gridGallery !== undefined) {
        gridGallery.destroy();
        gridGallery = undefined;
        list.removeAttribute("style");
        items.forEach((item) => item.removeAttribute("style"));
      }
    }
  })();

  const cardTour2Init = (() => {
    const classes = {
        block: "card-tour2",
        mods: {
          slider: "card-tour2_slider",
          _3: "card-tour2_img-grid",
          wideCard: "card-tour2_wide-card",
        },
        slider: "card-tour2__img-part",
        list: "card-tour2__imgs",
        item: "card-tour2__imgs-item",
      },
      pagination = {
        selector: ".card-tour2__gallery-pagination",
      };

    const section = document.querySelector(".tours-in2");
    const btnCard = section?.querySelector(".tours-in2__switch_card");
    const btnRow = section?.querySelector(".tours-in2__switch_row");
    const allCards = document.querySelectorAll(`.${classes.block}`);
    let swiperInstances = [];

    sectionInit();
    allCards.forEach((c, i) => c.classList.add(`_card_${i}`));
    allCardsUpdate();

    window.addEventListener("resize", throttle(allCardsUpdate, 500));

    function allCardsUpdate() {
      allCards.forEach((block, i) => {
        const instanceClass = `_card_${i}`;
        const mainBlock = document.querySelector(`.${classes.block}.${instanceClass}`);
        const offClass = mainBlock.classList.contains(classes.mods.wideCard);
        const slides = mainBlock.querySelectorAll(`.${classes.item}`);
        const count = slides.length;
        mainBlock.classList.remove(classes.mods.slider, classes.mods._3);
        if (count < 2) return;
        if (WINDOW_WIDTH_INNER >= DESKTOP_WIDTH_MD && offClass && count > 2) mainBlock.classList.add(classes.mods._3);
        if (WINDOW_WIDTH_INNER >= DESKTOP_WIDTH_MD && offClass && count > 3) slides[2].dataset.count = count - 3;

        const list = mainBlock.querySelector(`.${classes.list}`),
          items = mainBlock.querySelectorAll(`.${classes.item}`),
          sliderSettings = {
            wrapperClass: classes.list,
            slideClass: classes.item,
            speed: 400,
            spaceBetween: 0,

            observer: true,
            observeSlideChildren: true,
            observeParents: true,
            watchOverflow: true,

            followFinger: false,

            nested: () => {
              return mainBlock.closest(".swiper-initialized");
            },

            pagination: {
              el: pagination.selector,
              type: "bullets",
              clickable: true,
            },

            on: {
              init: (slider) => {
                mainBlock.classList.add(classes.mods.slider);
              },
            },
          };

        listUpdate(mainBlock, i, list, items, sliderSettings, instanceClass);
      });
    }

    function listUpdate(mainBlock, i, list, items, sliderSettings, instanceClass) {
      let offClass = mainBlock.classList.contains(classes.mods._3);
      if (!offClass && swiperInstances[i] == undefined) {
        swiperInstances[i] = new Swiper(`.${instanceClass} .${classes.slider}`, sliderSettings);
      } else if (offClass && swiperInstances[i] !== undefined) {
        swiperInstances[i].destroy();
        swiperInstances[i] = undefined;
        list.removeAttribute("style");
        items.forEach((item) => item.removeAttribute("style"));
      }
    }

    function sectionInit() {
      if (!section || !btnCard || !btnRow) return;
      section.addEventListener("click", sectionSwithing);
    }

    function switchToCards() {
      btnCard.disabled = true;
      btnRow.disabled = false;
      section.classList.add("tours-in2_cards");
      section.querySelectorAll(".card-tour2").forEach((c) => c.classList.replace("card-tour2_wide", "card-tour2_wide-card"));
    }

    function switchToRows() {
      btnCard.disabled = false;
      btnRow.disabled = true;
      section.classList.remove("tours-in2_cards");
      section.querySelectorAll(".card-tour2").forEach((c) => c.classList.replace("card-tour2_wide-card", "card-tour2_wide"));
    }

    function sectionSwithing(e) {
      if (e.target.closest(".tours-in2__switch_card")) {
        switchToCards();
        allCardsUpdate();
      } else if (e.target.closest(".tours-in2__switch_row")) {
        switchToRows();
        allCardsUpdate();
      }
    }
  })();

  const cardPackageInit = (() => {
    const classes = {
        block: "card-package",
        mods: {
          slider: "card-package_slider",
          _3: "card-package_img-grid",
          wideCard: "card-package_wide-card",
          wide: "card-package_wide",
        },
        slider: "card-package__img-part",
        list: "card-package__imgs",
        item: "card-package__imgs-item",
        markBtn: "card-package__mark-btn",
        markBtnCurrent: "_current",
        markedItems: "_marked",
        markedItemsHidden: "hidden",
      },
      pagination = {
        selector: ".card-package__gallery-pagination",
      };

    const section = document.querySelector(".tours-in2");
    const btnCard = section?.querySelector(".tours-in2__switch_card");
    const btnRow = section?.querySelector(".tours-in2__switch_row");
    const allCards = document.querySelectorAll(`.${classes.block}`);
    let swiperInstances = [];

    sectionInit();
    initTabs();
    allCards.forEach((c, i) => c.classList.add(`_card_${i}`));
    allCardsUpdate();

    window.addEventListener("resize", throttle(allCardsUpdate, 500));

    function allCardsUpdate() {
      allCards.forEach((block, i) => {
        const instanceClass = `_card_${i}`;
        const mainBlock = document.querySelector(`.${classes.block}.${instanceClass}`);
        const offClass = mainBlock.classList.contains(classes.mods.wideCard);
        const slides = mainBlock.querySelectorAll(`.${classes.item}`);
        const count = slides.length;
        const offCondition = WINDOW_WIDTH_INNER >= TABLET_WIDTH;
        mainBlock.classList.remove(classes.mods._3);
        if (count < 2) return;
        if (offCondition && offClass && count > 2) mainBlock.classList.add(classes.mods._3);
        if (offCondition && offClass && count > 3) slides[2].dataset.count = count - 3;

        const list = mainBlock.querySelector(`.${classes.list}`),
          items = mainBlock.querySelectorAll(`.${classes.item}`),
          sliderSettings = {
            wrapperClass: classes.list,
            slideClass: classes.item,
            speed: 400,
            spaceBetween: 0,

            observer: true,
            observeSlideChildren: true,
            observeParents: true,
            watchOverflow: true,

            followFinger: false,

            nested: () => {
              return mainBlock.closest(".swiper-initialized");
            },

            pagination: {
              el: pagination.selector,
              type: "bullets",
              clickable: true,
            },
          };

        listUpdate(mainBlock, i, list, items, sliderSettings, instanceClass);
      });
    }

    function listUpdate(mainBlock, i, list, items, sliderSettings, instanceClass) {
      let offClass = mainBlock.classList.contains(classes.mods._3);
      if (!offClass && swiperInstances[i] == undefined) {
        swiperInstances[i] = new Swiper(`.${instanceClass} .${classes.slider}`, sliderSettings);
        mainBlock.classList.add(classes.mods.slider);
      } else if (offClass && swiperInstances[i] !== undefined) {
        swiperInstances[i].destroy();
        swiperInstances[i] = undefined;
        list.removeAttribute("style");
        items.forEach((item) => item.removeAttribute("style"));
      }
    }

    function sectionInit() {
      if (!section || !btnCard || !btnRow) return;
      section.addEventListener("click", sectionSwithing);
    }

    function switchToCards() {
      btnCard.disabled = true;
      btnRow.disabled = false;
      section.classList.add("tours-in2_cards");
      section.querySelectorAll(`.${classes.block}`).forEach((c) => c.classList.replace(classes.mods.wide, classes.mods.wideCard));
    }

    function switchToRows() {
      btnCard.disabled = false;
      btnRow.disabled = true;
      section.classList.remove("tours-in2_cards");
      section.querySelectorAll(`.${classes.block}`).forEach((c) => c.classList.replace(classes.mods.wideCard, classes.mods.wide));
    }

    function sectionSwithing(e) {
      if (e.target.closest(".tours-in2__switch_card")) {
        switchToCards();
        allCardsUpdate();
      } else if (e.target.closest(".tours-in2__switch_row")) {
        switchToRows();
        allCardsUpdate();
      }
    }

    function initTabs() {
      document.body.addEventListener("click", (e) => {
        const btn = e.target.closest(`.${classes.markBtn}`);
        if (!btn) return;
        e.preventDefault();

        if (btn.classList.contains(classes.markBtnCurrent)) return;

        const block = btn.closest(`.${classes.block}`);
        if (!block) return;
        const btnSrc = btn.dataset.src;

        block.querySelectorAll(`[data-level]`).forEach((item) => (item.dataset.level = btnSrc));
        block
          .querySelectorAll(`.${classes.markedItems}:not(.${classes.markedItemsHidden})`)
          .forEach((item) => item.classList.add(classes.markedItemsHidden));
        block.querySelectorAll(`.${classes.markedItems}._${btnSrc}`).forEach((item) => item.classList.remove(classes.markedItemsHidden));

        block.querySelector(`.${classes.markBtn}.${classes.markBtnCurrent}`)?.classList.remove(classes.markBtnCurrent);
        btn.classList.add(classes.markBtnCurrent);
      });
    }
  })();

  const cities2 = (() => {
    document.addEventListener("click", (e) => {
      const block = e.target.closest(".cities2-wrap");
      const btnsList = block?.querySelector(".btns__list");
      if (!btnsList) return;

      const btn = e.target.closest("button");
      if (!btn || btn.disabled) return;

      btnsList.querySelectorAll("button").forEach((b) => {
        b.classList.remove("active");
        b.disabled = false;
      });
      btn.classList.add("active");
      btn.disabled = true;

      const filter = btn.dataset.filter;

      block.querySelectorAll(".cities2__item:not(.d-none)").forEach((item) => {
        if (filter === "visa-all") {
          item.classList.remove("hidden");
        } else if (filter === "visa-no") {
          item.classList.toggle("hidden", item.dataset.visa !== "no");
        }
      });

      block.querySelectorAll(".cities2-wrap__part").forEach((part) => {
        const visibleItems = part.querySelectorAll(".cities2__item:not(.hidden)");
        if (visibleItems.length === 0) {
          part.classList.add("hidden");
        } else {
          part.classList.remove("hidden");
        }
      });
    });
  })();

  if ($(".section-slider__list-wrap").length > 0) {
    let swiperInstances = [];
    $(".section-slider__list-wrap").each(function (index, element) {
      const $this = $(this);
      $this.addClass("section-slider___" + index);
      $this
        .parent()
        .find(".slider-btn2--prev")
        .addClass("prev-" + index);
      $this
        .parent()
        .find(".slider-btn2--next")
        .addClass("next-" + index);
      swiperInstances[index] = new Swiper(".section-slider___" + index, {
        wrapperClass: "section-slider__list",
        slideClass: "section-slider__item",
        speed: 400,
        spaceBetween: 20,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: 1,
        watchOverflow: true,
        loop: true,

        navigation: {
          prevEl: ".prev-" + index,
          nextEl: ".next-" + index,
        },

        on: {
          init: () => {
            replaceBtns($this);
            window.addEventListener(
              "resize",
              throttle(() => replaceBtns($this))
            );
          },
        },
      });
    });

    function replaceBtns($this) {
      const $prev = $this.closest(".section-slider").prev();
      if (!$prev.hasClass("section-slider-header")) return;

      const $buttons = $prev.parent().find(".slider-btns2");
      if (!$buttons.length) return;

      if (WINDOW_WIDTH_INNER < TABLET_WIDTH && $prev.has($buttons).length) $buttons.appendTo($this);
      else if (WINDOW_WIDTH_INNER >= TABLET_WIDTH && $this.has($buttons).length) $buttons.appendTo($prev);
    }
  }

  const swiperServiceLg = document.querySelector(".service.service--lg .swiper");

  if (swiperServiceLg) {
    const swiperGallery = new Swiper(".service.service--lg .swiper.service__list-wrap", {
      wrapperClass: "service__list",
      slideClass: "service__item",
      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      slidesPerView: "auto",
      spaceBetween: 12,
      watchOverflow: true,

      mousewheel: {
        releaseOnEdges: true,
      },
    });
  }

  (function () {
    var block = document.querySelector(".basement-tour");
    if (!block) return;
    new Swiper(".basement-tour__list-wrap", {
      wrapperClass: "basement-tour__list",
      slideClass: "basement-tour__item",
      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      watchOverflow: true,

      slidesPerView: "auto",
      spaceBetween: 20,

      // mousewheel: {
      //     releaseOnEdges: true,
      // }
      navigation: {
        nextEl: ".basement-tour .slider-btn2--next",
        prevEl: ".basement-tour .slider-btn2--prev",
      },

      on: {
        init: () => {
          function replaceBtns() {
            var section = block.closest(".basement__section");
            if (!section) return;
            var sectionHeader = section.querySelector(".basement__header");
            var btns = section.querySelector(".basement-tour__btns");
            var WIDTH_DESKTOP_MD = 1240;
            if (sectionHeader && WINDOW_WIDTH_INNER >= WIDTH_DESKTOP_MD && !sectionHeader.contains(btns)) {
              sectionHeader.append(btns);
            } else if (WINDOW_WIDTH_INNER < WIDTH_DESKTOP_MD && !block.contains(btns)) {
              block.append(btns);
            }
          }

          replaceBtns();
          window.addEventListener("resize", throttle(replaceBtns, 400));
        },
      },
    });
  })();

  const swiperServiceDef = document.querySelector(".service:not(.service--lg) .swiper");

  if (swiperServiceDef) {
    const swiperGallery = new Swiper(".service:not(.service--lg) .swiper.service__list-wrap", {
      wrapperClass: "service__list",
      slideClass: "service__item",
      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      slidesPerView: "auto",
      spaceBetween: 12,
      watchOverflow: true,
      mousewheel: {
        releaseOnEdges: true,
      },
      breakpoints: {
        1440: {
          spaceBetween: 20,
        },
      },
    });
  }

  const swiperNews = document.querySelector(".news");

  if (swiperNews) {
    const swiperGallery = new Swiper(".news__slider", {
      wrapperClass: "news__slider-inner",
      slideClass: "news__slide",
      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      slidesPerView: "auto",
      spaceBetween: 10,
      watchOverflow: true,

      breakpoints: {
        720: {
          spaceBetween: 20,
        },
      },
      navigation: {
        nextEl: ".news .slider-btn--next",
        prevEl: ".news .slider-btn--prev",
      },
    });
  }

  const basementPromo = document.querySelector(".basement-promo");
  if (basementPromo) {
    const swiperGallery = new Swiper(".basement-promo__list-wrap", {
      wrapperClass: "basement-promo__list",
      slideClass: "basement-promo__item",
      observer: true,
      observeSlideChildren: true,
      observeParents: true,
      slidesPerView: "auto",
      spaceBetween: 20,
      watchOverflow: true,
      navigation: {
        nextEl: ".basement-promo .slider-btn--next",
        prevEl: ".basement-promo .slider-btn--prev",
      },
      resistanceRatio: 0,
      freeMode: {
        enabled: true,
        momentumBounceRatio: 0,
      },
    });
  }

  /* basement-other - Мобильные - сетка, планшеты и больше - слайдер */
  const initBasementOther = () => {
    const dom = {
        blockClass: "basement-other",
        sliderClass: "basement-other__list-wrap",
        listClass: "basement-other__list",
        itemClass: "basement-other__item",
        itemVisibleClass: "_visible",
        nextBtnSelector: ".basement-other .slider-btn--next",
        prevBtnSelector: ".basement-other .slider-btn--prev",
        btnMoreWrapperClass: "basement-other__btn-wrap",
        btnMoreClass: "basement-other__btn",
      },
      blocks = document.querySelectorAll(`.${dom.blockClass}`);
    if (!blocks.length) return false;

    const getMainSettings = (slider) => {
      const list = slider.querySelector(`.${dom.listClass}`),
        items = slider.querySelectorAll(`.${dom.itemClass}`);

      if (!list || !items.length) return {};

      const sliderSettings = {
        wrapperClass: dom.listClass,
        slideClass: dom.itemClass,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 12,
        watchOverflow: true,
        navigation: {
          nextEl: dom.nextBtnSelector,
          prevEl: dom.prevBtnSelector,
        },
        resistanceRatio: 0,
        freeMode: {
          enabled: true,
          momentumBounceRatio: 0,
        },
      };

      return {
        list,
        items,
        sliderSettings,
      };
    };

    const btnMoreHandler = (block, items) => {
      const btn = block.querySelector(`button.${dom.btnMoreClass}`);
      btn?.addEventListener("click", function () {
        items.forEach((item) => item.classList.add(dom.itemVisibleClass));
        btn.closest(`.${dom.btnMoreWrapperClass}`)?.classList.add("hidden");
      });
    };

    const sliderUpdate = (sliderActive, i, sliderSettings, list, items) => {
      const screenWidth = window.innerWidth;
      if (screenWidth >= TABLET_WIDTH && !sliderActive) {
        sliderActive = new Swiper(`.${dom.sliderClass}._${i + 1}`, sliderSettings);
      } else if (screenWidth < TABLET_WIDTH && sliderActive) {
        sliderActive.destroy();
        sliderActive = null;
        list.removeAttribute("style");
        items.forEach((item) => item.removeAttribute("style"));
      }
      return sliderActive;
    };

    blocks.forEach((block, i) => {
      const slider = block.querySelector(`.${dom.sliderClass}`);
      if (!slider) return;

      slider.classList.add(`_${i + 1}`);

      let sliderActive = null;
      const { items, list, sliderSettings } = getMainSettings(slider);

      if (!items || !items.length || !list) return;
      btnMoreHandler(block, items);
      sliderActive = sliderUpdate(sliderActive, i, sliderSettings, list, items);
      window.addEventListener(
        "resize",
        throttle(() => {
          sliderActive = sliderUpdate(sliderActive, i, sliderSettings, list, items);
        }, 500)
      );
    });
  };
  initBasementOther();

  /* cards-grid - Мобильные - слайдер, планшеты и больше - сетка */
  initCardsGrid();

  function initCardsGrid() {
    const gridSmDom = {
        slider: "cards-grid__inner",
        list: "cards-grid__list",
        item: "cards-grid__item",
      },
      gridSm = document.querySelector(`.${gridSmDom.slider}`);

    if (gridSm === null) return false;

    const list = gridSm.querySelector(`.${gridSmDom.list}`),
      items = gridSm.querySelectorAll(`.${gridSmDom.item}`),
      gridSmGallerySettings = {
        wrapperClass: gridSmDom.list,
        slideClass: gridSmDom.item,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 16,
        watchOverflow: true,

        breakpoints: {
          740: {
            spaceBetween: 0,
          },
        },
      };

    let gridSmGallery = undefined;

    gridSmUpdate();
    window.addEventListener("resize", throttle(gridSmUpdate, 500));

    function gridSmUpdate() {
      let screenWidth = window.innerWidth;
      if (screenWidth < TABLET_WIDTH && gridSmGallery == undefined) {
        gridSmGallery = new Swiper("." + gridSmDom.slider, gridSmGallerySettings);
      } else if (screenWidth >= TABLET_WIDTH && gridSmGallery !== undefined) {
        gridSmGallery.destroy();
        gridSmGallery = undefined;
        list.removeAttribute("style");
        items.forEach((item) => item.removeAttribute("style"));
      }
    }
  }

  /* projects2 - Мобильные и планшеты - слайдер, десктопы - сетка */
  initProjects2Slider();

  function initProjects2Slider() {
    const classes = {
      slider: "projects2__inner",
      list: "projects2__grid",
      item: "projects2__item",
    };

    const blocks = document.querySelectorAll(`.${classes.slider}`);
    if (!blocks.length) return false;

    blocks.forEach((block, i) => {
      block.classList.add(`_i-${i}`);

      const list = block.querySelector(`.${classes.list}`);
      const items = block.querySelectorAll(`.${classes.item}`);
      const settings = {
        wrapperClass: classes.list,
        slideClass: classes.item,
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 10,
        watchOverflow: true,
        breakpoints: {
          980: { spaceBetween: 0 },
        },
      };

      let slider;

      const gSUpdate = () => {
        const screenWidth = window.innerWidth;
        if (screenWidth < DESKTOP_WIDTH && !slider) {
          slider = new Swiper(`.${classes.slider}._i-${i}`, settings);
        } else if (screenWidth >= DESKTOP_WIDTH && slider) {
          slider.destroy();
          slider = undefined;
          list.removeAttribute("style");
          items.forEach((item) => item.removeAttribute("style"));
        }
      };

      gSUpdate();
      window.addEventListener("resize", throttle(gSUpdate, 500));
    });
  }

  const toursMenuBtn = document.querySelector(".main-content__menu-btn") || document.querySelector(".main-content2__menu-btn");

  if (toursMenuBtn) {
    const toursCloseMenuBtn = document.querySelector(".tours-popup__menu-btn");
    const toursPopup = document.querySelector(".tours-popup");
    toursMenuBtn.addEventListener("click", () => {
      toursPopup.classList.add("active");
      lockScroll();
    });

    if (toursCloseMenuBtn) {
      toursCloseMenuBtn.addEventListener("click", () => {
        toursPopup.classList.remove("active");
        unLockScroll();
      });
    }
  }

  function lockScroll() {
    const body = document.querySelector("body");
    body.setAttribute("style", "overflow: hidden");
  }

  function unLockScroll() {
    const body = document.querySelector("body");
    body.removeAttribute("style");
  }

  // табы
  tabsActions(".section-tabs__switches button", ".section-tabs", ".section-tabs__bodies > li");
  tabsActions(".tabs-menu li > *", "body", ".tabs-list > *");

  function tabsActions(btns, parrent, body) {
    const tabsMenuBtns = document.querySelectorAll(btns);
    if (tabsMenuBtns.length > 0) {
      const tabsBodyItems = tabsMenuBtns[0].closest(parrent).querySelectorAll(body);
      tabsMenuBtns[0].classList.add("active");

      if (tabsBodyItems.length > 0) {
        tabsBodyItems[0].classList.add("active");
        for (let i = 0; i < tabsMenuBtns.length; i++) {
          tabsMenuBtns[i].addEventListener("click", function (e) {
            e.preventDefault();
            // this.classList.toggle;
            const bodyParent = tabsBodyItems[i].parentNode;
            const bodyParentAccordionBtnWrap = bodyParent.nextElementSibling;
            let bodyParentAccordionBtn = null;

            if (bodyParentAccordionBtnWrap !== null)
              bodyParentAccordionBtn = bodyParentAccordionBtnWrap.querySelector(".header-form__formclose-btn");

            if (!this.classList.contains("active")) {
              for (const i of tabsMenuBtns) {
                i.classList.remove("active");
              }
              this.classList.add("active");

              for (const item of tabsBodyItems) {
                item.classList.remove("active");
              }
              tabsBodyItems[i].classList.add("active");

              if (parseFloat(bodyParent.style.height) > 0) bodyParent.style.height = getComputedStyle(tabsBodyItems[i]).height;
              else if (bodyParentAccordionBtn !== null && window.innerWidth < TABLET_WIDTH) bodyParentAccordionBtn.click();

              if (bodyParent.classList.contains("header-form__container")) {
                bodyParent.classList.add("loading");
                setTimeout(() => {
                  bodyParent.classList.add("loaded");
                }, 100);
                setTimeout(() => {
                  bodyParent.classList.remove("loaded", "loading");
                }, 400);
              }
            } else if (
              this.classList.contains("btn--page") &&
              !bodyParent.classList.contains("active") &&
              window.innerWidth < TABLET_WIDTH
            ) {
              bodyParentAccordionBtn.click();
            }
          });
        }
      }
    }
  }

  /* btn-page actions */
  const btnPageActions = (() => {
    const parent = document.querySelector(".page__header-form");
    if (!parent) return;

    const btns = parent.querySelectorAll(".header-form__page-btns button.btn-page");
    const bodys = parent.querySelectorAll(".header-form__inner");
    if (!btns.length || !bodys.length) return;
    // Length check without console output

    const btnActiveClass = "_active";
    const bodyActiveClass = "active";

    const removeClassFromFirstInCollection = (cl, col) => {
      Array.from(col)
        .find((b) => b.classList.contains(cl))
        ?.classList.remove(cl);
    };

    if (![...btns].some((b) => b.classList.contains(btnActiveClass))) {
      btns[0].classList.add(btnActiveClass);
      removeClassFromFirstInCollection(bodyActiveClass, bodys);
      bodys[0].classList.add(bodyActiveClass);
    }

    for (let i = 0; i < btns.length; i++) {
      btns[i].addEventListener("click", function (e) {
        e.preventDefault();
        const bodyParent = bodys[i].closest(".header-form__container");
        const bodyOpenBtn = bodyParent.nextElementSibling?.querySelector(".header-form__formclose-btn");

        if (!this.classList.contains(btnActiveClass)) {
          removeClassFromFirstInCollection(btnActiveClass, btns);
          this.classList.add(btnActiveClass);
          removeClassFromFirstInCollection(bodyActiveClass, bodys);
          bodys[i].classList.add(bodyActiveClass);

          if (parseFloat(bodyParent.style.height) > 0) bodyParent.style.height = getComputedStyle(bodyParent.children[0]).height;
          else if (bodyOpenBtn && WINDOW_WIDTH_INNER < DESKTOP_WIDTH) bodyOpenBtn.click();

          bodyParent.classList.add("loading");
          setTimeout(() => {
            bodyParent.classList.add("loaded");
            setTimeout(() => {
              bodyParent.classList.remove("loaded", "loading");
            }, 300);
          }, 100);
        } else if (!bodyParent.classList.contains(bodyActiveClass) && WINDOW_WIDTH_INNER < DESKTOP_WIDTH) {
          bodyOpenBtn.click();
        }
      });
    }
  })();

  /* contacts2 actions */
  const contacts2Actions = (() => {
    const parent = document.querySelector(".contacts2");
    if (!parent) return;

    const btns = parent.querySelectorAll(".contacts2__btns button");
    const bodys = parent.querySelectorAll(".contacts2__left-item");
    if (!btns.length || !bodys.length) return;
    // Length check without console output

    const btnActiveClass = "active";
    const bodyActiveClass = "active";

    const removeClassFromFirstInCollection = (cl, col) => {
      Array.from(col)
        .find((b) => b.classList.contains(cl))
        ?.classList.remove(cl);
    };

    if (![...btns].some((b) => b.classList.contains(btnActiveClass))) {
      btns[0].classList.add(btnActiveClass);
      removeClassFromFirstInCollection(bodyActiveClass, bodys);
      bodys[0].classList.add(bodyActiveClass);
    }

    for (let i = 0; i < btns.length; i++) {
      btns[i].addEventListener("click", function (e) {
        e.preventDefault();
        const bodyParent = bodys[i].closest(".contacts2__body");
        const isRegions = e.target.closest("button").classList.contains("_regions");

        if (!this.classList.contains(btnActiveClass)) {
          let prev = Array.from(bodys).find((b) => b.classList.contains(bodyActiveClass));
          removeClassFromFirstInCollection(btnActiveClass, btns);
          removeClassFromFirstInCollection(bodyActiveClass, bodys);
          this.classList.add(btnActiveClass);
          bodys[i].classList.add(bodyActiveClass);

          if (c2map) {
            if (prev) {
              if (prev.classList.contains("_regions")) {
                for (let x in c2markers) {
                  if (c2markers[x]["r"]) {
                    c2markers[x]["m"].removeFrom(c2map);
                  }
                }
              } else c2markers[prev.getAttribute("data-id")]["m"].removeFrom(c2map);
            }
            if (isRegions) {
              let rm = [];
              for (let x in c2markers) {
                if (c2markers[x]["r"]) {
                  c2markers[x]["m"].addTo(c2map);
                  rm.push(c2markers[x]["m"]);
                }
              }
              c2map.getContainer().classList.add("visibility-hidden");
              setTimeout(() => {
                c2map.getContainer().classList.remove("visibility-hidden");
                c2map.invalidateSize(false).fitBounds(new L.featureGroup(rm).getBounds());
              }, 110);
            } else {
              c2markers[bodys[i].getAttribute("data-id")]["m"].addTo(c2map);
              c2map.setView(
                [bodys[i].getAttribute("data-lat"), bodys[i].getAttribute("data-lng")],
                bodys[i].getAttribute("data-zoom") > 0 ? bodys[i].getAttribute("data-zoom") : 14
              );
            }
          }

          bodyParent.classList.add("loading");
          setTimeout(() => {
            bodyParent.classList.add("loaded");
            parent.classList.toggle("_regions", isRegions);
            setTimeout(() => {
              bodyParent.classList.remove("loaded", "loading");
            }, 300);
          }, 100);
        }
      });
    }
  })();

  // переключение карточек js-slider2-tabs
  slider2Tabs(".js-slider2-tabs li > *", ".js-slider2-tabs-body", ".slider2__item");
  function slider2Tabs(_btns, _parrent, _item) {
    const btns = document.querySelectorAll(_btns);
    if (btns.length === 0) return false;

    const items = btns[0].closest(_parrent).querySelectorAll(_item);

    if (items.length === 0) return false;

    const swiper = items[0].closest(".swiper-initialized")?.swiper,
      slider2 = items[0].closest(".slider2"),
      btnParent = btns[0].closest(".js-slider2-tabs");

    btns[0].classList.add("active");

    btnParent.addEventListener("click", function (e) {
      const clickedBtn = e.target.closest(_btns);
      if (!clickedBtn || clickedBtn.classList.contains("active")) return;
      e.preventDefault();

      const activeBtn = btnParent.querySelector(".active"),
        btnIndex = Array.from(btns).indexOf(clickedBtn) + 1;

      slider2.classList.replace("slider2--vis", "slider2--invis");

      if (activeBtn) activeBtn.classList.remove("active");

      clickedBtn.classList.add("active");

      items.forEach((item) => {
        if (!item.classList.contains(`_${btnIndex}`)) item.classList.add("hidden");
        else item.classList.remove("hidden");
      });

      if (swiper) swiper.update();
      slider2.classList.replace("slider2--invis", "slider2--vis");
    });
  }

  // количество ночей
  const formItemNights = $(".form-item--nights");

  if (formItemNights.length > 0) {
    const inputs = $(".form-item--nights .form-item__input");

    $.each(inputs, function (index, input) {
      const inputValue = $(input).val();
      let dayArray = inputValue.split("-");
      let cleanDayArray = [];
      $.each(dayArray, function (index, day) {
        cleanDayArray.push(Number(day.trim()));
      });

      const btns = $(input).closest(".form-item").find(".nights__item-inner");

      if (cleanDayArray[0] < 1) cleanDayArray[0] = 1;
      if (cleanDayArray[1] > btns.length) cleanDayArray[1] = btns.length;

      for (let i = cleanDayArray[0] - 1; i <= cleanDayArray[1] - 1; i++) {
        btns[i].classList.add("nights__item-inner--active");
      }
    });

    for (const formItemNight of formItemNights) {
      const nightsItemBtns = formItemNight.querySelectorAll(".nights__item-inner");

      let activeOneIndex = -1;
      let activeTwoIndex = -1;
      let counter;

      function howMuchActive() {
        counter = 0;
        for (let i = 0; i < nightsItemBtns.length; i++) {
          if (nightsItemBtns[i].classList.contains("nights__item-inner--active")) {
            counter++;
          }
        }
      }

      for (let i = 0; i < nightsItemBtns.length; i++) {
        /*
                nightsItemBtns[i].addEventListener('click', function () {
                    howMuchActive();

                    if (counter == 0) {
                        activeOneIndex = this.textContent;
                        activeOneIndex = activeOneIndex.replace(/\s/g, "");
                        activeOneIndex = Number(activeOneIndex);
                    }

                    if (counter > 1) {
                        for (const btn of nightsItemBtns) {
                            btn.classList.remove('nights__item-inner--active');
                            activeOneIndex = this.textContent;
                            activeOneIndex = activeOneIndex.replace(/\s/g, "");
                            activeOneIndex = Number(activeOneIndex);
                            activeTwoIndex = -1;
                            const header = document.querySelector('.header');
                            header.classList.remove('active');
                        }
                        this.classList.add('nights__item-inner--active');
                    } else {
                        this.classList.add('nights__item-inner--active');
                        activeTwoIndex = this.textContent;
                        activeTwoIndex = activeTwoIndex.replace(/\s/g, "");
                        activeOneIndex = Number(activeOneIndex);
                    }

                    howMuchActive();


                    if (counter == 2 && activeOneIndex != -1 && activeTwoIndex != -1) {
                        if (activeTwoIndex < activeOneIndex) {
                            [activeTwoIndex, activeOneIndex] = [activeOneIndex, activeTwoIndex];
                        }

                        //результат тут
                        for (let index = activeOneIndex - 1; index < activeTwoIndex - 1; index++) {
                            nightsItemBtns[index].classList.add('nights__item-inner--active');
                        }

                        const headerForm = this.closest('.header-form');
                        headerForm.classList.remove('active');
                        unLockScroll();
                        const section = this.closest('section');
                        if (section) {
                            section.classList.remove('active');
                        }

                        let startDay = activeOneIndex;
                        let endDay = activeTwoIndex;

                        this.closest('.form-item').querySelector('.form-item__input').value = `${startDay}-${endDay}`
                        this.closest('.form-item').classList.remove('form-item--active');
                    }
                });
*/
        nightsItemBtns[i].addEventListener("mouseover", function () {
          howMuchActive();
          if (i < activeOneIndex && activeOneIndex != -1 && counter < 2) {
            for (let index = i; index < activeOneIndex; index++) {
              nightsItemBtns[index].classList.add("nights__item-inner--hover");
            }
          }
          if (i > activeOneIndex && activeOneIndex != -1 && counter < 2) {
            for (let index = activeOneIndex; index < i; index++) {
              nightsItemBtns[index].classList.add("nights__item-inner--hover");
            }
          }
        });

        nightsItemBtns[i].addEventListener("mouseout", function () {
          for (const item of nightsItemBtns) {
            item.classList.remove("nights__item-inner--hover");
          }
        });
      }
    }
  }

  // input-number
  let inputNumbers = document.querySelectorAll(".input-number");

  if (inputNumbers.length > 0) {
    const inputNumberClass = `input-number__input`;
    const inputPlusClass = `input-number__btn--plus`;
    const inputMinusClass = `input-number__btn--minus`;

    inputNumbers.forEach((item) => {
      let block = item;
      let input = block.querySelector(`.${inputNumberClass}`);
      let inputVal = +input.value;
      let inputMin = +input.dataset.min;
      let inputMax = +input.dataset.max;
      let plus = block.querySelector(`.${inputPlusClass}`);
      let minus = block.querySelector(`.${inputMinusClass}`);

      let plusHandler = function (evt) {
        evt.preventDefault();
        if (inputVal >= inputMax) return;
        inputVal = Math.min(inputVal + 1, inputMax);
        input.value = inputVal;
        input.dataset.value = inputVal;
        if (inputVal >= inputMin) minus.removeAttribute("disabled");
        if (inputVal >= inputMax) plus.setAttribute("disabled", true);
      };

      let minusHandler = function (evt) {
        evt.preventDefault();
        if (inputVal <= inputMin) return;
        inputVal = Math.max(inputVal - 1, inputMin);
        input.value = inputVal;
        input.dataset.value = inputVal;
        if (inputVal <= inputMin) minus.setAttribute("disabled", true);
        if (inputVal <= inputMax) plus.removeAttribute("disabled");
      };

      plus.addEventListener("click", plusHandler);
      minus.addEventListener("click", minusHandler);
    });
  }

  // select-input
  let searchBlocks = document.querySelectorAll(`.select-input`);

  if (searchBlocks.length > 0) {
    searchBlocks.forEach((item) => {
      let searchBlock = item;
      let searchInput = searchBlock.querySelector(`.select-input__input`);
      let searchBtn = searchBlock.querySelector(`.select-input__icon-wrap`);
      let searchItems = searchBlock.querySelectorAll(`.select-input__item`);

      let onSearchItemClick = function () {
        let item = this;
        let disabledButton = item.querySelector("button[disabled]");
        let linkWithoutHref = item.querySelector("a:not([href])");

        if (disabledButton || linkWithoutHref) return;

        let itemName = item.querySelector(".select-input__item-top");
        let itemNameText = itemName.textContent;
        let closestFilter = item.closest(".select-input");
        let closestInput = closestFilter.querySelector(".select-input__input");
        closestInput.value = itemNameText;
        searchItems.forEach((item) => item.classList.remove("select-input__item--active"));
        item.classList.add("select-input__item--active");
      };

      let showSearchOverlay = function () {
        searchBlocks.forEach((item) => item.classList.remove(`select-input--active`));
        searchBlock.classList.add(`select-input--active`);
      };

      let toggleSearchOverlay = function (e) {
        let thisSearch = e.target.closest(`.select-input`);
        let thisSearchIsActive = thisSearch.classList.contains(`select-input--active`);
        searchBlocks.forEach((item) => item.classList.remove(`select-input--active`));

        if (!thisSearchIsActive) {
          thisSearch.classList.add(`select-input--active`);
        } else {
          thisSearch.classList.remove(`select-input--active`);
        }
      };

      let onOutsideSearchOverlayClick = function (evt) {
        let e = evt.target;
        let isInsideSearchClick =
          (e.closest(".select-input .select-input__list-wrap") && !e.closest(".select-input .select-input__item-inner")) ||
          e.closest(".select-input__input") ||
          e.closest(".select-input__icon-wrap");

        if (isInsideSearchClick) {
          return;
        }

        searchBlock.classList.remove(`select-input--active`);
        evt.stopPropagation();
      };

      searchInput.addEventListener("focus", showSearchOverlay);
      searchInput.addEventListener("input", showSearchOverlay);
      searchBtn.addEventListener("click", toggleSearchOverlay);
      document.addEventListener("click", onOutsideSearchOverlayClick);
      searchItems.forEach((item) => item.addEventListener("click", onSearchItemClick));
    });
  }

  /* Элементы главной формы поиска туров */
  // init form-items
  let initFormItems = function () {
    const formItemActiveClass = `form-item--active`;
    let formItems = document.querySelectorAll(".form-item");
    if (formItems.length == 0) return;

    formItems.forEach((block) => {
      let blockInput = block.querySelector("input");
      let blockBtnToggle = block.querySelector(".form-item__btn");
      let blockBtnClear = block.querySelector(".form-item__btn-clear");
      let blockBodyCloseBtns = block.querySelectorAll(".form-item__body-btn--close");

      blockBodyCloseBtns.forEach((item) => {
        item.addEventListener("click", function (e) {
          e.target.closest(`.form-item`).classList.remove(formItemActiveClass);
          this.closest(".header-form").classList.remove("active");
          document.querySelector("header").classList.remove("active");
          unLockScroll();
        });
      });

      blockBtnClear?.addEventListener("click", function (e) {
        const input = this.closest(".form-item").querySelector("input");
        input.value = "";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.focus();
      });

      function checkInput() {
        let blockInputVal = blockInput.value;
        blockInputVal == false ? block.classList.add("form-item--empty") : block.classList.remove("form-item--empty");
      }

      function setActive() {
        closeActiveItems();
        block.classList.add("form-item--active");
        if (
          blockInput.classList.contains("js-search__input_where") &&
          blockInput.value &&
          blockInput.value === blockInput.getAttribute("data-name")
        ) {
          let blockInputVal = { name: blockInput.value, value: blockInput.getAttribute("data-value") };
          blockInput.setAttribute("data-empty", 1);
          blockInput.dispatchEvent(new Event("input", { bubbles: true }));
          setTimeout(() => {
            blockInput.setAttribute("data-name", blockInputVal["name"]);
            blockInput.setAttribute("data-value", blockInputVal["value"]);
            blockInput.setAttribute("data-return", 1);
            blockInput.removeAttribute("data-empty");
          }, 1);
        }
        // const header = document.querySelector('header');

        // if (header !== null) {
        //     header.classList.add('active');
        // }
      }

      function changeActive(e) {
        let thisFormItem = e.target.closest(`.form-item`);
        let thisFormItemIsActive = thisFormItem.classList.contains(formItemActiveClass);
        formItems.forEach((item) => item.classList.remove(formItemActiveClass));
        const headerFormBlock = this.closest(".header-form");
        // const header = document.querySelector('header');

        if (!thisFormItemIsActive) {
          thisFormItem.classList.add(formItemActiveClass);
          if (headerFormBlock !== null) {
            headerFormBlock.classList.add("active");
          }
          if (x < DESKTOP_WIDTH) {
            lockScroll();
          }
          if (block.classList.contains("form-item--nights")) {
            block.setAttribute("data-first", true);
          }

          // if (header !== null) {

          //     header.classList.add('active');
          // }
        } else {
          thisFormItem.classList.remove(formItemActiveClass);

          if (headerFormBlock !== null) {
            headerFormBlock.classList.remove("active");
          }

          // if (header !== null) {
          //     header.classList.remove('active');
          // }
          unLockScroll();
        }
      }

      function сlickOutside(evt) {
        let e = evt.target;
        let isInsideClick =
          e.closest(".form-item__body") ||
          e.closest("input") ||
          e.closest(".daterangepicker") ||
          e.closest(".available") ||
          e.closest(".form-item__body-btn") ||
          e.closest(".form-item__btn-clear") ||
          e.closest(".form-item__btn");

        if (isInsideClick) return;

        closeActiveItems(true);
        evt.stopPropagation();
      }

      window.addEventListener("load", checkInput);
      blockInput.addEventListener("focusin", checkInput);
      blockInput.addEventListener("focusout", checkInput);
      blockInput.addEventListener("input", checkInput);
      blockInput.addEventListener("focusin", setActive);
      blockBtnToggle.addEventListener("click", changeActive);
      document.addEventListener("click", сlickOutside);
    });
  };

  initFormItems();

  function closeActiveItems(blur = false) {
    let activeBlocks = document.querySelectorAll(".form-item--active");
    if (activeBlocks.length > 0) {
      activeBlocks.forEach((item) => {
        item.classList.remove("form-item--active");
        if (blur) {
          item.querySelector("input").blur();
          unLockScroll();

          const forms = document.querySelectorAll(".header-form");
          for (const form of forms) {
            form.classList.remove("active");
          }
        }

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      });
    }
  }

  // календарь
  // !!! #header-form-dates для обратной совместимости, потом его удалить !!!
  let calendarInput = $("#header-form-dates");
  const formCalendarPropsLocale = {
      format: "DD.MM",
      applyLabel: "Применить",
      cancelLabel: "Отмена",
      fromLabel: "С",
      daysOfWeek: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
      monthNames: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
      monthNamesShort: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
      firstDay: 1,
    },
    formCalendarProps = {
      "header-form-dates": {
        parentEl: ".page__header-form .form-item--calendar .form-item__body",
        opens: "center",
        autoApply: true,
        minDate: moment(),
        startDate: moment(calendarInput.attr("data-start"), "YYMMDD"),
        endDate: moment(calendarInput.attr("data-end"), "YYMMDD"),
        locale: formCalendarPropsLocale,
        isInvalidDate: function (date) {
          let list = [""],
            cur = moment(date).format("YYMMDD"),
            x,
            t;
          for (x = this.container.length - 1; x >= 0; x--) {
            t = this.container[x].closest(".form-item--calendar").querySelector("#header-form-dates");
            if (t) {
              list = (t.getAttribute("data-list") || "").split(",");
              break;
            }
          }
          for (x = list.length - 1; x >= 0; x--) {
            if (list[x] == cur) return false;
          }
          return true;
        },
      },
      "header-form-dates-tours": {
        parentEl: ".page__header-form .form-item--calendar .form-item__body",
        opens: "center",
        autoApply: true,
        minDate: moment(),
        startDate: moment(calendarInput.attr("data-start"), "YYMMDD"),
        endDate: moment(calendarInput.attr("data-end"), "YYMMDD"),
        locale: formCalendarPropsLocale,
        isInvalidDate: function (date) {
          let list = [""],
            cur = moment(date).format("YYMMDD"),
            x,
            t;
          for (x = this.container.length - 1; x >= 0; x--) {
            t = this.container[x].closest(".form-item--calendar").querySelector("#header-form-dates-tours");
            if (t) {
              list = (t.getAttribute("data-list") || "").split(",");
              break;
            }
          }
          for (x = list.length - 1; x >= 0; x--) {
            if (list[x] == cur) return false;
          }
          return true;
        },
      },
      "header-form-dates-hotels": {
        parentEl: ".page__header-form .form-item--calendar .form-item__body",
        opens: "center",
        autoApply: true,
        minDate: moment(),
        startDate: moment(calendarInput.attr("data-start"), "YYMMDD"),
        endDate: moment(calendarInput.attr("data-end"), "YYMMDD"),
        locale: formCalendarPropsLocale,
        isInvalidDate: function (date) {
          let list = [""],
            cur = moment(date).format("YYMMDD"),
            x,
            t;
          for (x = this.container.length - 1; x >= 0; x--) {
            t = this.container[x].closest(".form-item--calendar").querySelector("#header-form-dates-hotels");
            if (t) {
              list = (t.getAttribute("data-list") || "").split(",");
              break;
            }
          }
          for (x = list.length - 1; x >= 0; x--) {
            if (list[x] == cur) return false;
          }
          return true;
        },
      },
      "header-form-dates-excursions": {
        parentEl: ".page__header-form .form-item--calendar .form-item__body",
        opens: "center",
        autoApply: true,
        minDate: moment(),
        startDate: moment(calendarInput.attr("data-start"), "YYMMDD"),
        endDate: moment(calendarInput.attr("data-end"), "YYMMDD"),
        locale: formCalendarPropsLocale,
        isInvalidDate: function (date) {
          let list = [""],
            cur = moment(date).format("YYMMDD"),
            x,
            t;
          for (x = this.container.length - 1; x >= 0; x--) {
            t = this.container[x].closest(".form-item--calendar").querySelector("#header-form-dates-excursions");
            if (t) {
              list = (t.getAttribute("data-list") || "").split(",");
              break;
            }
          }
          for (x = list.length - 1; x >= 0; x--) {
            if (list[x] == cur) return false;
          }
          return true;
        },
      },
      "hotel-form-dates": {
        parentEl: ".header-form--light .form-item--calendar .form-item__body",
        opens: "center",
        autoApply: true,
        minDate: moment(),
        startDate: moment(),
        endDate: moment(),
        locale: formCalendarPropsLocale,
        isInvalidDate: function (date) {
          let list = (
              this.container[0].closest(".form-item--calendar").querySelector("#hotel-form-dates").getAttribute("data-list") || ""
            ).split(","),
            cur = moment(date).format("YYMMDD"),
            x;
          for (x = list.length - 1; x >= 0; x--) {
            if (list[x] == cur) return false;
          }
          return true;
        },
      },
    };

  var filterBar3Calendar = () => {
    let calendarInput = $("#filter-bar3-calendar"),
      filderDates = calendarInput.attr("data-list"),
      val = calendarInput.val();
    filderDates = filderDates ? filderDates.split(",") : [];
    val = val ? val.split(" - ") : "";
    if (!filderDates.length && !val) return;
    const formCalendarProps = {
      parentEl: ".filter-bar3__item_calendar .filter-bar3__menu",
      opens: "center",
      autoApply: true,
      //            singleDatePicker: true,
      //            alwaysShowCalendars: true,
      minDate: moment(),
      maxDate: filderDates ? moment(filderDates[filderDates.length - 1], "YYMMDD") : "",
      startDate: val ? moment(val[0], "DD.MM") : filderDates ? moment(filderDates[0], "YYMMDD") : "",
      endDate: val ? moment(val[1], "DD.MM") : filderDates ? moment(filderDates[filderDates.length - 1], "YYMMDD") : "",
      locale: formCalendarPropsLocale,
      isInvalidDate: function (date) {
        let cur = moment(date).format("YYMMDD"),
          x,
          t;
        for (x = filderDates.length - 1; x >= 0; x--) {
          if (filderDates[x] == cur) return false;
        }
        return true;
      },
    };

    calendarInput.daterangepicker(formCalendarProps);

    calendarInput.on({
      "apply.daterangepicker": function (evt, picker) {
        picker.element.closest(".filter-bar3__item").find(".filter-bar3__btn").click();
      },
      change: function () {
        let $this = $(this),
          picker = $this.data("daterangepicker");
        if (picker) {
          tour_search_get_params["date_from"] = picker.startDate.format("YYYY-MM-DD");
          tour_search_get_params["date_to"] = picker.endDate.format("YYYY-MM-DD");

          tourFilterHref();
        }
      },
    });

    document.addEventListener("click", (e) => {
      var a = e.target.closest(".filter-bar3__item_calendar .filter-bar3__btn");
      if (a && a.classList.contains("active")) {
        a.parentNode.querySelector("input")?.focus();
      }
    });
  };
  filterBar3Calendar();

  if (calendarInput.length) {
    calendarInput.daterangepicker(formCalendarProps["header-form-dates"]);

    calendarInput.on({
      "apply.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      "hideCalendar.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      change: function () {
        let $this = $(this),
          picker = $this.data("daterangepicker");
        if (picker) {
          $this.val(picker.startDate.format("DD.MM") + " - " + picker.endDate.format("DD.MM"));
          $this.attr("data-start", picker.startDate.format("YYMMDD"));
          $this.attr("data-end", picker.endDate.format("YYMMDD"));
          $this.closest(".form-item--active").removeClass("form-item--active");
          closePopups();
        }
      },
    });
  }

  calendarInput = $("#header-form-dates-tours");

  if (calendarInput.length) {
    formCalendarProps["header-form-dates-tours"]["startDate"] = moment(calendarInput.attr("data-start"), "YYMMDD");
    formCalendarProps["header-form-dates-tours"]["endDate"] = moment(calendarInput.attr("data-end"), "YYMMDD");

    calendarInput.daterangepicker(formCalendarProps["header-form-dates-tours"]);

    calendarInput.on({
      "apply.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      "hideCalendar.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      change: function () {
        let $this = $(this),
          picker = $this.data("daterangepicker");
        if (picker) {
          $this.val(picker.startDate.format("DD.MM") + " - " + picker.endDate.format("DD.MM"));
          $this.attr("data-start", picker.startDate.format("YYMMDD"));
          $this.attr("data-end", picker.endDate.format("YYMMDD"));
          if ($this.closest(".form-item--active").length) {
            $this.closest(".form-item--active").removeClass("form-item--active");
            closePopups();
          }
        }
      },
    });
  }

  calendarInput = $("#header-form-dates-hotels");

  if (calendarInput.length) {
    formCalendarProps["header-form-dates-hotels"]["startDate"] = moment(calendarInput.attr("data-start"), "YYMMDD");
    formCalendarProps["header-form-dates-hotels"]["endDate"] = moment(calendarInput.attr("data-end"), "YYMMDD");

    calendarInput.daterangepicker(formCalendarProps["header-form-dates-hotels"]);

    calendarInput.on({
      "apply.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      "hideCalendar.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      change: function () {
        let $this = $(this),
          picker = $this.data("daterangepicker");
        if (picker) {
          $this.val(picker.startDate.format("DD.MM") + " - " + picker.endDate.format("DD.MM"));
          $this.attr("data-start", picker.startDate.format("YYMMDD"));
          $this.attr("data-end", picker.endDate.format("YYMMDD"));
          $this.closest(".form-item--active").removeClass("form-item--active");
          closePopups();
        }
      },
    });
  }

  calendarInput = $("#header-form-dates-excursions");

  if (calendarInput.length) {
    formCalendarProps["header-form-dates-excursions"]["startDate"] = moment(calendarInput.attr("data-start"), "YYMMDD");
    formCalendarProps["header-form-dates-excursions"]["endDate"] = moment(calendarInput.attr("data-end"), "YYMMDD");

    calendarInput.daterangepicker(formCalendarProps["header-form-dates-excursions"]);

    calendarInput.on({
      "apply.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      "hideCalendar.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      change: function () {
        let $this = $(this),
          picker = $this.data("daterangepicker");
        if (picker) {
          $this.val(picker.startDate.format("DD.MM") + " - " + picker.endDate.format("DD.MM"));
          $this.attr("data-start", picker.startDate.format("YYMMDD"));
          $this.attr("data-end", picker.endDate.format("YYMMDD"));
          $this.closest(".form-item--active").removeClass("form-item--active");
          closePopups();
        }
      },
    });
  }

  calendarInput = $("#hotel-form-dates");

  if (calendarInput.length) {
    formCalendarProps["hotel-form-dates"]["startDate"] = moment(calendarInput.attr("data-start"), "YYMMDD");
    formCalendarProps["hotel-form-dates"]["endDate"] = moment(calendarInput.attr("data-end"), "YYMMDD");

    calendarInput.daterangepicker(formCalendarProps["hotel-form-dates"]);

    calendarInput.on({
      "apply.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      "hideCalendar.daterangepicker": function (evt, picker) {
        $(this).closest(".form-item").removeClass(".form-item--active");

        const formActive = this.closest(".header-form");
        formActive.classList.remove("active");
        unLockScroll();

        const header = document.querySelector("header");
        if (header) {
          header.classList.remove("active");
        }
      },
      change: function () {
        let $this = $(this),
          picker = $this.data("daterangepicker");
        if (picker) {
          $this.val(picker.startDate.format("DD.MM") + " - " + picker.endDate.format("DD.MM"));
          $this.attr("data-start", picker.startDate.format("YYMMDD"));
          $this.attr("data-end", picker.endDate.format("YYMMDD"));
          $this.closest(".form-item--active").removeClass("form-item--active");
        }
      },
    });
  }

  const calendarBtns = document.querySelectorAll(".form-item--calendar .form-item__btn");

  for (const calendarBtn of calendarBtns) {
    calendarBtn.addEventListener("click", function () {
      setTimeout(() => {
        if (calendarBtn.closest(".form-item").classList.contains("form-item--active")) {
          const label = this.closest(".form-item").querySelector(".form-item__wrap");
          label.click();

          // const header = document.querySelector('.header');
          // if (header) {
          //     header.classList.add('active');
          // }

          if (x < 980) {
            const formActive = this.closest(".header-form");
            formActive.classList.add("active");
            lockScroll();
          }
        }
      }, 200);
    });
  }

  // высота хеадера либо другого элемента
  function calcHeight(el = ".header") {
    const elHeight = document.querySelector(el).offsetHeight;
    return elHeight;
  }

  /* filter-bar3 item search */
  (function filterBar3ItemSearch() {
    document.querySelectorAll(".filter-bar3__item_input-search").forEach((el) => {
      const parent = el;
      const btn = el.querySelector(".filter-bar3__btn");
      const btnImg = btn.querySelector(".filter-bar3__flag");
      const btnClear = el.querySelector(".filter-bar3__clear");
      const input = el.querySelector(".filter-bar3__input");
      const list = el.querySelector(".form-drop-list3__list");

      checkInputVal(input, parent);

      btnClear.addEventListener("click", (e) => {
        e.preventDefault();
        input.value = "";
        if (btnImg) btnImg.src = "";
        input.dispatchEvent(new Event("input"));
        input.focus();
        //console.log(e.target.closest(''))
        //btn.click();
        return false;
      });

      input.addEventListener("input", (e) => {
        checkInputVal(input, parent);
      });

      input.addEventListener("focusin", (e) => {
        if (!btn.classList.contains("active")) btn.click();
        let sel = list.querySelector(".form-drop-list3__link.active");
        if (sel && input.value == sel.textContent.trim()) input.value = "";
      });

      input.addEventListener("focusout", (e) => {
        if (!input.value) input.value = input.getAttribute("data-default") || "";
      });

      // input.addEventListener('focusout', (e) => {
      //     if (e.relatedTarget && parent.contains(e.relatedTarget)) return;
      //     if (btn.classList.contains('active')) btn.click();
      // });

      parent.addEventListener("click", (e) => {
        checkInputVal(input, parent);
      });

      // parent.addEventListener('focusout', (e) => {
      //     const newFocusTarget = e.relatedTarget;
      //     if (!newFocusTarget || !parent.contains(newFocusTarget)) {
      //         setTimeout(() => {
      //             if (btn.classList.contains('active')) btn.click();
      //         }, 100);
      //     }
      // });

      function checkInputVal(input, block) {
        let val = input.value.toLowerCase().trim();
        val === "" ? block.classList.add("filter-bar3__item_input-empty") : block.classList.remove("filter-bar3__item_input-empty");
      }
    });
  })();

  // клик по кнопке поворот стрелки и вызов меню dropdown2
  selectDropMenu2({
    openBtnSelector: ".filter-bar3__btn",
    parentSelector: ".filter-bar3__dropdown",
    dropMenuSelector: ".filter-bar3__menu",
    dropMenuBtnSelector: ".filter-bar3__menu .form-drop-list3__link",
    focus: false,
  });
  selectDropMenu2({
    openBtnSelector: ".search-input2__btn",
    parentSelector: ".search-input2",
    dropMenuSelector: ".search-input2__form-drop-list",
    dropMenuBtnSelector: ".search-input2__form-drop-list a[href]",
    focus: true,
  });

  const url_tour_search = new URL(window.location.href),
    tourFilterHref = () => {
      let qs = new URLSearchParams(Object.entries(tour_search_get_params).reduce((a, [k, v]) => (v ? ((a[k] = v), a) : a), {})).toString();

      window.location.href =
        document.querySelector(".filter-bar3__reset").href +
        Object.entries(tour_search_url_params).reduce((a, [k, v]) => (v ? a + k + "-" + v + "/" : a), "") +
        (qs ? "?" + qs : "");
    };

  let tour_search_url_params = {
      type: document.querySelector(".filter-bar3 input[data-default-type]"),
      disease: document.querySelector(".filter-bar3 input[data-default-disease]"),
      country: document.querySelector(".filter-bar3 input[data-default-country]"),
      region: document.querySelector(".filter-bar3 input[data-default-region]"),
      city: document.querySelector(".filter-bar3 input[data-default-city]"),
    },
    tour_search_get_params = {};
  if (document.querySelector(".filter-bar3.js-filter-tours")) {
    tour_search_get_params = {
      //            country_id: url_tour_search.searchParams.get('country_id'),
      //            resort_id: url_tour_search.searchParams.get('resort_id'),
      recipient_id: url_tour_search.searchParams.get("recipient_id"),
      //            type_id: url_tour_search.searchParams.get('type_id'),
      category_id: url_tour_search.searchParams.get("category_id"),
      level_id: url_tour_search.searchParams.get("level_id"),
      date_from: url_tour_search.searchParams.get("date_from"),
      date_to: url_tour_search.searchParams.get("date_to"),
    };
  } else if (document.querySelector(".filter-bar3.js-filter-diseases")) {
    tour_search_get_params = {
      //            country_id: url_tour_search.searchParams.get('country_id'),
      //            resort_id: url_tour_search.searchParams.get('resort_id'),
      recipient_id: url_tour_search.searchParams.get("recipient_id"),
      type_id: url_tour_search.searchParams.get("type_id"),
      category_id: url_tour_search.searchParams.get("category_id"),
      level_id: url_tour_search.searchParams.get("level_id"),
      date_from: url_tour_search.searchParams.get("date_from"),
      date_to: url_tour_search.searchParams.get("date_to"),
    };
  } else {
    tour_search_get_params = {
      country_id: url_tour_search.searchParams.get("country_id"),
      resort_id: url_tour_search.searchParams.get("resort_id"),
      recipient_id: url_tour_search.searchParams.get("recipient_id"),
      type_id: url_tour_search.searchParams.get("type_id"),
      category_id: url_tour_search.searchParams.get("category_id"),
      level_id: url_tour_search.searchParams.get("level_id"),
      date_from: url_tour_search.searchParams.get("date_from"),
      date_to: url_tour_search.searchParams.get("date_to"),
    };
  }
  tour_search_url_params["type"] =
    tour_search_url_params["type"] && !tour_search_url_params["type"].disabled
      ? tour_search_url_params["type"].getAttribute("data-default-type")
      : "";
  tour_search_url_params["disease"] =
    tour_search_url_params["disease"] && !tour_search_url_params["disease"].disabled
      ? tour_search_url_params["disease"].getAttribute("data-default-disease")
      : "";
  tour_search_url_params["country"] =
    tour_search_url_params["country"] && !tour_search_url_params["country"].disabled
      ? tour_search_url_params["country"].getAttribute("data-default-country")
      : "";
  tour_search_url_params["region"] =
    tour_search_url_params["region"] && !tour_search_url_params["region"].disabled
      ? tour_search_url_params["region"].getAttribute("data-default-region")
      : "";
  tour_search_url_params["city"] =
    tour_search_url_params["city"] && !tour_search_url_params["city"].disabled
      ? tour_search_url_params["city"].getAttribute("data-default-city")
      : "";

  function selectDropMenu2({ openBtnSelector, parentSelector, dropMenuSelector, dropMenuBtnSelector, focus = false }) {
    const parents = document.querySelectorAll(parentSelector);
    if (parents.length === 0) return;

    const removeActiveFromAll = () => {
      document.querySelectorAll(`${openBtnSelector}.active`).forEach((btn) => btn.classList.remove("active"));
      document.querySelectorAll(`${dropMenuSelector}.active`).forEach((menu) => menu.classList.remove("active"));
      if (focus) {
        document.querySelectorAll(`${parentSelector} input.active`).forEach((input) => input.classList.remove("active"));
      }
    };

    document.addEventListener("click", (e) => {
      const target = e.target;

      if (
        !target.closest(openBtnSelector) &&
        !target.closest(dropMenuSelector) &&
        !target.closest(parentSelector) &&
        (!focus || !target.closest(parentSelector)) &&
        !target.closest(".next") &&
        !target.closest(".prev")
      ) {
        removeActiveFromAll();
      }
    });

    parents.forEach((parent) => {
      parent.addEventListener("click", (e) => {
        const target = e.target;

        const menuBtn = target.closest(dropMenuBtnSelector);
        const menuBtnImg = menuBtn?.querySelector("img");
        const input = parent.querySelector("input");
        const openBtn = parent.querySelector(openBtnSelector);
        const img = openBtn.querySelector("img");
        const menu = parent.querySelector(dropMenuSelector);

        if (openBtn === target || openBtn.contains(target)) {
          const isActive = openBtn.classList.contains("active");
          removeActiveFromAll();
          openBtn.classList.toggle("active", !isActive);
          menu?.classList.toggle("active", !isActive);
          if (focus && input) input.classList.toggle("active", !isActive);
          return;
        }

        if (menuBtn) {
          if (menuBtn.hasAttribute("href")) {
            const href = menuBtn.getAttribute("href");
            if (href.trim() !== "" && href !== "./" && href[0] !== "#") return;
          }

          const jsArc = menuBtn.closest(".js-currencies-archive");

          if (!jsArc && typeof menuBtn.dataset !== "undefined") {
            if (typeof menuBtn.dataset.resort_id !== "undefined") {
              if (typeof tour_search_get_params["resort_id"] == "undefined") {
                tour_search_url_params["region"] = tour_search_url_params["city"] = "";
                tour_search_url_params[menuBtn.dataset.param_name] = menuBtn.dataset.resort_alias;
              } else {
                tour_search_get_params["resort_id"] = menuBtn.dataset.resort_id;
              }
            }

            if (typeof menuBtn.dataset.country_id !== "undefined") {
              if (typeof tour_search_get_params["country_id"] == "undefined") {
                if (tour_search_url_params["country"] !== menuBtn.dataset.country_alias) {
                  if (typeof tour_search_get_params["resort_id"] == "undefined") {
                    tour_search_url_params["region"] = tour_search_url_params["city"] = "";
                  } else {
                    tour_search_get_params["resort_id"] = "";
                  }
                }

                tour_search_url_params["country"] = menuBtn.dataset.country_alias;
              } else {
                if (tour_search_get_params["country_id"] !== menuBtn.dataset.country_id) {
                  if (typeof tour_search_get_params["resort_id"] == "undefined") {
                    tour_search_url_params["region"] = tour_search_url_params["city"] = "";
                  } else {
                    tour_search_get_params["resort_id"] = "";
                  }
                }

                tour_search_get_params["country_id"] = menuBtn.dataset.country_id;
              }
            }
            if (typeof menuBtn.dataset.type_id !== "undefined") {
              if (typeof tour_search_get_params["type_id"] == "undefined") {
                tour_search_url_params["type"] = menuBtn.dataset.type_alias;
              } else {
                tour_search_get_params["type_id"] = menuBtn.dataset.type_id;
              }
            }

            if (typeof menuBtn.dataset.disease_id !== "undefined") tour_search_url_params["disease"] = menuBtn.dataset.disease_alias;

            if (typeof menuBtn.dataset.recipient_id !== "undefined") tour_search_get_params["recipient_id"] = menuBtn.dataset.recipient_id;

            if (typeof menuBtn.dataset.level_id !== "undefined") tour_search_get_params["level_id"] = menuBtn.dataset.level_id;

            if (typeof menuBtn.dataset.category_id !== "undefined") tour_search_get_params["category_id"] = menuBtn.dataset.category_id;

            tourFilterHref();
          }

          e.preventDefault();
          menu.querySelectorAll(".active").forEach((btn) => btn.classList.remove("active"));
          menuBtn.classList.add("active");
          input.value = menuBtn.textContent.trim();
          input.setAttribute("data-value", menuBtn.getAttribute("data-id"));
          if (img && menuBtnImg) {
            /* ios fix */
            img.src = "";
            img.src = menuBtnImg.src;
          }
          openBtn.classList.remove("active");
          menu.classList.remove("active");
          if (focus && input) input.classList.remove("active");

          if (jsArc) {
            let filterBar = menuBtn.closest(".filter-bar3"),
              monthValue = filterBar.querySelector('input[name="month"]').getAttribute("data-value"),
              baseValue = filterBar.querySelector('input[name="base"]').getAttribute("data-value");

            if (input && input.name == "month") {
              location.href = "?" + (monthValue ? "month=" + monthValue + "&" : "") + "base=" + baseValue;
              return;
            }

            $(".js-currencies-archive-list table th:eq(3)").text(baseValue == "RUB" ? "EURO/USD" : "1 RUB");

            jsArc.querySelectorAll(`.js-currencies-archive-list table`).forEach((table) => {
              const el = table.getAttribute("data-base") ? table : table.closest("[data-base]");
              if (el && el.getAttribute("data-base")) {
                if (el.getAttribute("data-base") == baseValue) {
                  el.classList.remove("hidden");
                  if (!el.classList.length) {
                    el.removeAttribute("class");
                  }
                  if (typeof window["drawChart" + baseValue] == "function") window["drawChart" + baseValue]();
                } else {
                  el.classList.add("hidden");
                }
              }
            });
            history.pushState(null, "", "?month=" + monthValue + "&base=" + baseValue);
          }

          return;
        }
      });
    });

    if (focus) {
      parents.forEach((parent) => {
        const input = parent.querySelector("input");
        const openBtn = parent.querySelector(openBtnSelector);
        const menu = parent.querySelector(dropMenuSelector);

        if (input && openBtn && menu) {
          input.addEventListener("focus", () => {
            removeActiveFromAll();

            input.classList.add("active");
            openBtn.classList.add("active");
            menu.classList.add("active");

            if (input.closest(".js-resorts-search-form") && !resortsSearchList.length) {
              let list = $(".city-list__list .city-list__link");
              if (!list.length) list = $(".hotels-list2__list .hotels-list2__link");
              if (!list.length) list = $(".hotel3-grid__list .card-hotel3__info-title a");
              list.each(function () {
                resortsSearchList.push([$.trim(this.textContent.toLowerCase()), this.href, this.innerHTML]);
              });
              if (resortsSearchList.length > 1)
                resortsSearchList.sort(function (a, b) {
                  return a[0] > b[0] ? 1 : a[0] < b[0] ? -1 : 0;
                });
            }
          });

          input.addEventListener("click", () => {
            if (!menu.classList.contains("active")) {
              removeActiveFromAll();
              input.classList.add("active");
              openBtn.classList.add("active");
              menu.classList.add("active");
            }
          });

          input.addEventListener("input", () => {
            let parrent = input.closest(".filter-bar3__item"),
              message = parrent.querySelector(".form-drop-list3__no-result");
            const inputWords = multiStr(input.value.toLowerCase());

            message.classList.add("d-none");

            if (input.closest(".js-resorts-search-form")) {
              let list = parrent.querySelectorAll(".form-drop-list3__list .form-drop-list3__link"),
                $templateItem = false,
                $templateLink,
                $listBlock;
              $.each(list, function (index, value) {
                if (value.parentNode.classList.contains("js-template")) {
                  $templateItem = $(value.parentNode).clone().removeClass("js-template hidden");
                  $templateLink = $templateItem.find(".form-drop-list3__link");
                  $listBlock = $(value.parentNode.parentNode);
                } else if (value.parentNode.classList.contains("js-default")) {
                  if (inputWords[0].length < 3) {
                    value.parentNode.classList.remove("d-none");
                  } else {
                    value.parentNode.classList.add("d-none");
                  }
                } else {
                  $(value.parentNode).remove();
                }
              });
              if (inputWords[0].length < 3) {
                return;
              }
              let count = 0,
                x;
              if ($templateItem.length && resortsSearchList.length) {
                for (x = 0; x < resortsSearchList.length; x++) {
                  if (
                    resortsSearchList[x][0].includes(inputWords[0]) ||
                    (inputWords[1] && resortsSearchList[x][0].includes(inputWords[1]))
                  ) {
                    $templateLink.attr("href", resortsSearchList[x][1]).html(resortsSearchList[x][2]);
                    $templateItem.clone().appendTo($listBlock);
                    count++;
                  }
                }
              }
              if (!count) {
                message.classList.remove("d-none");
              }
            } else {
              let list = parrent.querySelectorAll(".form-drop-list3__list li .form-drop-list3__link"),
                txt;

              $.each(list, function (index, value) {
                if (inputWords[0].length < 3) {
                  if (value.parentNode.classList.contains("js-default")) {
                    value.parentNode.classList.remove("d-none");
                  } else {
                    value.parentNode.classList.add("d-none");
                  }
                } else {
                  if (value.parentNode.classList.contains("js-default")) {
                    value.parentNode.classList.add("d-none");
                  } else {
                    txt = (value.textContent || value.innerText || value.innerHTML).toLowerCase();
                    if (txt.includes(inputWords[0]) || (inputWords[1] && txt.includes(inputWords[1]))) {
                      value.parentNode.classList.remove("d-none");
                    } else {
                      value.parentNode.classList.add("d-none");
                    }
                  }
                }
              });
              if (inputWords[0].length < 3) {
                return;
              }
              if (parrent.querySelectorAll(".form-drop-list3__list li.d-none").length == list.length) {
                message.classList.remove("d-none");
              }
            }
          });

          if (input.value) input.dispatchEvent(new Event("input", { bubbles: true }));
        }
      });
    }
  }

  // клик по кнопке поворот стрелки и вызов меню dropdown
  selectDropMenu(".filter-bar__btn", ".filter-bar__dropdown", ".filter-bar__menu", ".filter-bar__menu .form-drop-list__link");

  selectDropMenu(
    ".form-select__btn",
    ".form-select",
    ".form-select__form-drop-list",
    ".form-select__form-drop-list .form-drop-list__link",
    true
  );

  selectDropMenu(".search-input__input-btn", ".search-input", ".search-input__form-drop-list", ".search-input__form-drop-list a", true);

  function selectDropMenu(openBtn, parrent, dropMenu, dropMenuBtn, focus = false) {
    const filterBtns = document.querySelectorAll(openBtn),
      dropMenuButtons = document.querySelectorAll(dropMenuBtn),
      dropMenus = document.querySelectorAll(dropMenu);

    if (dropMenus.length > 0) {
      document.addEventListener("click", function (e) {
        const target = e.target;
        let itsMenu;
        let menuIsActive;
        let itsBtnMenu;
        let itsInput;
        let itsInputFocus;

        const labels = document.querySelectorAll(`${parrent} label`);

        if (focus) {
          for (const i of labels) {
            itsInput = target == i || i.contains(target);

            if (itsInput) {
              itsInputFocus = true;
            }
          }
        }

        for (const menu of dropMenus) {
          itsMenu = target == menu || menu.contains(target);

          if (menu.classList.contains("active")) {
            menuIsActive = true;
          }

          if (itsMenu) {
            break;
          }
        }

        for (const btn of filterBtns) {
          itsBtnMenu = target == btn || btn.contains(target);
          if (itsBtnMenu) break;
        }

        if (!itsMenu && !itsBtnMenu && menuIsActive && !itsInputFocus) {
          for (const btn of filterBtns) {
            btn.classList.remove("active");
          }

          for (const menu of dropMenus) {
            menu.classList.remove("active");
          }

          if (focus) {
            const inputs = document.querySelectorAll(`${parrent} input`);
            for (const input of inputs) {
              input.classList.remove("active");
            }
          }
        }
      });

      for (const btn of filterBtns) {
        btn.addEventListener("click", () => {
          const input = btn.closest(parrent).querySelector(`input`);
          if (focus) {
            if (btn.classList.contains("active")) {
              for (const btn of filterBtns) {
                btn.classList.remove("active");
              }
              input.classList.remove("active");
            } else {
              for (const btn of filterBtns) {
                btn.classList.remove("active");
                btn.closest(parrent).querySelector("input").classList.remove("active");
              }
              toggleActiveClass(btn);
              input.classList.add("active");
            }
          }

          let thisMenu = btn.closest(parrent).querySelector(dropMenu);

          if (thisMenu.classList.contains("active")) {
            for (const menu of dropMenus) {
              menu.classList.remove("active");
              btn.classList.remove("active");
            }
          } else {
            for (const menu of dropMenus) {
              menu.classList.remove("active");
            }
            for (const btn of filterBtns) {
              btn.classList.remove("active");
            }

            thisMenu.classList.add("active");
            btn.classList.add("active");
          }
        });
      }

      for (const btn of dropMenuButtons) {
        btn.addEventListener("click", function (e) {
          e.preventDefault();
          setTimeout(() => {
            let openBtnText = btn.closest(parrent).querySelector(`${openBtn} span`);

            let fakeSelectInput = btn.closest(parrent).querySelector("input");

            if (this.hasAttribute("href") || (this.hasAttribute("type") && !this.hasAttribute("disabled"))) {
              const thisMenuBtns = this.closest(dropMenu).querySelectorAll(dropMenuBtn);
              for (const b of thisMenuBtns) {
                b.classList.remove("active");
              }

              toggleActiveClass(this);

              if (btn.closest(dropMenu).classList.contains("active")) {
                btn.closest(dropMenu).classList.toggle("active");
                if (btn.closest(parrent).querySelector(openBtn)) {
                  btn.closest(parrent).querySelector(openBtn).classList.toggle("active");
                }
                if (btn.closest(parrent).querySelector("input")) {
                  btn.closest(parrent).querySelector("input").classList.toggle("active");
                }
              }

              if (openBtnText) {
                openBtnText.textContent = btn.textContent;
              }

              if (fakeSelectInput !== null) {
                fakeSelectInput.value = btn.textContent.trim();
                fakeSelectInput.setAttribute("data-value", btn.getAttribute("data-id"));
                let blockInputVal = fakeSelectInput.value;
                blockInputVal.length < 1 ? fakeSelectInput.classList.add("empty") : fakeSelectInput.classList.remove("empty");
              }

              if (btn.closest(".js-currencies-archive")) {
                let filterBar = btn.closest(".filter-bar"),
                  monthValue = filterBar.querySelector('input[name="month"]').getAttribute("data-value"),
                  baseValue = filterBar.querySelector('input[name="base"]').getAttribute("data-value");

                if (fakeSelectInput && fakeSelectInput.name == "month") {
                  location.href = "?" + (monthValue ? "month=" + monthValue + "&" : "") + "base=" + baseValue;
                  return;
                }

                $(".js-currencies-archive-list table th:eq(3)").text(baseValue == "RUB" ? "EURO/USD" : "1 RUB");

                btn
                  .closest(".js-currencies-archive")
                  .querySelectorAll(`.js-currencies-archive-list table`)
                  .forEach((table) => {
                    const el = table.getAttribute("data-base") ? table : table.closest("[data-base]");
                    if (el && el.getAttribute("data-base")) {
                      if (el.getAttribute("data-base") == baseValue) {
                        el.classList.remove("hidden");
                        if (!el.classList.length) {
                          el.removeAttribute("class");
                        }
                        if (typeof window["drawChart" + baseValue] == "function") window["drawChart" + baseValue]();
                      } else {
                        el.classList.add("hidden");
                      }
                    }
                  });
                history.pushState(null, "", "?month=" + monthValue + "&base=" + baseValue);
              } else if (btn.closest(".js-country-tours") || btn.closest(".js-resorts-regions")) {
                let params = [],
                  filterBar = btn.closest(".filter-bar");
                $(filterBar)
                  .find("input[name]")
                  .each(function () {
                    if (this.getAttribute("data-value")) {
                      params.push(this.name + "=" + this.getAttribute("data-value"));
                    }
                  });
                location.href = params.length ? "?" + params.join("&") : location.pathname;
              } else if (btn.closest(".js-excursions-form-block")) {
                let $input = $(btn.closest(".form-select")).find("input[name]"),
                  $form = $(btn.closest("form")),
                  $resorts = $form.find('input[name="resort_id"]'),
                  $resortsSel = $resorts.closest(".form-select"),
                  $types = $form.find('input[name="type_id"]'),
                  $typesSel = $types.closest(".form-select");

                if ($form.attr("data-process")) return;
                if ($input.attr("name") == "country") {
                  $form.attr("data-process", "country");
                  $input.attr("data-url", btn.dataset.url);
                  $resorts.trigger("click");
                  $resortsSel.find('.form-drop-list__link[data-id=""]')[0].click();
                  $resortsSel
                    .find('.form-drop-list__link[data-id!=""]')
                    .attr("data-id", "")
                    .text("")
                    .closest(".form-drop-list__item")
                    .addClass("hidden");
                  $types.trigger("click");
                  $typesSel.find('.form-drop-list__link[data-id=""]')[0].click();
                  $typesSel
                    .find('.form-drop-list__link[data-id!=""]')
                    .attr("data-id", "")
                    .text("")
                    .closest(".form-drop-list__item")
                    .addClass("hidden");
                  setTimeout(function () {
                    $resorts.removeClass("active");
                    $types.removeClass("active");
                  }, 40);

                  $.ajax({
                    url: $form.attr("data-ajax"),
                    data: {
                      country_id: $input.attr("data-value"),
                    },
                    cache: false,
                    dataType: "json",
                    type: "post",
                    error: function (xhr, status, err) {
                      alert("Ошибка получения данных! Попробуйте перезагрузить страницу");
                    },
                    success: function (data) {
                      if (data["resorts"]) {
                        var $list = $resortsSel.find(".form-drop-list__list"),
                          x;
                        for (x = 0; x < data["resorts"].length; x++) {
                          $list
                            .find(".form-drop-list__item.hidden")
                            .slice(0, 1)
                            .removeClass("hidden")
                            .find(".form-drop-list__link")
                            .attr("data-id", data["resorts"][x]["id"])
                            .text(data["resorts"][x]["name"]);
                        }
                      }
                      if (data["types"]) {
                        var $list = $typesSel.find(".form-drop-list__list"),
                          x;
                        for (x = 0; x < data["types"].length; x++) {
                          $list
                            .find(".form-drop-list__item.hidden")
                            .slice(0, 1)
                            .removeClass("hidden")
                            .find(".form-drop-list__link")
                            .attr("data-id", data["types"][x]["id"])
                            .text(data["types"][x]["name"]);
                        }
                      }
                    },
                    complete: function () {
                      setTimeout(function () {
                        $form.attr("data-process", null);
                      }, 40);
                    },
                  });
                } else if ($input.attr("name") == "resort_id") {
                  $form.attr("data-process", "resort_id");
                  $.ajax({
                    url: $form.attr("data-ajax"),
                    data: {
                      country_id: $form.find('input[name="country"]').attr("data-value"),
                      resort_id: $resorts.attr("data-value"),
                    },
                    cache: false,
                    dataType: "json",
                    type: "post",
                    error: function (xhr, status, err) {
                      alert("Ошибка получения данных! Попробуйте перезагрузить страницу");
                    },
                    success: function (data) {
                      if (data["status"]) {
                        var $list = $typesSel.find(".form-drop-list__list"),
                          x;
                        $typesSel.find('.form-drop-list__link[data-id!=""]').closest(".form-drop-list__item").addClass("hidden");
                        if (data["types"]) {
                          for (x = 0; x < data["types"].length; x++) {
                            $list
                              .find('.form-drop-list__link[data-id="' + data["types"][x]["id"] + '"]')
                              .closest(".form-drop-list__item")
                              .removeClass("hidden");
                          }
                        }
                        x = $list.find(".form-drop-list__item.hidden .form-drop-list__link.active");
                        if (x.length) {
                          $types.trigger("click");
                          $typesSel.find('.form-drop-list__link[data-id=""]')[0].click();

                          setTimeout(function () {
                            $types.removeClass("active");
                          }, 40);
                        }
                      }
                    },
                    complete: function () {
                      setTimeout(function () {
                        $form.attr("data-process", null);
                      }, 40);
                    },
                  });
                } else if ($input.attr("name") == "type_id") {
                  $form.attr("data-process", "type_id");
                  $.ajax({
                    url: $form.attr("data-ajax"),
                    data: {
                      country_id: $form.find('input[name="country"]').attr("data-value"),
                      type_id: $types.attr("data-value"),
                    },
                    cache: false,
                    dataType: "json",
                    type: "post",
                    error: function (xhr, status, err) {
                      alert("Ошибка получения данных! Попробуйте перезагрузить страницу");
                    },
                    success: function (data) {
                      if (data["status"]) {
                        var $list = $resortsSel.find(".form-drop-list__list"),
                          x;
                        $resortsSel.find('.form-drop-list__link[data-id!=""]').closest(".form-drop-list__item").addClass("hidden");
                        if (data["resorts"]) {
                          for (x = 0; x < data["resorts"].length; x++) {
                            $list
                              .find('.form-drop-list__link[data-id="' + data["resorts"][x]["id"] + '"]')
                              .closest(".form-drop-list__item")
                              .removeClass("hidden");
                          }
                        }
                        x = $list.find(".form-drop-list__item.hidden .form-drop-list__link.active");
                        if (x.length) {
                          $resorts.trigger("click");
                          $resortsSel.find('.form-drop-list__link[data-id=""]')[0].click();

                          setTimeout(function () {
                            $resorts.removeClass("active");
                          }, 40);
                        }
                      }
                    },
                    complete: function () {
                      setTimeout(function () {
                        $form.attr("data-process", null);
                      }, 40);
                    },
                  });
                }
              } else if (btn.closest(".js-hotels-list")) {
                let $input = $(btn.closest(".filter-bar__item")).find("input[name]");
                if ($input.attr("name") == "country") {
                  location.href = btn.getAttribute("data-id");
                } else if ($input.attr("name") == "resort_id") {
                  refreshHotelsList("resort_id", $input.attr("data-no-history"));
                } else if ($input.attr("name") == "category_id") {
                  refreshHotelsList("category_id", $input.attr("data-no-history"));
                }
              } else if (btn.closest(".js-viphotels-country-hotels") || btn.closest(".js-viphotels-country-wellness")) {
                var $block = $(btn.closest(".js-viphotels-country-hotels") || btn.closest(".js-viphotels-country-wellness")),
                  resort_id = fakeSelectInput.getAttribute("data-value") || "",
                  category_id = $block.find('input[name="category_id"]').val(),
                  valid_cats = filterVipHotels(
                    $block.find(".filter-bar2-filterable-list .news2__item"),
                    resort_id,
                    category_id,
                    "category_id"
                  );

                $block
                  .find('input[name="category_id"]')
                  .closest(".news2__filter-btns")
                  .find(".btns__list .btn")
                  .each(function () {
                    if (!this.getAttribute("data-id")) return;
                    this.disabled = valid_cats[this.getAttribute("data-id")] ? false : true;
                  });
              } else if (btn.closest(".sub-newsletter__info")) {
                $("#subscribe_city")
                  .val($.trim($(this).text()))
                  .attr("data-value", $.trim(this.getAttribute("data-id") || ""))
                  .trigger("change");
              }
            }
          }, 30);
          return false;
        });
      }

      if (focus) {
        const inputs = document.querySelectorAll(`${parrent} input`);

        for (const i of inputs) {
          i.addEventListener("focus", function () {
            setTimeout(() => {
              let block, element;
              for (const input of inputs) {
                input.classList.remove("active");
                block = input.closest(parrent);
                element = block ? block.querySelector(openBtn) : false;
                if (element) element.classList.remove("active");
                element = block ? block.querySelector(dropMenu) : false;
                if (element) element.classList.remove("active");
              }
              this.classList.add("active");
              block = this.closest(parrent);
              element = block ? block.querySelector(openBtn) : false;
              if (element) element.classList.add("active");
              element = block ? block.querySelector(dropMenu) : false;
              if (element) element.classList.add("active");
            }, 30);
            if (i.closest(".js-hotels-search-form") && !hotelsSearchList.length) {
              $(".hotels-list__lists .hotels-list__link").each(function () {
                hotelsSearchList.push([$.trim(this.textContent.toLowerCase()), this.href, this.innerHTML]);
              });
              if (hotelsSearchList.length > 1)
                hotelsSearchList.sort(function (a, b) {
                  return a[0] > b[0] ? 1 : a[0] < b[0] ? -1 : 0;
                });
            }
          });

          i.addEventListener("click", function () {
            setTimeout(() => {
              for (const menu of dropMenus) {
                menu.classList.remove("active");
              }
              this.closest(parrent).querySelector(dropMenu).classList.add("active");
            }, 30);
          });
        }
      }
    }
  }

  window.addEventListener("resize", () => {
    for (var tmp in window) {
      if (tmp.length == 12 && tmp.substr(0, 9) == "drawChart" && typeof window[tmp] == "function") window[tmp]();
    }
  });

  function toggleActiveClass(el) {
    el.classList.toggle("active");
  }

  // кнопки в фильтрах
  const filterBtns = document.querySelectorAll(".news2__filter-btns .btn");

  if (filterBtns.length > 0) {
    filterBtns.forEach((filterBtn) => {
      filterBtn.addEventListener("click", function () {
        if (this.classList.contains("active")) return;
        const btnsActive = this.closest(".btns__list").querySelectorAll(".btn.active");
        for (const btnActive of btnsActive) {
          btnActive.classList.remove("active");
        }
        this.classList.add("active");
        const input = this.closest(".btns__list-wrap").querySelector("input");
        if (input) input.value = this.getAttribute("data-id");

        let block = this.closest(".js-viphotels-country-hotels");
        if (!block) block = this.closest(".js-viphotels-country-wellness");

        if (block) {
          let resort_id = block.querySelector('input[name="resort_id"]').getAttribute("data-value") || "",
            category_id = this.getAttribute("data-id"),
            valid_resorts = filterVipHotels(
              $(block).find(".filter-bar2-filterable-list .news2__item"),
              resort_id,
              category_id,
              "resort_id"
            ),
            list = block
              .querySelector('input[name="resort_id"]')
              .closest(".filter-bar__dropdown")
              .querySelectorAll(".form-drop-list__link");
          for (const item of list) {
            if (!item.getAttribute("data-id")) continue;
            if (valid_resorts[item.getAttribute("data-id")]) item.setAttribute("href", "");
            else item.removeAttribute("href");
          }
        }
      });
    });
  }

  function refreshHotelsList(src, no_state) {
    let block = document.querySelector(".js-hotels-list"),
      form = block.querySelector(".filter-bar--hotels-list"),
      resorts = form.querySelector('input[name="resort_id"]'),
      resort_id = resorts.getAttribute("data-value"),
      region_id = "",
      categories = form.querySelector('input[name="category_id"]'),
      category_id = categories.getAttribute("data-value"),
      valid = {},
      qs = [];
    if (resort_id) {
      if (resort_id.substr(0, 1) == "r") region_id = resort_id.substr(1);
      qs.push("resort_id=" + resort_id);
    }
    if (category_id) qs.push("category_id=" + category_id);
    qs = qs.length ? "?" + qs.join("&") : "";

    block.querySelectorAll(".hotels-list__lists-item .hotels-list__item").forEach((item) => {
      let cur_region = item.getAttribute("data-region"),
        cur_resort = item.getAttribute("data-resort"),
        cur_category = item.getAttribute("data-category"),
        is_resort = false,
        is_category = false;

      if ((region_id && cur_region == region_id) || !resort_id || cur_resort == resort_id) is_resort = true;
      if (!category_id || cur_category == category_id) is_category = true;

      if (src == "resort_id") {
        if (is_resort) valid[cur_category] = cur_category;
      } else if (src == "category_id") {
        if (is_category) {
          valid[cur_resort] = cur_resort;
          if (cur_region) valid["r" + cur_region] = "r" + cur_region;
        }
      }

      if (is_resort && is_category) {
        item.classList.remove("hidden");
      } else {
        item.classList.add("hidden");
      }
    });

    block.querySelectorAll(".hotels-list__lists-item").forEach((item) => {
      if (item.querySelectorAll(".hotels-list__item:not(.hidden)").length) {
        item.classList.remove("hidden");
      } else {
        item.classList.add("hidden");
      }
    });

    if (src == "resort_id") {
      categories
        .closest(".filter-bar__item")
        .querySelectorAll(".form-drop-list__link")
        .forEach((item) => {
          let id = item.getAttribute("data-id");
          if (!id) return;
          if (valid[id]) item.setAttribute("href", "");
          else item.removeAttribute("href");
        });
    } else if (src == "category_id") {
      resorts
        .closest(".filter-bar__item")
        .querySelectorAll(".form-drop-list__link")
        .forEach((item) => {
          let id = item.getAttribute("data-id");
          if (!id) return;
          if (valid[id]) item.setAttribute("href", "");
          else item.removeAttribute("href");
        });
    }

    if (!no_state) {
      form = location.href.indexOf("?");
      form = form > 0 ? [location.href.substr(0, form), location.href.substr(form)] : [location.href, ""];
      if (form[1] != qs) history.pushState(null, "", form[0] + qs);
    }
  }

  function setHotelsListFromUrl() {
    let form = document.querySelector(".js-hotels-list .filter-bar--hotels-list");
    if (!form) return;

    let resorts = form.querySelector('input[name="resort_id"]'),
      resort_id = resorts.getAttribute("data-value"),
      categories = form.querySelector('input[name="category_id"]'),
      category_id = categories.getAttribute("data-value"),
      qs = { resort_id: "", category_id: "" },
      x;
    if (document.location.search.length > 1) {
      for (x of document.location.search.substr(1).split("&")) {
        x = x.split("=");
        if (x[0] == "resort_id" || x[0] == "category_id") qs[x[0]] = x[1];
      }
    }
    if (qs["resort_id"] != resort_id) {
      x = resorts.closest(".filter-bar__item").querySelector('.form-drop-list__link[data-id="' + qs["resort_id"] + '"]');
      if (x) {
        resorts.setAttribute("data-no-history", 1);
        x.dispatchEvent(
          new Event("click", {
            bubbles: true,
            cancelable: true,
          })
        );
        resorts.removeAttribute("data-no-history");
      }
    }
    if (qs["category_id"] != category_id) {
      x = categories.closest(".filter-bar__item").querySelector('.form-drop-list__link[data-id="' + qs["category_id"] + '"]');
      if (x) {
        categories.setAttribute("data-no-history", 1);
        x.dispatchEvent(
          new Event("click", {
            bubbles: true,
            cancelable: true,
          })
        );
        categories.removeAttribute("data-no-history");
      }
    }
  }

  setHotelsListFromUrl();

  $(window).on("popstate", function (e) {
    let hotelsBlock = document.querySelector(".js-hotels-list");
    if (!hotelsBlock) return;

    setHotelsListFromUrl();
  });

  // у инпутов механики
  checkInputValue(".hard-input__input");
  checkInputValue(".form-select__input");

  function checkInputValue(inputs) {
    const blockInputs = document.querySelectorAll(inputs);

    for (const blockInput of blockInputs) {
      window.addEventListener("load", checkInput);
      blockInput.addEventListener("focusin", () => blockInput.classList.remove("empty"));
      blockInput.addEventListener("focusout", checkInput);

      function checkInput() {
        let blockInputVal = blockInput.value;
        blockInputVal.length < 1 ? blockInput.classList.add("empty") : blockInput.classList.remove("empty");
      }
    }
  }

  // закрытие/открытие попапов
  const overlay = document.querySelector(".overlay"),
    popupCloseBtns = document.querySelectorAll(".popap-close-btn");

  // overlay.addEventListener('click', () => closePopups());

  document.addEventListener("keydown", function (e) {
    if (e.code == "Escape") {
      closePopups();
    }
  });

  document.addEventListener("click", function (e) {
    const target = e.target.closest(".blog-nav__link");

    if (target === null || target.getAttribute("href").charAt(0) !== "#") return false;

    const popup = target.closest(".tours-popup");

    if (popup === null) return false;

    popup.querySelector(".popap-close-btn").click();
  });

  for (const btn of popupCloseBtns) {
    btn.addEventListener("click", () => closePopups());
  }

  function closePopups() {
    const activeCalendarsWrap = document.querySelectorAll(".form-item--calendar.form-item--active");
    for (const wrap of activeCalendarsWrap) {
      const calendar = wrap.querySelector(".daterangepicker");
      calendar.setAttribute("style", "display: none");
    }

    const activeFormItems = document.querySelectorAll(".form-item.form-item--active");
    for (const item of activeFormItems) {
      item.classList.remove("form-item--active");
    }

    let jsActive = document.querySelectorAll(".js-active.active");
    for (const i of jsActive) {
      i.classList.remove("active");
    }

    // const navItemBodys = document.querySelectorAll('.nav-item__body');
    // for (const body of navItemBodys) {
    //     if (WINDOW_WIDTH_INNER < DESKTOP_WIDTH) {
    //         body.style.height = '0';
    //     } else {
    //         body.style.height = "auto";
    //     }
    // }

    unLockScroll();
  }

  document.querySelectorAll('[data-indeterminate="indeterminate"').forEach((item) => {
    item.indeterminate = true;
  });

  // фильтр новостей
  let filterCategory = "all";

  // ПОКАЗАТЬ еще
  showPlusItems(".hotels__btn", ".hotels__list", 4, 4);
  //showPlusItems('.news-list-section__btn', '.news-list', 10, 10, true);
  showPlusItems(".news-list-section .btn", ".news-list", 10, 0, true, ".news-list-section");
  //showPlusItems('.reviews-list__btn-row', '.reviews-list__inner', 10, 10);

  function showPlusItems(btnName, listName, startShow, plusNumber, filter = false, clear = false, all = false) {
    const list = document.querySelector(listName),
      btns = document.querySelectorAll(btnName);
    let listItems = document.querySelectorAll(`${listName} > *`);

    if (btns.length > 0 && list) {
      let blockStep = parseInt(list.getAttribute("data-step"));
      blockStep = isNaN(blockStep) || blockStep < 1 ? 0 : blockStep;
      for (const btn of btns) {
        let howMuchShowNow = blockStep || startShow,
          hidebox = btn.closest(".js-btn-hidebox");

        if (howMuchShowNow >= listItems.length) {
          if (!clear) {
            btn.classList.add("btn-row--hide");
            if (hidebox) hidebox.classList.add("hidden");
          } else {
            btn.classList.remove("btn-row--hide");
            if (hidebox) hidebox.classList.remove("hidden");
          }
        } else {
          btn.classList.remove("btn-row--hide");
          if (hidebox) hidebox.classList.remove("hidden");

          const wrapperName = "best-wrapper-ever";

          if (!document.querySelector(`.${wrapperName}`)) {
            const wrapper = document.createElement("div");
            wrapper.setAttribute("class", wrapperName);

            list.parentNode.insertBefore(wrapper, list);
            wrapper.appendChild(list);
          }

          for (const i of listItems) {
            i.setAttribute("style", "height: 0; overflow: hidden; margin-bottom: 0; padding: 0; border: 0");
          }

          for (let i = 0; i < howMuchShowNow; i++) {
            listItems[i].removeAttribute("style");
          }

          const wrapperNode = document.querySelector(`.${wrapperName}`);

          let heightList = function () {
            let height;

            function heightNow() {
              window.addEventListener("load", () => {
                height = list.offsetHeight;
                wrapperNode.setAttribute("style", `height: ${height}px`);
              });
            }

            heightNow();
          };

          heightList();

          function upgradeHeight() {
            wrapperNode.setAttribute("style", `height: ${list.offsetHeight}px`);
          }

          btn.addEventListener("click", function () {
            let listItems = document.querySelectorAll(`${listName} > *`);
            let timeOut = 0;
            if (clear) {
              howMuchShowNow = blockStep || startShow;

              for (const i of listItems) {
                i.setAttribute("style", "height: 0; overflow: hidden; margin-bottom: 0; padding: 0; border: 0");
              }
              upgradeHeight();
              for (const b of btns) {
                b.classList.remove("active");
              }
              this.classList.add("active");
              timeOut = 600;

              const btnRow = this.closest(clear).querySelector(".btn-row"),
                hideboxRow = btnRow ? btnRow.closest(".js-btn-hidebox") : false;
              if (btnRow) btnRow.classList.remove("btn-row--hide");
              if (hideboxRow) hideboxRow.classList.remove("hidden");
            } else {
              const btnRow = this.parentNode.querySelector(".btn-row"),
                hideboxRow = btnRow ? btnRow.closest(".js-btn-hidebox") : false;
              if (btnRow) btnRow.classList.add("btn-row--hide");
              if (hideboxRow) hideboxRow.classList.add("hidden");
            }

            if (filter) {
              const newFilter = btn.getAttribute("data-filter-category");
              if (newFilter) {
                filterCategory = newFilter.trim();
              }
            }

            if (filterCategory != "") {
              const listFilterItems = listItems;
              if (filterCategory != "all") {
                listItems = [];

                for (const item of listFilterItems) {
                  if (item.getAttribute("data-filter-category").includes(filterCategory)) {
                    listItems.push(item);
                  }
                }
              }
            }

            howMuchShowNow += blockStep || plusNumber;

            setTimeout(() => {
              if (all || howMuchShowNow > listItems.length) {
                if (!clear) {
                  btn.classList.add("btn-row--hide");
                  if (hidebox) hidebox.classList.add("hidden");
                } else {
                  btn.classList.remove("btn-row--hide");
                  if (hidebox) hidebox.classList.remove("hidden");
                }

                for (let i = 0; i < listItems.length; i++) {
                  listItems[i].removeAttribute("style");
                }

                upgradeHeight();
              } else {
                btn.classList.remove("btn-row--hide");
                if (hidebox) hidebox.classList.remove("hidden");

                for (let i = 0; i < howMuchShowNow; i++) {
                  listItems[i].removeAttribute("style");
                }

                btn.classList.add("btn-row--load");
                upgradeHeight();
                btn.classList.remove("btn-row--load");
              }
            }, timeOut);
          });
        }
      }
    }
  }

  document.querySelectorAll(".btn-row2").forEach((btn) => {
    let block = btn.closest(".main-content2__block"),
      list = block ? block.querySelector("ul[data-step]") : false;

    if (!list) return;

    let listItems = list.children;
    listItems = Array.from(listItems).filter((el) => el.tagName.toLowerCase() === "li");

    let blockStep = parseInt(list.getAttribute("data-step"));
    blockStep = isNaN(blockStep) || blockStep < 1 ? 0 : blockStep;

    let howMuchShowNow = blockStep;

    if (howMuchShowNow >= listItems.length) {
      btn.classList.add("btn-row2_hide");
    } else {
      btn.classList.remove("btn-row2_hide");

      for (const i of listItems) {
        i.setAttribute("style", "height: 0; overflow: hidden; margin-top: 0; padding: 0; border: 0");
      }

      for (let i = 0; i < howMuchShowNow; i++) {
        listItems[i].removeAttribute("style");
      }

      btn.addEventListener("click", (e) => {
        howMuchShowNow += blockStep;

        setTimeout(() => {
          if (howMuchShowNow >= listItems.length) {
            btn.classList.add("btn-row2_hide");

            for (let i = 0; i < listItems.length; i++) {
              listItems[i].removeAttribute("style");
            }
          } else {
            btn.classList.remove("btn-row2_hide");

            for (let i = 0; i < howMuchShowNow; i++) {
              listItems[i].removeAttribute("style");
            }
          }
        }, 0);
      });
    }
  });

  $("#more_hotels_diseases").on("click", function () {
    const loadStyle = "btn-row2_load",
      btn = this,
      hidebox = btn.closest(".js-btn-hidebox"),
      limit = parseInt($(btn).data("limit")),
      offset = limit + parseInt($(btn).data("offset")),
      curUrl = new URL(window.location.href);

    btn.classList.add(loadStyle);

    curUrl.search = "";

    $.ajax({
      url: curUrl.toString(),
      data: {
        offset: offset,
      },
      cache: false,
      dataType: "html",
      type: "post",
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");

        btn.classList.remove(loadStyle);
      },
      success: function (data) {
        if (offset + limit >= parseInt($(btn).data("total"))) {
          $(btn).addClass("btn-row--hide");
          if (hidebox) $(hidebox).addClass("hidden");
        } else {
          $(btn).data("offset", offset);
        }

        $(data).appendTo($("#more_hotels_diseases").closest("section").find("ul"));

        btn.classList.remove(loadStyle);
      },
    });
  });

  // отправление формы появление плашки
  /*
    const formsBtns = document.querySelectorAll('.forms__btn');
    for (const btn of formsBtns) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const alert = this.closest('form').querySelector('.alert--fixed');
            alert.classList.add('active');

            setTimeout(function () {
                alert.classList.remove('active');
            }, 3000);
        })
    }
    */

  // очистка фильтр бара
  $(".empty-result__btn").click(function (e) {
    const inputs = $(this).closest(".tours-in").find(".filter-bar__input");
    const links = $(this).closest(".tours-in").find(".form-drop-list__link");

    $(links).removeClass("active");

    inputs[0].value = "Без перелета";
    inputs[1].value = "Все города";
    inputs[2].value = "Все типы";
  });

  // работа с rutube
  const rutubeF = (event, slide, player, state = "") => {
    if (!player.contentWindow || !slide) return;

    if (event === undefined && state === "resume") {
      player.contentWindow.postMessage(
        JSON.stringify({
          type: "player:play",
          data: {},
        }),
        "*"
      );
      return;
    }

    let ev_type,
      ev_data,
      isSelected = slide.el.classList.contains("is-selected", "is-done");

    try {
      const data = JSON.parse(event.data);
      ev_type = data.type;
      ev_data = data.data;
    } catch (err) {
      ev_type = "JSON invalid";
      return;
    }

    if (ev_type == "player:ready" && isSelected) {
      player.contentWindow.postMessage(
        JSON.stringify({
          type: "player:setCurrentTime",
          data: { time: 0 },
        }),
        "*"
      );
      player.contentWindow.postMessage(
        JSON.stringify({
          type: "player:play",
          data: {},
        }),
        "*"
      );
      player.contentWindow.postMessage(
        JSON.stringify({
          type: "player:inMute",
          data: {},
        }),
        "*"
      );
    } else if (state === "pause" && ev_type == "player:currentTime" && !isSelected) {
      player.contentWindow.postMessage(
        JSON.stringify({
          type: "player:pause",
          data: {},
        }),
        "*"
      );
    }
  };

  // Fancybox
  Fancybox.bind("[data-fancybox]", {
    idle: false,
    l10n: {
      CLOSE: "Закрыть",
      NEXT: "Далее",
      PREV: "Назад",
      MODAL: "Окно можно закрыть нажатием клавиши ESC",
      ERROR: "Ошибка! Пожалуйста, попробуйте позже",
      IMAGE_ERROR: "Изображение не найдено",
      ELEMENT_NOT_FOUND: "HTML элемент не найден",
      IFRAME_ERROR: "Ошибка загрузки страницы",
      TOGGLE_ZOOM: "Масштабирование",
      TOGGLE_THUMBS: "Показ миниатюр",
      TOGGLE_SLIDESHOW: "Слайдшоу",
      TOGGLE_FULLSCREEN: "Полноэкранный режим",
      DOWNLOAD: "Загрузка",
    },
    Carousel: {
      transition: "classic",
      friction: 0.3,
    },
    Thumbs: {
      type: "classic",
    },
    on: {
      "Carousel.ready Carousel.change": (fancybox) => {
        const slide = fancybox.getSlide();
        const isVideoTrigger = slide.triggerEl.classList.contains("_show-video");

        if (!isVideoTrigger) return;
        slide.el.classList.add("has-youtube");

        if (slide.triggerEl.classList.contains("_vk")) {
          const a = slide.contentEl.querySelector("iframe");
          const player = VK.VideoPlayer(a);
          a.setAttribute("frameborder", "0");
          player.play();
          player.unmute();
        }

        if (slide.triggerEl.classList.contains("_rutube")) {
          const player = slide.contentEl.querySelector("iframe");
          rutubeF(undefined, slide, player, "resume");

          window.addEventListener("message", (event) => {
            if (event.origin !== "https://rutube.ru") return;
            rutubeF(event, slide, player, "");
          });
        }
      },
      "Carousel.unselectSlide": (fancybox) => {
        fancybox.carousel.slides.forEach((slide) => {
          if (!slide["triggerEl"] || !slide["el"] || !slide["el"].classList.contains("has-youtube")) return;

          if (slide["triggerEl"].classList.contains("_vk")) {
            const a = slide["el"].querySelector("iframe");
            const player = VK.VideoPlayer(a);
            player.pause();
          }

          if (slide["triggerEl"].classList.contains("_rutube")) {
            const player = slide["el"].querySelector("iframe");

            window.addEventListener("message", (event) => {
              if (event.origin !== "https://rutube.ru") return;
              rutubeF(event, slide, player, "pause");
            });
          }
        });
      },
    },
    Html: {
      iframeAttr: {
        allow: "autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock;",
      },
    },
  });

  /* кнопка "показать/скрыть" текст в контенте */
  initHubs();

  function initHubs() {
    const hubBtns = document.querySelectorAll(".hub-btn");

    if (hubBtns.length > 0) {
      for (const btn of hubBtns) {
        const upperBlock = btn.previousElementSibling;
        const upperBlockChild = upperBlock.children[0];
        const upperBlockMaxLines = upperBlock.dataset.maxLines || 10;

        let upperBlockLineHeight = 10;
        let upperBlockHeightFull = 999999999;
        let upperBlockHeightVisible = upperBlockLineHeight * upperBlockMaxLines;

        checkUpperBlock();

        btn.addEventListener("click", function () {
          if (btn.classList.contains("hub-hidden")) {
            showUpperBlock();
          } else {
            hideUpperBlock();
          }
        });

        window.addEventListener("resize", () => {
          checkUpperBlock();
        });

        function checkUpperBlock() {
          upperBlockLineHeight = parseFloat(getComputedStyle(upperBlock).lineHeight);
          upperBlockHeightFull = parseFloat(getComputedStyle(upperBlockChild).height);
          upperBlockHeightVisible = upperBlockLineHeight * upperBlockMaxLines;

          if (upperBlockHeightVisible < upperBlockHeightFull && btn.classList.contains("hidden")) {
            btn.classList.remove("hidden");
            hideUpperBlock();
          } else if (upperBlockHeightVisible >= upperBlockHeightFull && !btn.classList.contains("hidden")) {
            btn.classList.add("hidden");
            upperBlock.style.height = "auto";
          }
        }

        function hideUpperBlock() {
          upperBlock.style.height = `${upperBlockHeightFull}px`;

          setTimeout(() => {
            upperBlock.style.height = `${upperBlockHeightVisible}px`;
            btn.classList.add("hub-hidden");
          }, 50);
        }

        function showUpperBlock() {
          upperBlock.style.height = `${upperBlockHeightVisible}px`;

          setTimeout(() => {
            upperBlock.style.height = `${upperBlockHeightFull}px`;
            btn.classList.remove("hub-hidden");
          }, 50);

          setTimeout(() => {
            upperBlock.style.height = "auto";
          }, 500);
        }
      }
    }
  }

  // поиск по селекту
  $(".js-header-form-from").each(function () {
    findInSelect(this, ".form-item", ".cities2 a", ".form-item__no-result");
  });
  $(".js-header-form-to").each(function () {
    findInSelect(this, ".form-item", ".js-where-default .cities2 a", ".form-item__no-result");
    findInSelect(this, ".form-item", ".js-where-search .cities2 a", ".form-item__no-result");
  });
  findInSelect("#content-form-from", ".form-item", ".cities2 a", ".form-item__no-result");
  $(".form-select__input").each(function () {
    findInSelect(this, ".form-select", ".form-drop-list__link", ".form-drop-list__no-result");
  });
  $(".search-input__search").each(function () {
    findInSelect(this, ".search-input", ".form-drop-list__link", ".form-drop-list__no-result");
  });
  $(".filter-bar3__item_input-search .filter-bar3__input").each(function () {
    findInSelect(this, ".filter-bar3__item_input-search", ".form-drop-list3__link", ".form-drop-list3__no-result");
  });

  function multiStr(str) {
    let ss = [str, ""],
      x,
      c;
    for (x = 0; x < ss[0].length; x++) {
      c = ss[0].charAt(x);
      if (e2rt[c]) {
        ss[1] += e2rt[c];
      } else {
        ss[1] += c;
      }
    }
    if (ss[1] == ss[0]) ss[1] = "";

    return ss;
  }

  function findInSelect(id, parrent, itemList, messageNotFound) {
    let input = $(id);

    if (input) {
      let list = input.closest(parrent).find(itemList);
      const message = input.closest(parrent).find(messageNotFound);

      $.each(input, function (index, inp) {
        if (!inp.classList.contains("js-header-form-to")) {
          if ($(inp).val() != "") {
            let inpValues = multiStr($(inp).val().trim().toLowerCase());

            $.each(list, function (index, value) {
              const valueClean = value.textContent.trim().toLowerCase();
              if (valueClean == inpValues[0] || (inpValues[1] && valueClean == inpValues[1])) {
                list[index].classList.add("active");
              }
            });
          }
        }
      });

      $.each(list, function (index, value) {
        $(value).click(function (e) {
          e.preventDefault();
          unLockScroll();

          $(".header-form").removeClass("active");

          const href = $(this).attr("href");

          if (href) {
            const thisMenuLinks = $(this).closest(parrent).find(itemList);

            $.each(thisMenuLinks, function (index, val) {
              $(val).removeClass("active");
            });
            $(this).addClass("active");

            const inp = $(this).closest(parrent).find("input");
            inp.val(this.textContent.trim()).attr("data-name", this.textContent.trim());
            $(this).closest(parrent).removeClass("form-item--active");
            $(this).closest(parrent).removeClass("form-item--empty");

            $(".header").removeClass("active");

            if (href.substr(0, 1) != "#") window.location.href = href;

            return;
          }
        });
      });

      if ($(id).attr("data-fis-events")) return;
      $(id).on("input", function (ev) {
        if (ev.target.classList.contains("js-header-form-to")) return;
        if (ev.target.closest(".js-hotels-search-form")) {
          let $templateItem = false,
            $templateLink,
            $listBlock;
          const inputWords = multiStr(ev.target.value.toLowerCase());
          message.addClass("d-none");
          $.each(list, function (index, value) {
            if (value.parentNode.classList.contains("js-template")) {
              $templateItem = $(value.parentNode).clone().removeClass("js-template hidden");
              $templateLink = $templateItem.find(itemList);
              $listBlock = $(value.parentNode.parentNode);
            } else if (value.parentNode.classList.contains("js-default")) {
              if (inputWords[0].length < 3) {
                value.parentNode.classList.remove("d-none");
              } else {
                value.parentNode.classList.add("d-none");
              }
            } else {
              $(value.parentNode).remove();
            }
          });
          if (inputWords[0].length < 3) {
            list = input.closest(parrent).find(itemList);
            return;
          }
          let count = 0,
            x;
          if ($templateItem.length && hotelsSearchList.length) {
            for (x = 0; x < hotelsSearchList.length; x++) {
              if (hotelsSearchList[x][0].includes(inputWords[0]) || (inputWords[1] && hotelsSearchList[x][0].includes(inputWords[1]))) {
                $templateLink.attr("href", hotelsSearchList[x][1]).html(hotelsSearchList[x][2]);
                $templateItem.clone().appendTo($listBlock);
                count++;
              }
            }
          }
          list = input.closest(parrent).find(itemList);
          if (!count) {
            message.removeClass("d-none");
          }
        } else {
          message.addClass("d-none");
          const inputWords = multiStr($(ev.target).val().toLowerCase());

          if (inputWords[0] != "") {
            let count = 0;
            $.each(list, function (index, value) {
              value.classList.add("d-none");
              if (value.getAttribute("data-href")) value.setAttribute("href", value.getAttribute("data-href"));
              if (value.classList.contains("js-nosearch")) {
                return;
              }
              if (
                value.textContent.toLowerCase().includes(inputWords[0]) ||
                (inputWords[1] && value.textContent.toLowerCase().includes(inputWords[1]))
              ) {
                value.classList.remove("d-none");
                count++;

                if (value.closest(".form-drop-list3__inner-list")) {
                  let parr = value.closest(".form-drop-list3__item").querySelector(".form-drop-list3__link");
                  if (parr.classList.contains("d-none")) {
                    parr.setAttribute("data-href", parr.getAttribute("href"));
                    parr.removeAttribute("href");
                    parr.classList.remove("d-none");
                  }
                }
              }
            });

            if (count == 0) {
              message.removeClass("d-none");
            }
          } else {
            $.each(list, function (index, value) {
              value.classList.remove("d-none");
              if (value.getAttribute("data-href")) value.setAttribute("href", value.getAttribute("data-href"));
            });
            message.addClass("d-none");
          }
        }
      });

      $(id).attr("data-fis-events", 1);
    }
  }

  let searchCountries = {},
    searchResorts = {};
  let searchFormFieldsAjax = function ($form, data, selects_init, is_anysearch) {
    if ($form.attr("data-process")) return;
    $form.find(".error").removeClass("error");
    $form.attr("data-process", 1);
    $.ajax({
      url: $form.attr("data-action-ajax"),
      data: data,
      cache: false,
      dataType: "json",
      timeout: 10000,
      type: "post",
      error: function (xhr, status, err) {
        // Error handling without console output
      },
      success: function (data) {
        let $cur,
          $items,
          $tpl,
          str,
          x,
          y,
          sel = [];
        if (selects_init && selects_init & 2) {
          $items = $form
            .find(".js-search__input_from")
            .val("")
            .attr("data-value", "")
            .closest(".js-search__block")
            .find(".form-item__body ul");
          $tpl = $items.find("li").first();
          $items.find("li").slice(1).remove();
          for (x = 0; x < data["townfroms"].length; x++) {
            $cur = $tpl.clone();
            $cur.find(".search-list__link_active").removeClass("search-list__link_active");
            $cur.attr("data-id", data["townfroms"][x]["id"]).removeClass("hidden");
            if ($cur.hasClass("js-element-text")) $cur.text(data["townfroms"][x]["name"]);
            else $cur.find(".js-element-text").text(data["townfroms"][x]["name"]);
            $items.append($cur);
            if (data["townfroms"][x]["sel"]) sel.push($cur.find("a"));
          }
          findInSelect($form.find(".js-header-form-from"), ".form-item", ".cities a", ".form-item__no-result");
        }
        if (selects_init && selects_init & 1) {
          x = $form.attr("data-action-ajax");
          searchCountries[x] = data["countries"];
          searchResorts[x] = data["resorts"];
          //                    str = multiStr(($form.find('.js-search__input_where').val() || '').trim().toLowerCase());
          str = ["", ""];
          str[2] = str[0].length > 1;
          $items = $form.find(".js-search__input_where").attr("data-value", "").closest(".js-search__block").find(".form-item__body");
          $items = { d: $items.find(".js-where-default"), s: $items.find(".js-where-search") };
          $items[str[2] ? "s" : "d"].removeClass("hidden");
          $items[str[2] ? "d" : "s"].addClass("hidden");
          $items["dp"] = $items["d"].find(".cities2-wrap__part-title._popular").next(".cities2");
          $items["do"] = $items["d"].find(".cities2-wrap__part-title:not(._popular)").next(".cities2");
          $items["sp"] = $items["s"].find(".cities2-wrap__part-title._popular").next(".cities2");
          $items["so"] = $items["s"].find(".cities2-wrap__part-title:not(._popular)").next(".cities2");
          $tpl = {
            dp: $items["dp"].find("li.hidden").first(),
            do: $items["do"].find("li.hidden").first(),
            sp: $items["sp"].find("li.hidden").first(),
            so: $items["so"].find("li.hidden").first(),
          };
          $items["dp"].find("li:not(.hidden)").remove();
          $items["dp"].find("li").slice(1).remove();
          $items["do"].find("li:not(.hidden)").remove();
          $items["do"].find("li").slice(1).remove();
          $items["sp"].find("li:not(.hidden)").remove();
          $items["sp"].find("li").slice(1).remove();
          $items["so"].find("li:not(.hidden)").remove();
          $items["so"].find("li").slice(1).remove();
          for (x = 0; x < data["countries"].length; x++) {
            y = data["countries"][x]["pop"] ? "dp" : "do";
            $cur = $tpl[y].clone().removeClass("hidden d-none");
            $cur.attr({ "data-id": data["countries"][x]["id"], "data-visa": data["countries"][x]["visa"] ? "yes" : "no" });
            $cur.find(".js-element-icon").html(data["countries"][x]["icon"] || "");
            if ($cur.hasClass("js-element-text")) $cur.text(data["countries"][x]["name"]);
            else $cur.find(".js-element-text").text(data["countries"][x]["name"]);
            $cur.find(".js-element-text2").text(data["countries"][x]["ext"] || "");
            $items[y].append($cur);
            if (data["countries"][x]["sel"]) sel.push($cur.find("a"));
            str[3] = data["countries"][x]["name"].trim().toLowerCase();
            if (str[2] && str[3].indexOf(str[0]) < 0 && (str[1].length < 2 || str[3].indexOf(str[1]) < 0)) continue;
            if (str[2]) {
              $cur = $tpl["sp"].clone().removeClass("hidden d-none");
              $cur.attr({ "data-id": data["countries"][x]["id"], "data-visa": data["countries"][x]["visa"] ? "yes" : "no" });
              $cur.find(".js-element-icon").html(data["countries"][x]["icon"] || "");
              if ($cur.hasClass("js-element-text")) $cur.text(data["countries"][x]["name"]);
              else $cur.find(".js-element-text").text(data["countries"][x]["name"]);
              $cur.find(".js-element-text2").text(data["countries"][x]["ext"] || "");
              $items["sp"].append($cur);
              if (data["countries"][x]["sel"]) sel.push($cur.find("a"));
            }
          }
          if (str[2] && data["resorts"].length) {
            for (x = 0; x < data["resorts"].length; x++) {
              str[3] = data["resorts"][x]["name"].trim().toLowerCase();
              str[4] = str[3] + " " + data["resorts"][x]["ext"].trim().toLowerCase();
              if (
                str[2] &&
                str[3].indexOf(str[0]) < 0 &&
                str[4].indexOf(str[0]) != 0 &&
                (str[1].length < 2 || (str[3].indexOf(str[1]) < 0 && str[4].indexOf(str[1]) != 0))
              )
                continue;
              $cur = $tpl["so"].clone().removeClass("hidden d-none");
              $cur.attr("data-id", data["resorts"][x]["id"]);
              $cur.find(".js-element-icon").html(data["resorts"][x]["icon"] || "");
              if ($cur.hasClass("js-element-text")) $cur.text(data["resorts"][x]["name"]);
              else $cur.find(".js-element-text").text(data["resorts"][x]["name"]);
              $cur.find(".js-element-text2").text(data["resorts"][x]["ext"] || "");
              $items["so"].append($cur);
              if (data["resorts"][x]["sel"]) sel.push($cur);
            }
          }
          if (str[2] && !$items["sp"].find("li:not(.hidden)").length && !$items["so"].find("li:not(.hidden)").length) {
            $form.find(".js-search__input_where").val("");
            $items["d"].removeClass("hidden");
            $items["s"].addClass("hidden");
            str[2] = false;
          }

          if ($items[str[2] ? "dp" : "sp"].find("li:not(.hidden)").length)
            $items[str[2] ? "dp" : "sp"].closest(".cities2-wrap__part").removeClass("hidden");
          else $items[str[2] ? "dp" : "sp"].closest(".cities2-wrap__part").addClass("hidden");

          if ($items[str[2] ? "do" : "so"].find("li:not(.hidden)").length)
            $items[str[2] ? "do" : "so"].closest(".cities2-wrap__part").removeClass("hidden");
          else $items[str[2] ? "do" : "so"].closest(".cities2-wrap__part").addClass("hidden");

          if ($items[str[2] ? "do" : "so"].closest(".cities2-wrap").find(".cities2-wrap__part:not(.hidden)").length)
            $items[str[2] ? "do" : "so"].closest(".cities2-wrap").removeClass("hidden");
          else $items[str[2] ? "do" : "so"].closest(".cities2-wrap").addClass("hidden");

          findInSelect($form.find(".js-header-form-to"), ".form-item", ".js-where-default .cities2 a", ".form-item__no-result");
          findInSelect($form.find(".js-header-form-to"), ".form-item", ".js-where-search .cities2 a", ".form-item__no-result");

          $items["m"] = $items["dp"].closest(".form-item").find(".form-item__no-result");

          if ($items[str[2] ? "sp" : "dp"].find("li:not(.hidden)").length || $items[str[2] ? "so" : "do"].find("li:not(.hidden)").length) {
            if ($items["m"].attr("data-default")) $items["m"].text($items["m"].attr("data-default"));
            $items["m"].addClass("d-none");
          } else {
            if (!$items["m"].attr("data-default")) $items["m"].attr("data-default", $items["m"].text());
            if ($items["m"].attr("data-filter")) $items["m"].text($items["m"].attr("data-filter"));
            $items["m"].removeClass("d-none");
          }
          y = str[2];
        } else y = "";
        if (sel)
          for (x in sel) {
            sel[x].trigger("click");
            if (typeof y == "boolean" && sel[x].closest(".cities2-wrap.js-where-" + (y ? "search" : "default")).length) {
              setTimeout(
                ($el) => {
                  $el.closest(".form-item__body")[0].scrollTo({ top: $el[0].offsetTop });
                },
                1,
                sel[x]
              );
            }
          }
        $items = data["datesList"] ? data["datesList"].split(",") : [];
        $tpl = $form.find(".js-search__input_dates");
        $cur = formCalendarProps[$tpl.attr("id")];
        $cur["startDate"] = moment(data["dateStart"], "YYMMDD");
        $cur["endDate"] = moment(data["dateEnd"], "YYMMDD");
        $cur["minDate"] = moment($items ? $items[0] : data["dateStart"], "YYMMDD");
        $cur["maxDate"] = moment($items ? $items[$items.length - 1] : data["dateEnd"], "YYMMDD");
        $tpl
          .val(data["dates"])
          .attr({ "data-start": data["dateStart"], "data-end": data["dateEnd"], "data-list": data["datesList"] })
          .addClass("search-field__input_value")
          .daterangepicker($cur)
          .trigger("change");
        let $nights = $form.find(".js-search__input_nights");
        $items = $nights.closest(".js-search__block").find(".form-item__body .nights__grid");
        $tpl = $items.find(".nights__item.hidden").first();
        $nights.val("").attr("data-value", "");
        $items.find(".nights__item-inner").removeClass("nights__item-inner--active").prop("disabled", true);
        for (x = 0, y = 0; x < data["nights"].length; x++) {
          $cur = $items.find('.nights__item-inner[data-id="' + data["nights"][x]["id"] + '"]');
          $cur.prop("disabled", null);
          if (data["nights"][x]["sel"]) {
            if (y === 0) {
              $nights.closest(".js-search__block").attr("data-first", true);
              $cur.trigger("click");
              y = false;
            } else y = $cur;
          }
        }
        if (y) y.trigger("click");
        if (data["datesList"] && $form.closest(".js-hotel-search__wrapper.hidden").length) {
          $form.closest(".js-hotel-search__wrapper.hidden").removeClass("hidden");
          $(".js-hotel-search-submit")
            .attr("href", "#")
            .click(function (e) {
              e.preventDefault();
              $form.trigger("submit");
              return false;
            });
        }
        if (is_anysearch) {
          $form.attr("data-use-tour", data["forceUrl"] == "tours" ? "1" : null);
          $form.attr("data-use-hotel", data["forceUrl"] == "hotels" ? "1" : null);
          $form.attr("data-use-excursion", data["forceUrl"] == "excursions" ? "1" : null);
        }
      },
      complete: function () {
        $form.find(".js-search__input_from").prop("disabled", null);
        $form.find(".js-search__input_where").prop("disabled", null);
        $form.find(".js-search__input_dates").prop("disabled", null);
        $form.find(".js-search__input_nights").prop("disabled", null);
        $form.attr("data-process", null);
      },
    });
  };

  let onSearchSelectItemClick = function (evt) {
    evt.preventDefault();

    let $this = $(this);

    if ($this.closest(".members__add").length || $this.hasClass("btns__list-item")) return;

    let $block = $this.closest(".js-search__block"),
      $form = $this.closest("form"),
      $inp = $block.find(".form-item__input"),
      is_where = $inp.hasClass("js-search__input_where");
    $inp
      .val($.trim(is_where && $this.closest(".cities2__item._country").length ? $this.find(".js-element-text").text() : $this.text()))
      .attr({ "data-value": $this.attr("data-id") || "", "data-return": null });
    if (is_where) $inp.attr("data-name", $inp.val());
    if ($inp.val()) {
      $inp.removeClass("error");
      $block.removeClass("form-item--empty");
    } else $block.addClass("form-item--empty");

    //        $block.removeClass(fieldActiveClass);

    if (($inp.hasClass("js-search__input_from") || $inp.hasClass("js-search__input_where")) && !$form.attr("data-process")) {
      $form.find(".js-search__input_from").prop("disabled", true);
      $form.find(".js-search__input_where").prop("disabled", true);
      $form.find(".js-search__input_dates").prop("disabled", true);
      $form.find(".js-search__input_nights").prop("disabled", true);
      let is_from = $inp.hasClass("js-search__input_from") ? 1 : 0,
        data = [
          { name: "from", value: $form.find(".js-search__input_from").attr("data-value") },
          { name: "where", value: $form.find(".js-search__input_where").attr("data-value") || $form.attr("data-where") },
          { name: "is_from", value: is_from },
        ],
        hotel_id = $form.attr("data-hotel");
      if (hotel_id) data.push({ name: "hotel", value: hotel_id });

      $block.removeClass("form-item--active");
      $inp.trigger("blur");

      searchFormFieldsAjax($form, data, is_from, 0);
    }
  };

  $(document).on("input", ".js-search__form .js-search__input_where", function () {
    let $this = $(this),
      $form = $this.closest("form"),
      $items = $this.closest(".js-search__block").find(".form-item__body"),
      $tpl = $items.find("li.hidden").first(),
      str = multiStr($this.attr("data-empty") ? "" : $this.val().trim().toLowerCase()),
      key = $form.attr("data-action-ajax"),
      $cur,
      x;

    $this.attr("data-return", null);

    if (!searchCountries[key]) {
      setTimeout(function () {
        $this.trigger("focusin").trigger("input");
      }, 200);
      return;
    }

    str[2] = str[0].length > 1;
    $items = { d: $items.find(".js-where-default"), s: $items.find(".js-where-search") };

    if (!str[2]) {
      $items["d"].removeClass("hidden");
      $items["s"].addClass("hidden");
      return;
    }

    $items["sp"] = $items["s"].find(".cities2-wrap__part-title._popular").next(".cities2");
    $items["so"] = $items["s"].find(".cities2-wrap__part-title:not(._popular)").next(".cities2");
    $tpl = { sp: $items["sp"].find("li.hidden").first(), so: $items["so"].find("li.hidden").first() };

    $items["s"].removeClass("hidden");
    $items["d"].addClass("hidden");

    $items["sp"].find("li:not(.hidden)").remove();
    $items["sp"].find("li").slice(1).remove();
    $items["so"].find("li:not(.hidden)").remove();
    $items["so"].find("li").slice(1).remove();

    for (x = 0; x < searchCountries[key].length; x++) {
      str[3] = searchCountries[key][x]["name"].trim().toLowerCase();
      if (str[2] && str[3].indexOf(str[0]) < 0 && (str[1].length < 2 || str[3].indexOf(str[1]) < 0)) continue;
      $cur = $tpl["sp"].clone().removeClass("hidden d-none");
      $cur.attr({ "data-id": searchCountries[key][x]["id"], "data-visa": searchCountries[key][x]["visa"] ? "yes" : "no" });
      $cur.find(".js-element-icon").html(searchCountries[key][x]["icon"] || "");
      if ($cur.hasClass("js-element-text")) $cur.text(searchCountries[key][x]["name"]);
      else $cur.find(".js-element-text").text(searchCountries[key][x]["name"]);
      $cur.find(".js-element-text2").text(searchCountries[key][x]["ext"] || "");
      $items["sp"].append($cur);
    }
    if (str[2] && searchResorts[key].length) {
      for (x = 0; x < searchResorts[key].length; x++) {
        str[3] = searchResorts[key][x]["name"].trim().toLowerCase();
        str[4] = str[3] + " " + searchResorts[key][x]["ext"].trim().toLowerCase();
        if (
          str[2] &&
          str[3].indexOf(str[0]) < 0 &&
          str[4].indexOf(str[0]) != 0 &&
          (str[1].length < 2 || (str[3].indexOf(str[1]) < 0 && str[4].indexOf(str[1]) != 0))
        )
          continue;
        $cur = $tpl["so"].clone().removeClass("hidden d-none");
        $cur.attr("data-id", searchResorts[key][x]["id"]);
        $cur.find(".js-element-icon").html(searchResorts[key][x]["icon"] || "");
        if ($cur.hasClass("js-element-text")) $cur.text(searchResorts[key][x]["name"]);
        else $cur.find(".js-element-text").text(searchResorts[key][x]["name"]);
        $cur.find(".js-element-text2").text(searchResorts[key][x]["ext"] || "");
        $items["so"].append($cur);
      }
    }

    if ($items["sp"].find("li:not(.hidden)").length) $items["sp"].closest(".cities2-wrap__part").removeClass("hidden");
    else $items["sp"].closest(".cities2-wrap__part").addClass("hidden");

    if ($items["so"].find("li:not(.hidden)").length) $items["so"].closest(".cities2-wrap__part").removeClass("hidden");
    else $items["so"].closest(".cities2-wrap__part").addClass("hidden");

    if ($items["so"].closest(".cities2-wrap").find(".cities2-wrap__part:not(.hidden)").length)
      $items["so"].closest(".cities2-wrap").removeClass("hidden");
    else $items["so"].closest(".cities2-wrap").addClass("hidden");

    findInSelect($form.find(".js-header-form-to"), ".form-item", ".js-where-default .cities2 a", ".form-item__no-result");
    findInSelect($form.find(".js-header-form-to"), ".form-item", ".js-where-search .cities2 a", ".form-item__no-result");

    $items["m"] = $items["s"].closest(".form-item").find(".form-item__no-result");
    if ($items["sp"].find("li:not(.hidden)").length || $items["so"].find("li:not(.hidden)").length) {
      if ($items["m"].attr("data-default")) $items["m"].text($items["m"].attr("data-default"));
      $items["m"].addClass("d-none");
    } else {
      if (!$items["m"].attr("data-default")) $items["m"].attr("data-default", $items["m"].text());
      if ($items["m"].attr("data-filter")) $items["m"].text($items["m"].attr("data-filter"));
      $items["m"].removeClass("d-none");
    }
  });

  $(document).on("focusout", ".js-search__form .js-search__input_where", function (evt) {
    let $this = $(this),
      $find = $this.closest(".form-item").find(".js-where-search .cities2 li:not(.hidden) a"),
      txt;
    if ((!$this.val() || !$find.length) && $this.attr("data-value")) {
      if (evt.relatedTarget && (evt.relatedTarget.classList.contains(".cities2") || evt.relatedTarget.closest(".cities2"))) {
        if (!$this.attr("data-todef")) {
          $this.attr(
            "data-todef",
            setTimeout(() => {
              /*if ($this.attr('data-return'))*/ $this.val($this.attr("data-name")).trigger("focusin");
              //else $this.val('').attr('data-value', '');
            }, 5)
          );
        }
      } else {
        /*if ($this.attr('data-return'))*/ $this.val($this.attr("data-name")).trigger("focusin");
        //else $this.val('').attr('data-value', '');
      }
    } else if ($find.length == 1) {
      $find = $find.slice(0, 1);
      if ($this.attr("data-value") != $find.closest("li").attr("data-id")) $find.trigger("click");
      else {
        txt = $.trim($find.closest(".cities2__item._country").length ? $find.find(".js-element-text").text() : $find.text());
        if ($this.val() != txt) $this.val(txt).trigger("input");
      }
    } else if ($this.attr("data-value")) {
      if (!$this.attr("data-todef")) {
        $this.attr(
          "data-todef",
          setTimeout(() => {
            $this.val($this.attr("data-name")).trigger("input");
          }, 250)
        );
      }
    }
  });

  let cancelSearchFormWhereToDef = function ($el) {
    let $inp = $el.closest(".form-item").find(".js-search__input_where"),
      to = $inp.length ? $inp.attr("data-todef") : 0;
    if (to) {
      clearTimeout(to);
      $inp.attr("data-todef", null);
    }
  };

  $(document).on("click", ".js-search__form .form-item--cities2 .form-item__body", function () {
    cancelSearchFormWhereToDef($(this));
  });

  $(document).on("click", ".js-search__form .form-item--cities2 .form-item__btn-clear", function () {
    cancelSearchFormWhereToDef($(this));
  });

  $(document).on("click", ".js-search__form .js-where-search .cities2 a", function () {
    var $this = $(this),
      id = parseInt($this.closest(".cities2__item").attr("data-id")),
      $block = $this.closest(".form-item"),
      $rel = $block.find('.js-where-default .cities2 .cities2__item[data-id="' + id + '"]');
    $block.find(".js-where-default .cities2 a.active").removeClass("active");
    if ($rel.length) {
      $rel.find("a").addClass("active");
      $block.find(".js-where-default").removeClass("hidden");
      $block.find(".js-where-search").addClass("hidden");
      setTimeout(
        ($el) => {
          $el.closest(".form-item__body")[0].scrollTo({ top: $el[0].offsetTop });
        },
        1,
        $rel
      );
    }
  });

  $(document).on("click", ".js-search__form .form-item__body li:not(.nights__item)", onSearchSelectItemClick);

  let onSearchNightsItemClick = function (evt) {
    evt.preventDefault();

    let $this = $(this),
      $list = $this.closest(".nights__grid"),
      $block = $this.closest(".js-search__block"),
      $inp = $block.find(".js-search__input_nights"),
      val = "",
      last = false,
      is_range = false;

    if ($block.attr("data-first")) {
      $list.find(".nights__item-inner--active").removeClass("nights__item-inner--active");
      $this.addClass("nights__item-inner--active");
      $block.attr("data-first", null);
    } else {
      last = parseInt($list.find(".nights__item-inner--active").attr("data-id"));
      val = parseInt($this.attr("data-id"));
      if (last != val) {
        last = [Math.min(last, val), Math.max(last, val)];
        $list.find(".nights__item-inner").each(function () {
          if ($(this).prop("disabled")) return;
          val = parseInt($(this).attr("data-id"));
          if (val >= last[0] && val <= last[1]) $(this).addClass("nights__item-inner--active");
        });
      }
      $block.removeClass("form-item--active");
      val = "";
      last = false;
    }
    $this
      .closest(".nights__grid")
      .find(".nights__item-inner--active")
      .each(function () {
        let id = parseInt($(this).attr("data-id"));
        if (!last) {
          val = id;
        } else if (id == last + 1) {
          is_range = true;
        } else {
          val += (is_range ? "-" + last : "") + "," + id;
          is_range = false;
        }
        last = id;
      });
    if (is_range) val += "-" + last;
    $inp.val(val).attr("data-value", val).removeClass("error").closest(".js-search__block").removeClass("form-item--empty");
    if (is_range) closePopups();
  };

  $(document).on("click", ".js-search__form .nights__grid .nights__item-inner", onSearchNightsItemClick);

  let onSearchFormSubmit = function (evt) {
    evt.preventDefault();

    let $this = $(this),
      from = $this.find(".js-search__input_from").attr("data-value"),
      where = $this.find(".js-search__input_where").attr("data-value"),
      resort = 0,
      dateStart = $this.find(".js-search__input_dates").attr("data-start"),
      dateEnd = $this.find(".js-search__input_dates").attr("data-end"),
      nights = $this.find(".js-search__input_nights").attr("data-value").replace("-", ",").split(","),
      adults = $this.find(".js-search__input_adults").attr("data-value"),
      childs = $this.find(".js-search__input_childs").attr("data-value"),
      url = $this.attr(from ? "data-action-tour" : "data-action-hotel");
    if (!where) {
      $this.find(".js-search__input_where").addClass("error");
    } else {
      resort = where.split("/", 2);
      if (resort.length > 1) {
        where = resort[0];
        resort = resort[1];
      } else resort = 0;
    }
    if (!dateStart || !dateEnd) {
      $this.find(".js-search__input_dates").addClass("error");
    }
    if (!nights.length || nights[0] < 1) {
      $this.find(".js-search__input_nights").addClass("error");
    }
    if (!adults && !childs) {
      $this.find(".js-search__input_adults").addClass("error");
    }
    if ($this.find(".error").length) {
      evt.preventDefault();
      return false;
    }
    url +=
      (from ? "?TOWNFROMINC=" + from + "&" : "?") +
      "STATEINC=" +
      where +
      (resort ? "&TOWNS=" + resort : "") +
      "&CHECKIN_BEG=20" +
      dateStart +
      "&NIGHTS_FROM=" +
      nights[0] +
      "&CHECKIN_END=20" +
      dateEnd +
      "&NIGHTS_TILL=" +
      nights[nights.length - 1] +
      "&ADULT=" +
      adults +
      (childs ? "&CHILD=" + childs : "") +
      "&DOLOAD=1";
    $('<a href="' + url + '" target="_blank"></a>')
      .appendTo($("body"))[0]
      .click();
  };

  $(document).on("submit", "form.js-top-search__form", onSearchFormSubmit);

  let onHotelSearchFormSubmit = function (evt) {
    evt.preventDefault();

    let $this = $(this),
      from = parseInt($this.find(".js-search__input_from").attr("data-value")),
      where = $this.attr("data-where"),
      hotel = $this.attr("data-hotel"),
      dateStart = $this.find(".js-search__input_dates").attr("data-start"),
      dateEnd = $this.find(".js-search__input_dates").attr("data-end"),
      nights = $this.find(".js-search__input_nights").attr("data-value").replace("-", ",").split(","),
      adults = $this.find(".js-search__input_adults").attr("data-value"),
      childs = $this.find(".js-search__input_childs").attr("data-value"),
      url = $this.attr(from ? "data-action-tour" : "data-action-hotel");
    if ($this.attr("data-use-tour")) url = $this.attr("data-action-tour");
    else if ($this.attr("data-use-hotel")) url = $this.attr("data-action-hotel");
    else if ($this.attr("data-use-excursion")) url = $this.attr("data-action-excursion");
    if (!dateStart || !dateEnd) {
      $this.find(".js-search__input_dates").addClass("error");
    }
    if (!nights.length || nights[0] < 1) {
      $this.find(".js-search__input_nights").addClass("error");
    }
    if (!adults && !childs) {
      $this.find(".js-search__input_adults").addClass("error");
    }
    if ($this.find(".error").length) {
      evt.preventDefault();
      return false;
    }
    url +=
      (from ? "?TOWNFROMINC=" + from + "&" : "?") +
      "STATEINC=" +
      where +
      "&HOTELS=" +
      hotel +
      "&CHECKIN_BEG=20" +
      dateStart +
      "&NIGHTS_FROM=" +
      nights[0] +
      "&CHECKIN_END=20" +
      dateEnd +
      "&NIGHTS_TILL=" +
      nights[nights.length - 1] +
      "&ADULT=" +
      adults +
      (childs ? "&CHILD=" + childs : "") +
      "&DOLOAD=1";
    $('<a href="' + url + '" target="_blank"></a>')
      .appendTo($("body"))[0]
      .click();
  };

  $(document).on("submit", "form.js-hotel-search__form", onHotelSearchFormSubmit);

  if ($(".js-search__form").length) {
    $(".js-search__form").each(function () {
      let $this = $(this),
        data = "is_init=1",
        selects_init = 0,
        is_anysearch = 0;
      if ($this.hasClass("js-top-search__form")) {
        data +=
          "&where_q=" +
          /*$this.find('.js-search__input_where').val() || */ "" +
          "&where=" +
          ($this.find(".js-search__input_where").attr("data-value") || $this.attr("data-where") || "");
        selects_init = 3;
      } else if ($this.hasClass("js-hotel-search__form")) {
        data += "&hotel=" + ($this.attr("data-hotel") || "") + "&anysearch=1";
        selects_init = 2;
        is_anysearch = 1;
      }
      searchFormFieldsAjax($this, data, 3, is_anysearch);
    });
  }

  let onEventsResortsBtnClick = function () {
    let $this = $(this),
      id = $this.closest(".btns__list-item").attr("data-id");
    if ($this.hasClass("active")) return;
    $this.closest(".btns__list").find(".btn.active").removeClass("active");
    $this.addClass("active");
    $this
      .closest(".events")
      .find(".events__body tr[data-resort]")
      .each(function () {
        if (!id || $(this).attr("data-resort") == id) $(this).removeClass("hidden");
        else $(this).addClass("hidden");
      });
  };

  $(".events .btns__list .btn").on("click", onEventsResortsBtnClick);

  const fancyboxL10n = {
    CLOSE: "Закрыть",
    NEXT: "Далее",
    PREV: "Назад",
    MODAL: "Окно можно закрыть нажатием клавиши ESC",
    ERROR: "Ошибка! Пожалуйста, попробуйте позже",
    IMAGE_ERROR: "Изображение не найдено",
    ELEMENT_NOT_FOUND: "HTML элемент не найден",
    IFRAME_ERROR: "Ошибка загрузки страницы",
    TOGGLE_ZOOM: "Масштабирование",
    TOGGLE_THUMBS: "Показ миниатюр",
    TOGGLE_SLIDESHOW: "Слайдшоу",
    TOGGLE_FULLSCREEN: "Полноэкранный режим",
    DOWNLOAD: "Загрузка",
  };

  Fancybox.bind("[data-tour-gallery]", {
    idle: false,
    l10n: fancyboxL10n,
    Carousel: {
      transition: "classic",
      friction: 0.3,
    },
    Thumbs: {
      type: "classic",
    },
    placeFocusBack: false,
    groupAttr: "data-tour-gallery",
  });

  const popap2 = (() => {
    Fancybox.bind("[data-popup2]", {
      autoFocus: false,
      Carousel: {
        transition: "classic",
        friction: 0.3,
      },
      closeButton: false,
      compact: true,
      defaultType: "inline",
      defaultDisplay: "flex",
      dragToClose: false,
      idle: false,
      l10n: fancyboxL10n,
      mainClass: "popup2-init",
      on: {
        // "*": (fancybox, eventName) => {
        //     console.log(`fancybox eventName: ${eventName}`);
        // },
        init: (f) => {},
      },
    });
  })();

  const popapMap = (() => {
    Fancybox.bind("[data-map]", {
      autoFocus: false,
      Carousel: {
        transition: "classic",
        friction: 0.3,
      },
      closeButton: false,
      compact: true,
      defaultType: "inline",
      defaultDisplay: "flex",
      dragToClose: false,
      idle: false,
      l10n: fancyboxL10n,
      mainClass: "popup-map-init",
      on: {
        // "*": (fancybox, eventName) => {
        //     console.log(`fancybox eventName: ${eventName}`);
        // },
        init: (f) => {},
        reveal: (f) => {
          if (popupMap) popupMap.invalidateSize(false);
        },
      },
      placeFocusBack: false,
    });
  })();

  const popapPackage = (() => {
    let fInnerSlider;
    let fInnerSliderThumbs;
    let triggerEl;
    let level;

    Fancybox.bind("[data-package]", {
      autoFocus: false,
      Carousel: {
        transition: "classic",
        friction: 0.3,
      },
      closeButton: false,
      compact: true,
      defaultType: "inline",
      defaultDisplay: "flex",
      dragToClose: false,
      idle: false,
      l10n: fancyboxL10n,
      mainClass: "popup-package-init",
      on: {
        // "*": (fancybox, eventName) => {
        //     console.log(`fancybox eventName: ${eventName}`);
        // },
        reveal: (fancybox, slide) => {
          triggerEl = slide.triggerEl;
          level = slide.triggerEl.dataset.level;
          if (level) {
            slide.contentEl.querySelector(`[data-src="${level}"]`)?.click();
          }

          fInnerSliderThumbs = initPopupPackageSliderThumbs(slide.el);
          fInnerSlider = initPopupPackageSlider(slide.el, fInnerSliderThumbs);

          const carouselTrigger = triggerEl.closest(".card-package__imgs-item");
          if (carouselTrigger) {
            const siblings = carouselTrigger.parentNode.children;
            const i = [...siblings].findIndex((el) => el === carouselTrigger);
            if (i > 0) fInnerSlider.slideTo(i);
          }
        },
        destroy: () => {
          fInnerSliderThumbs?.destroy(true, true);
          fInnerSlider?.destroy(true, true);
          fInnerSlider = null;
          fInnerSliderThumbs = null;
          triggerEl = null;
          level = undefined;
        },
        click: (fancybox, e) => {
          const btn = e.target.closest(`.popup-package__mark-btn`);
          if (btn) {
            level = btn.dataset.src;
            triggerEl.closest(".card-package")?.querySelector(`.card-package__mark-btn[data-src="${level}"]`)?.click();
          }
        },
      },
      placeFocusBack: false,
    });

    function initPopupPackageSlider(container, thumbs = false) {
      const containerCarousel = container?.querySelector(".popup-package__slider");
      if (!containerCarousel) return;
      if (containerCarousel.querySelectorAll(".popup-package__slide-inner img").length < 2) return;

      return new Swiper(containerCarousel, {
        loop: true,
        thumbs: {
          swiper: thumbs,
        },
        wrapperClass: "popup-package__slider-list",
        slideClass: "popup-package__slide",
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        watchOverflow: true,
        slidesPerView: "auto",
        spaceBetween: 0,
        navigation: {
          nextEl: ".popup-package__slider-btns .slider-btn2--next",
          prevEl: ".popup-package__slider-btns .slider-btn2--prev",
        },
        pagination: {
          el: ".popup-package__slider-pagination",
          type: "fraction",
        },
      });
    }

    function initPopupPackageSliderThumbs(container) {
      const containerCarousel = container?.querySelector(".popup-package__thumbs");
      if (!containerCarousel) return;
      if (containerCarousel.querySelectorAll(".popup-package__thumb-inner img").length < 2) return;

      return new Swiper(containerCarousel, {
        freeMode: true,
        watchSlidesProgress: true,
        wrapperClass: "popup-package__thumbs-list",
        slideClass: "popup-package__thumb",
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 12,
        watchOverflow: true,
      });
    }

    (function initTabs() {
      const classes = {
        popup: "popup-package",
        markBtn: "popup-package__mark-btn",
        markBtnCurrent: "_current",
        markedItems: "_marked",
        markedItemsHidden: "hidden",
      };

      document.body.addEventListener("click", (e) => {
        const btn = e.target.closest(`.${classes.markBtn}`);
        if (!btn) return;
        e.preventDefault();

        if (btn.classList.contains(classes.markBtnCurrent)) return;

        const popup = btn.closest(`.${classes.popup}`);
        if (!popup) return;
        const btnSrc = btn.dataset.src;

        popup
          .querySelectorAll(`.${classes.markedItems}:not(.${classes.markedItemsHidden})`)
          .forEach((item) => item.classList.add(classes.markedItemsHidden));
        popup.querySelectorAll(`.${classes.markedItems}._${btnSrc}`).forEach((item) => item.classList.remove(classes.markedItemsHidden));

        popup.querySelector(`.${classes.markBtn}.${classes.markBtnCurrent}`)?.classList.remove(classes.markBtnCurrent);
        btn.classList.add(classes.markBtnCurrent);
      });
    })();

    return {
      getSlider: () => fInnerSlider,
      getThumbs: () => fInnerSliderThumbs,
    };
  })();

  const sliderContent = (() => {
    const classes = {
      block: "slider-content",
    };

    const block = document.querySelector(`.${classes.block}`);

    const fInnerSliderThumbs = initBlockPackageSliderThumbs(block);
    const fInnerSlider = initBlockPackageSlider(block, fInnerSliderThumbs);

    function initBlockPackageSlider(container, thumbs = false) {
      const containerCarousel = container?.querySelector(".slider-content__slider");
      if (!containerCarousel) return;
      if (containerCarousel.querySelectorAll(".slider-content__slide-inner img").length < 2) return;

      return new Swiper(containerCarousel, {
        loop: true,
        thumbs: {
          swiper: thumbs,
        },
        wrapperClass: "slider-content__slider-list",
        slideClass: "slider-content__slide",
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        watchOverflow: true,
        slidesPerView: "auto",
        spaceBetween: 0,
        navigation: {
          nextEl: ".slider-content__slider-btns .slider-btn2--next",
          prevEl: ".slider-content__slider-btns .slider-btn2--prev",
        },
        pagination: {
          el: ".slider-content__slider-pagination",
          type: "fraction",
        },
      });
    }

    function initBlockPackageSliderThumbs(container) {
      const containerCarousel = container?.querySelector(".slider-content__thumbs");

      if (!containerCarousel) return;
      if (containerCarousel.querySelectorAll(".slider-content__thumb-inner img").length < 2) return;

      return new Swiper(containerCarousel, {
        freeMode: true,
        watchSlidesProgress: true,
        wrapperClass: "slider-content__thumbs-list",
        slideClass: "slider-content__thumb",
        observer: true,
        observeSlideChildren: true,
        observeParents: true,
        slidesPerView: "auto",
        spaceBetween: 12,
        watchOverflow: true,
      });
    }
  })();

  if ($(".js-margin-if-content .content-block").length > 0) {
    $(".js-margin-if-content .content-block").each(function () {
      if ($(this).text().trim().length > 0) {
        $(this).closest(".js-margin-if-content").addClass("margin-bottom-f");
      }
    });
  }

  // fix counter-reset
  const olWithStarts = document.querySelectorAll("ol[start]");

  olWithStarts.forEach((ol) => {
    const start = ol.getAttribute("start") - 1;
    ol.style = "counter-reset: list " + start;
  });

  // Показ скрытого окна рекламного баннера
  $(".the-note__btn").on("click", function () {
    $(this).siblings(".the-note__main").toggleClass("active");
  });

  $(".the-note__main-link").on("click", function (e) {
    e.preventDefault();

    navigator.clipboard.writeText($(this).data("token"));
  });

  /* subscribe */
  let submitBtns = document.querySelectorAll(".subscribe-field__submit");
  submitBtns.forEach((btn) => {
    let form = btn.closest("form");

    form.addEventListener("submit", showSubscribePopup);
  });

  function showSubscribePopup(e, email) {
    e.preventDefault();

    const popupForm = "#popup-mc-embedded-subscribe-form";

    Fancybox.show(
      [
        {
          src: "#popup-mc-embedded-subscribe-form",
          type: "inline",
        },
      ],
      {
        compact: false,
        dragToClose: false,
      }
    );

    $("#popup-mc-embedded-subscribe-form")
      .find('input[name="email"]')
      .val(e.target.querySelector("input[type=email]").value)[0]
      .dispatchEvent(new Event("input"));

    return false;
  }

  function emitSubscribeMessage(e) {
    e.preventDefault();
    const formBlock = e.target.closest(".subscribe"),
      alert = document.querySelector(".alert");

    if (formBlock.querySelector(".subscribe__message") !== null) {
      formBlock.classList.add("subscribe--message");

      setTimeout(() => {
        formBlock.classList.remove("subscribe--message");
      }, 3300);
    } else if (alert !== null) {
      alert.classList.add("active");

      setTimeout(function () {
        alert.classList.remove("active");
      }, 2000);
    }
  }

  $(document).on("submit", "#tour-booking form", function () {
    var $form = $(this);
    window.setTimeout(function () {
      if (!$form.find(".b24-form-control-alert").length) {
        try {
          _tmr.push({ type: "reachGoal", id: 3487225, goal: "test" });
        } catch (e) {}
      }
    }, 300);
  });

  /* Print page */
  let initPrintPage = function () {
    const form = document.forms.r_e_page_print;
    if (!form) return;

    const body = document.querySelector("body");
    const page = document.querySelector(".page");
    const pageHash = "#?print";

    loadPage();

    let container = page.querySelector(".js-printdata");

    window.addEventListener("reload", backPage);
    window.addEventListener("popstate", changePage);
    form.addEventListener("submit", formHandler);

    function formHandler(e) {
      e.preventDefault();
      setData();
      showPage(e);
      printData();
      // getSlidersImg();
      // checkImgsAndPrint();
      print();
    }

    function loadPage() {
      getData();

      if (window.location.hash === pageHash) {
        window.location.hash = "";
      }
    }

    function showPage(e) {
      e.target.closest(".popup-print").querySelector(".is-close-btn").click();
      body.classList.add("body-print");
      page.classList.add("page--print");

      if (window.location.hash !== pageHash) {
        history.pushState(null, null, pageHash);
      }
    }

    function backPage() {
      if (window.location.hash !== pageHash) return;
      history.back();
    }

    function changePage() {
      if (window.location.hash === pageHash) {
        body.classList.add("body-print");
        page.classList.add("page--print");
      } else {
        body.classList.remove("body-print");
        page.classList.remove("page--print");
      }
    }

    function setData() {
      let data = {};
      for (let i = 0; i < form.elements.length - 1; i++) {
        const el = form.elements[i];
        data[el.name] = el.value;
      }
      localStorage.setItem(form.name, JSON.stringify(data));
    }

    function getData() {
      let data = JSON.parse(localStorage.getItem(form.name));
      if (data === null) return;

      for (const key in data) {
        if (Object.hasOwnProperty.call(data, key)) {
          form.elements[key].value = data[key];
        }
      }
    }

    function printData() {
      let data = JSON.parse(localStorage.getItem(form.name));
      if (data === null) return;
      if (container === null) {
        document.querySelector(".header__inner").insertAdjacentHTML("beforeend", '<div class="js-printdata" style="display:none;"></div>');
        container = document.querySelector(".js-printdata");
      }

      container.innerHTML = "";
      for (const key in data) {
        if (Object.hasOwnProperty.call(data, key)) {
          container.innerHTML += `<div>${data[key]}</div>`;
        }
      }
    }

    // function getSlidersImg() {
    //     const sliders = page.querySelectorAll('.slider, .slider-simple');
    //     if (sliders.length === 0 || page.querySelector('.js-img')) return;

    //     sliders.forEach(slider => {
    //         const link = slider.querySelector('li>a:not(.slider__img-box--video)')
    //         const img = slider.querySelector('li>*:not(.slider__img-box--video) img')

    //         link ? setImg(link.href) : setImg(img.src);

    //         function setImg(src) {
    //             slider.insertAdjacentHTML(
    //                 'beforeend',
    //                 '<img class="half-img js-img" src="' + src + '" alt="">'
    //             );
    //         }
    //     })
    // }

    // function checkImgsAndPrint() {
    //     let imgs = page.querySelectorAll('.js-img')

    //     if (imgs.length > 0 && imgs[imgs.length - 1].onload === null) {
    //         imgs.forEach(img => {
    //             img.onload = function () {
    //                 if (img === imgs[imgs.length - 1]) {
    //                     setTimeout(() => {
    //                         print()
    //                     }, 500);
    //                 }
    //             };
    //         })
    //     }
    //     else {
    //         print()
    //     }
    // }

    function print() {
      setTimeout(() => {
        window.print();
      }, 500);
    }
  };

  initPrintPage();

  /* Share link clipboard */
  $("body").on("click", function (e) {
    if (!e.target.classList.contains("js-share-link-clipboard")) return;

    const alert = $("#popup-share .alert--fixed");

    alert.addClass("active");

    setTimeout(function () {
      alert.removeClass("active");
    }, 2000);

    e.preventDefault();
    e.stopPropagation();

    copy2clipboard($.trim(e.target.getAttribute("data-clipboard") || e.target.getAttribute("href")));
  });

  $(".js-post-tours-block.hidden[data-post]").each(function () {
    var $this = $(this);
    $.ajax({
      url: $this.attr("data-ajax"),
      data: {
        post: $this.attr("data-post"),
      },
      cache: false,
      dataType: "html",
      timeout: 10000,
      type: "post",
      error: function (xhr, status, err) {
        // Error handling without console output
      },
      success: function (data) {
        if (data) {
          $(".js-post-tours-area.hidden").removeClass("hidden");
          $this.removeClass("hidden").find(".hotels__list").html(data);
        }
      },
    });
  });

  function copy2clipboard(text, callback /* optional */) {
    // use modern clipboard API
    if (navigator.clipboard) {
      navigator.clipboard
        .writeText(text)
        .then(function () {
          // if a callback is provided, call it
          callback && callback();
          return true;
        })
        .catch(function (err) {
          errorMessage(err);
          return false;
        });
      return true;
    }
    // use old document.execCommand('copy')
    else {
      // create a temporary textArea containing the text
      var textArea = document.createElement("textarea"),
        isCopied = false;
      textArea.setAttribute("style", "width:1px;border:0;opacity:0;");
      document.body.appendChild(textArea);
      textArea.value = text;

      // select the textArea
      textArea.select();

      try {
        // copy from textArea
        isCopied = document.execCommand("copy");

        // if copy was successful, and a callback is provided, call it. if copy failed, display error message
        isCopied ? callback && callback() : errorMessage();
      } catch (err) {
        errorMessage(err);
      }
      // remove temporary textArea
      document.body.removeChild(textArea);
      return isCopied;
    }
  }

  function errorMessage(msg) {
    // Error handling without console output
  }

  /* show-more blog-list__item - ajax */
  $(".blog-list__btn-more").on("click", function (e) {
    var $btn = $(this),
      block = $btn.closest(".blog-list2")[0],
      list = block.querySelector(".blog-list2__list"),
      offset = parseInt($btn.data("offset"));

    $btn.addClass("btn-row--load").prop("disabled", true);

    $.ajax({
      url: $(this).data("url") + "?offset=" + offset,
      cache: false,
      dataType: "json",
      type: "get",
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");
      },
      success: function (data) {
        if (data["num"] + offset >= parseInt($btn.data("total"))) {
          hideBtn();
        } else {
          $btn
            .data("offset", offset + data["num"])
            .removeClass("btn-row--load")
            .prop("disabled", false);
        }

        $(data["html"]).appendTo($(list));
      },
    });

    function hideBtn() {
      var btnWrap = $btn.closest(".blog-list2__btn-wrap");
      btnWrap.css("opacity", "0");
      setTimeout(() => {
        btnWrap.slideUp();
        $btn.removeClass("btn-row--load");
      }, 300);
    }
  });

  /* show-more benefits ajax */
  $('.btn-row3[data-chapter="actions"]').on("click", function (e) {
    e.preventDefault();

    const cl_load = "btn-row3--load",
      prop_diabled = "disabled",
      num_loaded = $(".actions2__item").length,
      $btn = $(this);

    $btn.addClass(cl_load).prop(prop_diabled, true);

    $.ajax({
      url:
        "//" +
        window.location.hostname +
        "/promotions/load/" +
        "?country_id=" +
        $btn.data("country_id") +
        "&type_id=" +
        $btn.data("type_id") +
        "&is_archive=" +
        $btn.data("is_archive") +
        "&offset=" +
        num_loaded,
      cache: false,
      dataType: "html",
      type: "get",
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");
      },
      success: function (html) {
        $(html).appendTo($(".actions2__list"));
      },
    }).always(function () {
      if ($(".actions2__item").length >= $btn.data("num")) {
        $btn.remove();
      } else {
        $btn.removeClass(cl_load).prop(prop_diabled, false);
      }
    });
  });

  var $dashamail_form = $('form[name="mc-embedded-subscribe-form"]');

  if ($dashamail_form.length) {
    const dasha_privat_val = "Частное лицо";

    let params = new URLSearchParams(document.location.search);

    let //dasha_is_privat = params.get("is_privat"),
      dasha_subscribe_email = params.get("subscribe_email");

    if (typeof dasha_subscribe_email !== "undefined" && dasha_subscribe_email !== null) {
      document.getElementById("mce-EMAIL").value = dasha_subscribe_email;
    }

    //if (typeof dasha_is_privat !== 'undefined' && dasha_is_privat !== null
    //        && dasha_is_privat === 'true') {

    //    $dashamail_form.find('#mailing_type').val(dasha_privat_val);

    document.getElementById("cities_block").style.display = "none";

    document.getElementById("mailing_city").removeAttribute("required");

    document.getElementById("city-field").style.display = "none";

    document.getElementById("more_merge_3").removeAttribute("required");
    //}
    /*
        document.addEventListener('click', function(e) {
            const target = e.target;

            if (target.id !== 'mailing_type') return false;

            const cityBlock = target.form.querySelector('#cities_block'),
                otherCity = target.form.querySelector('#city-field');


            const intervalID = setInterval(() => {
                const isActive = target.classList.contains('active');

                if (!isActive) {
                    const newVal = target.value;
                    clearInterval(intervalID);

                    if (newVal === dasha_privat_val) {

                        cityBlock.style.display = 'none';

                        document.getElementById('mailing_city').removeAttribute('required');

                        otherCity.style.display = 'none';

                        otherCity.removeAttribute('required');
                    }
                    else {

                        cityBlock.style.display = 'block';

                        document.getElementById('mailing_city').setAttribute('required', 'required');
                    }


                }
            }, 500);
        });
        */
  }

  $dashamail_form.not(".js-sendsay").on("submit", function (e) {
    e.preventDefault();

    var data = [],
      mcity = document.getElementById("mailing_city");

    data.push({ name: "list_id", value: document.getElementById("mailing_type").dataset["value"] });
    data.push({ name: "no_conf", value: "" });
    data.push({ name: "notify", value: "" });
    data.push({ name: "email", value: $(this).find("#mce-EMAIL").val() });
    data.push({ name: "merge_1", value: $(this).find("#mce-MERGE1").val() });

    if (mcity.hasAttribute("required")) {
      data.push({
        name: "merge_3",
        value: !mcity.dataset["value"].match(/^\s*$/g) ? mcity.dataset["value"] : document.getElementById("more_merge_3").value,
      });
    }

    $("#mc-embedded-subscribe").prop("disabled", true);

    $.post($dashamail_form.prop("action"), data)
      .fail(function () {
        //alert("Сервер временно недоступен. Попробуйте повторить позже");
      })
      .always(function () {
        $("#mc-embedded-subscribe").prop("disabled", false);

        var formBlock = $dashamail_form.get(0);

        if (formBlock.querySelector(".mesf-message") === null) {
          return;
        }

        formBlock.classList.add("_message");

        setTimeout(() => {
          if ($("#popup-mc-embedded-subscribe-form").length) {
            Fancybox.close();

            setTimeout(() => {
              formBlock.classList.remove("_message");
            }, 300);
          } else $dashamail_form.remove();
        }, 6600);
      });

    return false;
  });

  $dashamail_form.filter(".js-sendsay").on("submit", function (e) {
    e.preventDefault();

    var data = [],
      mcity = document.getElementById("mailing_city");

    data.push({ name: "email", value: $(this).find("#mce-EMAIL").val() });
    data.push({ name: "name", value: $(this).find("#mce-NAME").val() });

    if (mcity.hasAttribute("required")) {
      data.push({
        name: "city",
        value: !mcity.dataset["value"].match(/^\s*$/g) ? mcity.dataset["value"] : document.getElementById("more_merge_3").value,
      });
    }

    if ($(this).find('[name="confirm"]').is(":checked")) {
      data.push({ name: "confirm", value: $(this).find('[name="confirm"]').val() });
    }

    $("#mc-embedded-subscribe").prop("disabled", true);

    $.post($dashamail_form.attr("data-submit"), data)
      .fail(function () {
        //alert("Сервер временно недоступен. Попробуйте повторить позже");
      })
      .always(function () {
        $("#mc-embedded-subscribe").prop("disabled", false);

        var formBlock = $dashamail_form.get(0);

        if (formBlock.querySelector(".mesf-message") === null) {
          return;
        }

        formBlock.classList.add("_message");

        setTimeout(() => {
          Fancybox.close();
          setTimeout(() => {
            formBlock.classList.remove("_message");
          }, 300);
        }, 6600);
      });

    return false;
  });

  $('.sub-newsletter__info.js-sendsay [name="type"]').on("change", function () {
    var $form = $(this).closest("form"),
      $cities = $("#subscribe_cities");
    if (this.value == "agency") {
      $cities.removeClass("visibility-hidden").find('[name="city"]').prop("required", true).trigger("change");
    } else {
      $cities.addClass("visibility-hidden").find('[name="city"]').prop("required", null);
      $("#subscribe_custom_city").prop("required", null).closest(".hard-input").addClass("hidden");
    }
  });
  $('.sub-newsletter__info.js-sendsay [name="city"]').on("change", function () {
    var $custom = $("#subscribe_custom_city");
    if (this.getAttribute("data-value")) {
      $custom.prop("required", null).closest(".hard-input").addClass("hidden");
    } else {
      $custom.prop("required", true).closest(".hard-input").removeClass("hidden");
    }
  });

  $(".sub-newsletter__info.js-sendsay").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this),
      data = [],
      mcity = document.getElementById("subscribe_city");

    data.push({ name: "email", value: $form.find('[name="email"]').val() });
    data.push({ name: "name", value: $form.find('[name="name"]').val() });

    if (mcity.hasAttribute("required")) {
      data.push({
        name: "city",
        value: !mcity.dataset["value"].match(/^\s*$/g) ? mcity.dataset["value"] : document.getElementById("subscribe_custom_city").value,
      });
    }

    if ($form.find('[name="confirm"]').is(":checked")) {
      data.push({ name: "confirm", value: $form.find('[name="confirm"]').val() });
    }

    $form.find('[type="submit"]').prop("disabled", true);
    var $alert;

    $.post($form.attr("data-submit"), data)
      .fail(function (xhr, status, err) {
        $alert = $form.find(".alert--fixed.alert--error");
        $alert
          .addClass("active")
          .find(".alert__text")
          .text(status + (err.message ? ": " + err.message : ""));

        setTimeout(function () {
          $alert.removeClass("active");
        }, 3000);
      })
      .done(function (resp, status, xhr) {
        if (resp["_err"]) {
          $alert = $form.find(".alert--fixed.alert--error");
          $alert.find(".alert__text").text(resp["_err"]);
        } else {
          $alert = $form.find(".alert--fixed").not(".alert--error");
        }
        $alert.addClass("active");

        setTimeout(function () {
          $alert.removeClass("active");
        }, 3000);
      })
      .always(function () {
        $form.find('[type="submit"]').prop("disabled", null);
      });

    return false;
  });

  $(".js-autocomplete-strict").on("focusout", function () {
    if (this.value) {
      this.value = $.trim(this.getAttribute("data-value") || "");
    } else {
      this.setAttribute("data-value", "");
    }
  });

  $("button[data-like]").on("click", function (e) {
    e.preventDefault();

    var $button = $(this);

    var upd_button = function ($button, likes) {
      const base = $button.hasClass("btn-post2") ? "btn-post2" : "btn-post";

      if (!$button.hasClass(base + "--active")) {
        $button.addClass(base + "--active");
      }

      $button
        .find("span." + base + "__text")
        .text(likes)
        .show();
    };

    $.ajax({
      type: "PUT",
      url: $(this).data("like"),
      contentType: "application/json",
      // data: JSON.stringify(data), // access in body
    })
      .done(function (data) {
        upd_button($button, data["likes"]);

        if ($button.parents("aside.main__bottom--recent").length) {
          return;
        }

        if ($(".main-content__info-btns").length) {
          upd_button($(".main-content__info-btns").find("button[data-like]"), data["likes"]);
        }

        if ($(".main-content__info--bottom").length) {
          upd_button($(".main-content__info--bottom").find("button[data-like]"), data["likes"]);
        }

        if ($(".main-content__info--top").length) {
          upd_button($(".main-content__info--top").find("button[data-like]"), data["likes"]);
        }
      })
      .fail(function () {
        alert("Произошла ошибка! Попробуйте повторить позже");
      });
  });

  $("#blog_comment").find("[type=submit]").prop("disabled", false);

  $("#blog_comment").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this),
      formData = $form.serializeArray(),
      $button = $form.find("[type=submit]");

    $button.prop("disabled", true);
    $button.find("span.btn__text").hide();
    $button.find("span.btn__load-wrap").show();

    formData.push({ name: "url", value: window.location.href });

    $.post(
      $form.prop("action"),
      formData,
      function () {
        const active = "active",
          $alert = $form.find(".alert--fixed");

        $alert.addClass(active);

        setTimeout(function () {
          $alert.removeClass(active);
        }, 3000);

        $form[0].reset();
      },
      "json"
    )
      .fail(function ($xhr) {
        alert("Сервер временно недоступен. Попробуйте повторить позже");
      })
      .always(function () {
        $button.find("span.btn__load-wrap").hide();
        $button.find("span.btn__text").show();
        $button.prop("disabled", false);
      });

    return false;
  });

  $("button[data-comments]").on("click", function () {
    $("html, body").animate(
      {
        scrollTop: $("#blog_comments").offset().top - 84,
      },
      500
    );
  });

  /* Comments open big text */
  checkCommentsHeight();

  function checkCommentsHeight() {
    document.querySelectorAll(".card-comment__text").forEach((block) => {
      function clickHandler() {
        this.closest(".card-comment").querySelector(".card-comment__text").classList.add("_open");
        this.setAttribute("disabled", "");
      }

      const moreBtn = block.closest(".card-comment").querySelector(".card-comment__more");

      if (block.scrollHeight <= block.offsetHeight) {
        moreBtn.setAttribute("disabled", "");
        moreBtn.removeEventListener("click", clickHandler);
      } else {
        moreBtn.removeAttribute("disabled");
        moreBtn.addEventListener("click", clickHandler);
      }
    });
  }

  /* set textarea larger */
  document.querySelectorAll("textarea").forEach((e) => {
    e.addEventListener("focus", setTextareaLarger);
  });

  function setTextareaLarger(e) {
    const textarea = e.target.closest("textarea");

    if (textarea === null) return false;

    if (textarea.getAttribute("rows") === "1") {
      textarea.setAttribute("rows", "4");
    }

    textarea.removeEventListener("focus", setTextareaLarger);
  }

  /* Highlighting the active section on scroll */
  class ChangeNavCurrentLink {
    constructor(config) {
      this.pagePartSelector = config.pagePartSelector;
      this.targetSelectors = config.targetSelectors;
      this.navBlockSelector = config.navBlockSelector;
      this.navBlockContainerSelector = config.navBlockContainerSelector;
      this.navBlockScrollXSelector = config.navBlockScrollXSelector;
      this.navItemSelector = config.navItemSelector;
      this.activeClass = config.activeClass;
      this.offsetBlocksSelectors = config.offsetBlocksSelectors;

      this.throttle = typeof config.throttle === "function" ? config.throttle : (fn) => fn;

      this.section = document.querySelector(this.pagePartSelector);
      this.nav = document.querySelector(this.navBlockSelector);
      if (!this.section || !this.nav) return;

      this.titles = Array.from(this.section.querySelectorAll(this.targetSelectors));
      this.navItems = Array.from(this.nav.querySelectorAll(this.navItemSelector));
      if (!this.navItems.length) return;

      this.navLinks = this.navItems.map((li) => li.querySelector("a"));

      this.idToNavLink = new Map();
      this.navLinks.forEach((link) => {
        if (link && link.hash) {
          this.idToNavLink.set(link.hash.slice(1), link);
        }
      });

      this.titles = this.titles.filter((el) => this.idToNavLink.has(el.id));

      this.indent = this.calculateTopOffset(this.offsetBlocksSelectors);

      window.addEventListener("resize", () => {
        this.indent = this.calculateTopOffset(this.offsetBlocksSelectors);
        this.changeCurrentLink();
      });

      this.changeCurrentLink();
      window.addEventListener(
        "scroll",
        this.throttle(() => this.changeCurrentLink(), 100)
      );

      this.timeout = null;
    }

    changeCurrentLink() {
      this.resetNav();

      const sectionBottom = this.section.getBoundingClientRect().bottom;
      const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

      for (let i = 0; i < this.titles.length; i++) {
        const el = this.titles[i];
        const elId = el.id;
        const navLink = this.idToNavLink.get(elId);
        if (!navLink) continue;

        const elPosition = this.getScrollPositionForElement(el);
        const nextElPosition = this.getScrollPositionForElement(this.titles[i + 1] || null);

        if (currentScroll >= elPosition && currentScroll < nextElPosition) {
          navLink.classList.add(this.activeClass);
          this.scrollNav(navLink);
          break;
        }

        if (i === this.titles.length - 1 && currentScroll >= elPosition && sectionBottom > this.indent) {
          navLink.classList.add(this.activeClass);
          this.scrollNav(navLink);
          break;
        }
      }
    }

    getScrollPositionForElement(el) {
      if (!el) return Infinity;
      return el.offsetTop - this.marginTop(el) - this.indent;
    }

    marginTop(el) {
      return parseFloat(getComputedStyle(el).marginTop) || 0;
    }

    resetNav() {
      this.navLinks.forEach((link) => link.classList.remove(this.activeClass));
    }

    calculateTopOffset(offsetSelectors) {
      if (!Array.isArray(offsetSelectors) || offsetSelectors.length === 0) return 0;
      return offsetSelectors.reduce((sum, selector) => {
        const el = document.querySelector(selector);
        return el ? sum + el.offsetHeight : sum;
      }, 0);
    }

    scrollNav(el) {
      clearTimeout(this.timeout);

      const container =
        this.navBlockContainerSelector && this.navBlockContainerSelector.trim() !== ""
          ? this.navItems[0].closest(this.navBlockContainerSelector)
          : null;
      const list =
        this.navBlockScrollXSelector && this.navBlockScrollXSelector.trim() !== ""
          ? this.navItems[0].closest(this.navBlockScrollXSelector)
          : null;

      if (!container || !list || !el) return;

      const listScrollLeft = list.scrollLeft;

      const itemRect = el.getBoundingClientRect();
      const listRect = list.getBoundingClientRect();

      const itemOffsetLeft = itemRect.left + window.pageXOffset;
      const listOffsetLeft = listRect.left + window.pageXOffset;
      const space = container.getBoundingClientRect().left;

      const scrollLeftUpd = listScrollLeft + (itemOffsetLeft - listOffsetLeft) - space;

      this.timeout = setTimeout(() => {
        this.smoothScrollTo(list, scrollLeftUpd, 300);
      }, 300);
    }

    smoothScrollTo(element, target, duration) {
      const start = element.scrollLeft;
      const change = target - start;
      const startTime = performance.now();

      const easeInOutQuad = (t) => (t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t);

      const animateScroll = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        element.scrollLeft = start + change * easeInOutQuad(progress);
        if (progress < 1) {
          requestAnimationFrame(animateScroll);
        }
      };

      requestAnimationFrame(animateScroll);
    }
  }

  new ChangeNavCurrentLink({
    pagePartSelector: ".main-content2:not(.main-content2_post) .main-content2__content-part",
    targetSelectors: "h2[id], h3[id], h4[id], h5[id], h6[id]",
    navBlockSelector: ".blog-nav",
    navBlockContainerSelector: ".blog-nav",
    navBlockScrollXSelector: ".blog-nav__list",
    navItemSelector: "li",
    activeClass: "current",
    offsetBlocksSelectors: [".page__header"],
    throttle: throttle,
  });

  new ChangeNavCurrentLink({
    pagePartSelector: ".main-content2_post .content-block",
    targetSelectors: "h2, h3",
    navBlockSelector: ".blog-nav",
    navBlockContainerSelector: ".blog-nav",
    navBlockScrollXSelector: ".blog-nav__list",
    navItemSelector: "li",
    activeClass: "current",
    offsetBlocksSelectors: [".page__header"],
    throttle: throttle,
  });

  (function checkBlogNavPlacement() {
    const mainEl = document.querySelector(".main-content2:not(.main-content2_post) .main-content2__main-container");
    if (!mainEl) return;
    const column = mainEl.querySelector(".main-content2__column");
    const elementParent = mainEl.querySelector(".main-content2__column-sticky");
    const fixedElem = elementParent?.querySelector(".blog-nav");

    if (!column || !fixedElem || !elementParent) return;

    function checkAndChange() {
      if (WINDOW_WIDTH_INNER >= DESKTOP_WIDTH_MD && !elementParent.contains(fixedElem)) elementParent.append(fixedElem);
      else if (WINDOW_WIDTH_INNER < DESKTOP_WIDTH_MD && column.nextElementSibling !== fixedElem) column.after(fixedElem);
    }

    checkAndChange();
    window.addEventListener("resize", throttle(checkAndChange, 500));
  })();

  if ($("div.blog-nav").length) {
    $('<div class="tours-popup__blog-nav blog-nav ">' + $("aside.main__aside  div.blog-nav ").html() + "</div>").appendTo(
      "div.tours-popup__body"
    );

    $("#mobile_page_menu").find("div.aside-list.tours-popup__aside-list").remove();

    $("#mobile_page_menu").find("ul.tours-popup__aside-links.aside-links").remove();
  }

  /* mc-embedded-subscribe-form handlers */
  (function () {
    document.addEventListener("click", function (e) {
      const target = e.target;

      const closestHeader = target.closest(".form-select__header");

      if (!closestHeader) return false;

      const mailingTypeElement = closestHeader.querySelector("#mailing_type");

      if (!mailingTypeElement) return false;

      const cityBlock = mailingTypeElement.form.querySelector("#cities_block"),
        otherCity = mailingTypeElement.form.querySelector("#city-field"),
        privat = "Частное лицо";

      const intervalID = setInterval(() => {
        const isActive = mailingTypeElement.classList.contains("active");

        if (!isActive) {
          const newVal = mailingTypeElement.value;
          clearInterval(intervalID);
          if (newVal === privat) {
            cityBlock.style.display = "none";

            otherCity.style.display = "none";

            document.getElementById("mailing_city").removeAttribute("required");

            document.getElementById("more_merge_3").removeAttribute("required");
          } else {
            cityBlock.style.display = "block";

            const mcity = document.getElementById("mailing_city");

            mcity.setAttribute("required", "required");

            if (!mcity.dataset.value.length) {
              otherCity.style.display = "block";

              document.getElementById("more_merge_3").setAttribute("required", "required");
            }
          }
        }
      }, 500);
    });

    document.addEventListener("click", function (e) {
      const target = e.target;

      const closestHeader = target.closest(".form-select__header");

      if (!closestHeader) return false;

      const mailingCityElement = closestHeader.querySelector("#mailing_city");

      if (!mailingCityElement) return false;

      const otherCity = mailingCityElement.form.querySelector("#city-field"),
        query = "Другой";

      const intervalID = setInterval(() => {
        const isActive = mailingCityElement.classList.contains("active");

        if (!isActive) {
          const newVal = mailingCityElement.value;
          clearInterval(intervalID);

          if (newVal !== query) {
            otherCity.style.display = "none";

            document.getElementById("more_merge_3").removeAttribute("required");
          } else {
            otherCity.style.display = "block";

            document.getElementById("more_merge_3").setAttribute("required", "required");
          }
        }
      }, 500);
    });
  })();

  /* blog-list--index - убрать лишние моды у card-blog--wide на мобильных */
  changeBlogListIndexFirstCardMods();

  function changeBlogListIndexFirstCardMods() {
    const list = document.querySelector(".blog-list--index");

    if (list === null) return false;

    const card = list.querySelector(".card-blog"),
      btns = card.querySelectorAll(".btn-post"),
      mod = "card-blog--wide",
      btnMod = "btn-post--light";

    f();
    window.addEventListener("resize", throttle(f, 500));

    function f() {
      if (WINDOW_WIDTH_INNER < TABLET_WIDTH) {
        card.classList.remove(mod);
        btns.forEach((btn) => {
          btn.classList.remove(btnMod);
        });
      } else {
        card.classList.add(mod);
        btns.forEach((btn) => {
          btn.classList.add(btnMod);
        });
      }
    }
  }

  /* Changing font-size hero item title if its text is overflow */
  changeHeroTitleFontSize();
  window.addEventListener("resize", throttle(changeHeroTitleFontSize, 500));
  function changeHeroTitleFontSize() {
    const blocks = document.querySelectorAll(".hero__title h3");
    if (!blocks.length) return false;

    blocks.forEach((block) => {
      block.style.removeProperty("font-size");
      if (block.style.length === 0) block.removeAttribute("style");

      // Получаем стили блока, учитываем padding, margin по модулю, и немного с запасом (0.95)
      const blockStyles = getComputedStyle(block),
        paddingLeft = parseFloat(blockStyles.paddingLeft),
        paddingRight = parseFloat(blockStyles.paddingRight),
        marginLeft = Math.abs(parseFloat(blockStyles.marginLeft)),
        marginRight = Math.abs(parseFloat(blockStyles.marginRight)),
        blockWidth = (block.clientWidth - paddingLeft - paddingRight - marginLeft - marginRight) * 0.95,
        text = block.textContent.trim();

      // Создаем временный элемент для измерения ширины текста в одну строку
      const tempSpan = document.createElement("span");
      tempSpan.style.position = "absolute";
      tempSpan.style.whiteSpace = "nowrap";
      tempSpan.style.visibility = "hidden";
      tempSpan.style.font = blockStyles.font;
      tempSpan.textContent = text;
      document.body.appendChild(tempSpan);

      let textWidth = tempSpan.getBoundingClientRect().width;

      if (textWidth > blockWidth) {
        // Начальный размер шрифта в vw
        let fontSizeVw = (parseFloat(blockStyles.fontSize) / document.documentElement.clientWidth) * 100;

        // Уменьшаем размер шрифта до тех пор, пока текст не поместится в блок
        while (textWidth > blockWidth && fontSizeVw > 0) {
          fontSizeVw--;
          tempSpan.style.fontSize = `${fontSizeVw}vw`;
          textWidth = tempSpan.getBoundingClientRect().width;
        }

        // Устанавливаем новый размер шрифта блоку
        block.style.fontSize = `${Math.floor(fontSizeVw)}vw`;
      }

      document.body.removeChild(tempSpan);
    });
  }

  $(".js-press-event-form").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this),
      formData = $form.serializeArray(),
      $button = $form.find("[type=submit]");

    $button.prop("disabled", true);
    $button.find("span.btn__text").hide();
    $button.find("span.btn__load-wrap").show();

    formData.push({ name: "url", value: window.location.href });

    $.post(
      $form.attr("data-action"),
      formData,
      function () {
        const active = "active",
          $alert = $form.find(".alert--success");

        $alert.addClass(active);

        setTimeout(function () {
          $alert.removeClass(active);
        }, 3000);

        $form[0].reset();
      },
      "json"
    )
      .fail(function ($xhr) {
        const active = "active",
          $alert = $form.find(".alert--error");

        $alert.addClass(active);

        setTimeout(function () {
          $alert.removeClass(active);
        }, 3000);
      })
      .always(function () {
        $button.find("span.btn__load-wrap").hide();
        $button.find("span.btn__text").show();
        $button.prop("disabled", false);
      });

    return false;
  });

  /*  */
  const asideHandler = (() => {
    const asideToggle = document.querySelector(".aside-toggle");
    const main = document.querySelector(".page__main.main");
    const mainAside = document.querySelector(".main__aside");
    if (!asideToggle || !main || !mainAside) return;

    const mainClosedClass = "main--aside-close";
    const listItemClass = "aside-list__item";
    const innerListClass = "aside-list__hide-box";
    const linkWithInnerListClass = "aside-list__link--menu";
    const asideList = mainAside.querySelector(".aside-list");

    const closeAsideListInnerLists = () => {
      asideList?.querySelectorAll(`.${innerListClass}`).forEach((list) => {
        list.classList.remove("active");
        list.style.height = "0";
        list.closest(`.${listItemClass}`).querySelector(`.${linkWithInnerListClass}`).classList.remove("active");
      });
    };

    const closeAside = () => {
      main.classList.add(mainClosedClass);
      closeAsideListInnerLists();

      let expires = new Date();
      expires.setTime(expires.getTime() + 365 * 24 * 60 * 60 * 1000);
      document.cookie = "aside_menu_closed=1; expires=" + expires.toUTCString() + "; path=/";
    };

    const openAside = () => {
      main.classList.remove(mainClosedClass);

      let expires = new Date();
      expires.setTime(expires.getTime());
      document.cookie = "aside_menu_closed=; expires=" + expires.toUTCString() + "; path=/";
    };

    const openAsideListInnerList = (e) => {
      const link = e.target.closest(`.${linkWithInnerListClass}`);
      if (!link) return;
      openAside();
    };

    const toggleAside = () => {
      main.classList.contains(mainClosedClass) ? openAside() : closeAside();
    };

    asideToggle.addEventListener("click", toggleAside);
    asideList?.addEventListener("click", openAsideListInnerList);
  })();

  /*  */
  class customAttrTitle {
    /**
     * @param {string} selector
     * @param {object} [options]
     * @param {string|null} [options.contentSelector=null]
     * @param {() => boolean} [options.checkFn=null]
     * @param {string|object} [options.tooltipClass='tooltip'] - строка или объект с классами по позициям
     * @param {string} [options.position='top']
     */
    constructor(selector, options = {}) {
      this.selector = selector;
      this.tooltips = document.querySelectorAll(selector);
      this.transformFn = options.transformFn || false;
      this.checkFn = options.checkFn || null;
      this.contentSelector = options.contentSelector || null;
      this.defaultPosition = options.position || "top";

      if (typeof options.tooltipClass === "object" && options.tooltipClass !== null) {
        this.tooltipClasses = {
          top: options.tooltipClass.top || "tooltip",
          right: options.tooltipClass.right || "tooltip",
        };
      } else {
        const className = options.tooltipClass || "tooltip";
        this.tooltipClasses = { top: className, right: className };
      }

      this.init();
    }

    init() {
      this.tooltips.forEach((tooltip) => {
        let timeoutId;

        const clear = () => clearTimeout(timeoutId);

        const removeTooltip = (tooltipText, tooltip, handlers) => {
          if (tooltipText.parentNode) tooltipText.parentNode.removeChild(tooltipText);
          tooltip.removeEventListener("mouseleave", handlers.mouseLeaveHandler);
          tooltip.removeEventListener("click", handlers.clickHandler);
        };

        const getTooltipContent = () => {
          if (tooltip.hasAttribute("title")) {
            const titleValue = tooltip.getAttribute("title").trim();
            tooltip.setAttribute("data-title", titleValue);
            tooltip.removeAttribute("title");
            return tooltip.getAttribute("data-title").trim();
          } else if (tooltip.hasAttribute("data-title")) {
            return tooltip.getAttribute("data-title").trim();
          } else if (this.contentSelector) {
            const child = tooltip.querySelector(this.contentSelector);
            if (child) return child.textContent.trim();
          } else return tooltip.textContent.trim();
        };

        const mouseenterEvent = () => {
          if (this.checkFn && this.checkFn(tooltip) === false) return;
          if (this.transformFn) this.transformFn(tooltip);

          timeoutId = setTimeout(() => {
            const title = getTooltipContent();
            if (!title) return;

            const tooltipText = document.createElement("div");
            const position = tooltip.dataset.tooltipPosition || this.defaultPosition;

            tooltipText.className = this.tooltipClasses[position] || "tooltip";
            tooltipText.textContent = title;
            document.body.appendChild(tooltipText);

            const rect = tooltip.getBoundingClientRect();

            if (position === "right") {
              const centerY = rect.top + rect.height / 2;
              const computedStyle = getComputedStyle(tooltip, "::before");
              const pseudoElementWidth = parseFloat(computedStyle.getPropertyValue("width")) || 0;
              const leftPosition = rect.left + pseudoElementWidth + window.scrollX + 7;

              tooltipText.style.left = `${leftPosition}px`;
              tooltipText.style.maxWidth = `calc(90vw - ${leftPosition}px)`;
              tooltipText.style.top = `${centerY + window.scrollY}px`;
              tooltipText.style.transform = "";
            } else if (position === "top") {
              const centerX = rect.left + rect.width / 2;
              const tooltipHeight = tooltipText.offsetHeight;
              const topPosition = rect.top + window.scrollY - tooltipHeight - 8;

              tooltipText.style.top = `${topPosition}px`;
              tooltipText.style.left = `${centerX + window.scrollX}px`;
              tooltipText.style.transform = "translateX(-50%)";
              tooltipText.style.maxWidth = "90vw";
            }

            tooltipText.style.opacity = "1";

            const mouseLeaveHandler = () => removeTooltip(tooltipText, tooltip, handlers);
            const clickHandler = () => removeTooltip(tooltipText, tooltip, handlers);

            const handlers = { mouseLeaveHandler, clickHandler };

            tooltip.addEventListener("mouseleave", mouseLeaveHandler);
            tooltip.addEventListener("click", clickHandler);
          }, 300);
        };

        tooltip.addEventListener("mouseenter", mouseenterEvent);
        tooltip.addEventListener("mouseleave", clear);
        tooltip.addEventListener("click", clear);
      });
    }
  }

  new customAttrTitle(".main__aside .aside-list__link", {
    checkFn: () => document.querySelector(".main")?.classList.contains("main--aside-close"),
    tooltipClass: {
      right: "tooltip tooltip_right",
    },
    position: "right",
  });

  new customAttrTitle(".card-tour2__includes-item");
  new customAttrTitle(".best-deal__includes-item", {
    checkFn: () => WINDOW_WIDTH_INNER < DESKTOP_WIDTH_MD,
  });
  new customAttrTitle(".card-hotel3__includes-item");
  new customAttrTitle(".hotel-icon2");
  new customAttrTitle(".data-title");
  new customAttrTitle(".tour-calendar__month .tour-calendar__day", {
    checkFn: (elem) => {
      const forbiddenClasses = ["tour-calendar__day_off", "tour-calendar__day_disabled"];

      if (forbiddenClasses.some((cls) => elem.classList.contains(cls))) return false;

      return true;
    },
    transformFn: (elem) => {
      if (elem.hasAttribute("data-title")) return false;
      const isToday = elem.classList.contains("tour-calendar__day_today");

      if (elem.hasAttribute("href")) {
        const href = elem.getAttribute("href").trim();
        const isValidHref = href !== "" && !href.startsWith("#") && href !== "/";

        if (isValidHref && isToday) {
          elem.setAttribute("data-title", "Сегодня. Забронировать");
        } else if (isValidHref) {
          elem.setAttribute("data-title", "Забронировать");
        } else {
          elem.setAttribute("data-title", "?");
        }
      } else if (isToday) {
        elem.setAttribute("data-title", "Сегодня");
      } else {
        elem.setAttribute("data-title", "Недоступно");
      }
    },
  });

  /* drag the scrollable element horizontally with the mouse */
  const draggableScrollHorizontal = (selector) => {
    const elements = document.querySelectorAll(selector);
    if (!elements.length) return false;

    elements.forEach((element) => {
      let isDragging = false,
        wasDragging = false,
        startX,
        scrollLeft,
        targetDown,
        targetUp;

      element.dataset.isReadyToGrab = "";

      element.addEventListener("mouseenter", (e) => {
        element.style.cursor = isOverflow() ? "grab" : "";
      });

      element.addEventListener("mousedown", (e) => {
        if (!isOverflow()) return;
        isDragging = true;
        targetDown = e.target;
        startX = e.pageX - element.offsetLeft;
        scrollLeft = element.scrollLeft;
        element.style.cursor = "grabbing";
      });

      document.addEventListener("mouseleave", () => {
        if (!isOverflow()) return;
        isDragging = false;
        element.style.cursor = "grab";
      });

      document.addEventListener("mouseup", (e) => {
        if (!isOverflow()) return;
        isDragging = false;
        targetUp = e.target;
        element.style.cursor = "grab";
      });

      document.addEventListener(
        "click",
        (e) => {
          let a = targetDown === targetUp;
          let b = targetUp?.closest("[href]");
          let c = targetDown?.closest("[href]");
          if ((a || b || c) && wasDragging) {
            e.preventDefault();
            e.stopPropagation();
          }
          wasDragging = false;
        },
        true
      );

      document.addEventListener("mousemove", (e) => {
        if (!isDragging || !isOverflow()) return;
        e.preventDefault();
        const x = e.pageX - element.offsetLeft;
        const walk = x - startX;
        // const walk = (x - startX) * 2; - to speed up
        if (Math.abs(walk) < 3) return;
        element.scrollLeft = scrollLeft - walk;
        wasDragging = true;
      });

      // this is nessesery
      element.addEventListener("mousedown", (e) => {
        if (isDragging) e.preventDefault();
      });

      function isOverflow() {
        return element.scrollWidth > element.clientWidth;
      }
    });
  };

  draggableScrollHorizontal(".basement-news__list");
  draggableScrollHorizontal(".header-form__page-btns");
  draggableScrollHorizontal("table");
  draggableScrollHorizontal("[data-is-ready-to-grab]");

  (function () {
    var card = document.querySelector(".main-content2__column-card");
    var cardFix = document.querySelector(".main-content2 .card-tourfix");

    if (!card || !cardFix) return;

    var content = document.querySelector(".main-content2");

    watchVisibilityAboveTop(card, cardFix, "_hidden");

    function watchVisibilityAboveTop(target, control, classToRemove) {
      const targetBlock = target;
      const controlBlock = control;

      function checkVisibility() {
        const rect = targetBlock.getBoundingClientRect();
        const contentRect = content.getBoundingClientRect();
        const contentIsMin = contentRect.bottom - rect.bottom < windowHeight;

        if (contentIsMin) {
          controlBlock.classList.add("_static");
          window.removeEventListener("scroll", checkVisibility);
          return;
        }

        if (rect.bottom <= 0) {
          controlBlock.classList.remove(classToRemove);
        } else {
          controlBlock.classList.add(classToRemove);
        }
      }

      window.addEventListener("scroll", checkVisibility);
      checkVisibility();
    }
  })();

  /* Layers Position */
  (function () {
    const p = [
      {
        block: ".filter-bar3__dropdown",
        list: ".filter-bar3__menu",
        layerMod: "form-drop-list3_wrap",
      },
      {
        block: ".search-input2",
        list: ".form-drop-list3",
        layerMod: "form-drop-list3_wrap",
      },
    ];

    function handleCheckLayers() {
      p.forEach(({ block, list, layerMod }) => {
        checkLayersPosition(block, list, layerMod);
      });
    }

    function handleCheckTarget(e) {
      p.forEach(({ block, list, layerMod }) => {
        checkTarget(e, block, list, layerMod);
      });
    }

    window.addEventListener("load", handleCheckLayers);
    window.addEventListener("resize", throttle(handleCheckLayers, 500));
    document.addEventListener("click", (e) => handleCheckTarget(e));
    document.addEventListener("input", (e) => handleCheckTarget(e));

    function checkTarget(e, selector, list, selectorMod) {
      const target = e.target.closest(selector);
      if (!target) return;
      checkLayerPosition(target, list, selectorMod);
    }

    function checkLayerPosition(block, layersSelector, layerMod = "", bW, pddng) {
      const layer = block?.querySelector(layersSelector);
      if (!layer) return;
      const bodyW = bW || WINDOW_WIDTH;
      const padding = pddng || 20;
      layer.classList.remove(layerMod);
      layer.style.right = "";
      layer.style.left = "";
      layer.style.maxWidth = "";
      layer.style.width = "";

      const { right, left, width } = layer.getBoundingClientRect();
      const { right: blockRight } = block.getBoundingClientRect();
      let maxLayerW = bodyW - padding * 2;
      let rightPos = bodyW - padding;

      const peg = layer.closest(".filter-bar3");
      if (peg) {
        const { right: pegRight } = peg.getBoundingClientRect();
        const { paddingLeft, paddingRight } = getComputedStyle(peg);
        maxLayerW = peg.offsetWidth - parseFloat(paddingLeft) - parseFloat(paddingRight);
        rightPos = pegRight - parseFloat(paddingRight);
      }

      const beyondTheRight = right > bodyW - padding;
      const excessWidth = maxLayerW - width;

      if (excessWidth <= 0) {
        layer.style.width = `${maxLayerW}px`;
        layer.classList.add(layerMod);
        layer.style.right = `${blockRight - rightPos}px`;
        layer.style.left = `auto`;
      } else if (beyondTheRight) {
        layer.style.right = "0";
        layer.style.left = `auto`;
        const { left: lleft, width: lwidth } = layer.getBoundingClientRect();
        if (lleft < padding) {
          layer.style.width = `100%`;
        }
      }
    }

    function checkLayersPosition(blockSelector, layersSelector, layerMod) {
      const blocks = document.querySelectorAll(blockSelector);
      if (!blocks.length) return;
      const bodyW = WINDOW_WIDTH;
      const padding = 20;

      blocks.forEach((block) => {
        checkLayerPosition(block, layersSelector, layerMod, bodyW, padding);
      });
    }
  })();

  /* filterBar3ExpandHandler */
  (function filterBar3ExpandHandler() {
    document.querySelectorAll(".filter-bar3__expand").forEach((expand) => {
      const btn = expand;
      const container = btn.closest(".filter-bar3");
      const itemsToHide = container?.querySelectorAll(".filter-bar3__item._mobile-hidden");
      if (!container || itemsToHide.length < 1) {
        btn.remove();
        return;
      }
      btn.addEventListener("click", (e) => {
        const isToShow = btn.classList.contains("_to-show");
        btn.classList.toggle("_to-show", !isToShow);
        container.classList.toggle("_show-mobile-hidden-items", isToShow);
      });
    });
  })();

  /* title-icon */
  (function () {
    const baseTemplate = `
            <span class="title-icon__icon"><svg width="18" height="18"><use xlink:href="/images/sprite-menuicons.svg#{{modifier}}"></use></svg></span>`;

    document.querySelectorAll(".content-block").forEach((contentBlock) => {
      const titleIcons = contentBlock.querySelectorAll(".title-icon.title-icon_bg");

      titleIcons.forEach((icon) => {
        const classes = Array.from(icon.classList);
        const modifierClass = classes.find((cls) => cls.startsWith("___"));
        if (!modifierClass) return;
        const modifier = modifierClass ? modifierClass.split("___")[1] : "";

        const html = baseTemplate.replace("{{modifier}}", modifier);
        icon.insertAdjacentHTML("afterbegin", html);
      });
    });
  })();

  /*  */
  const features = (() => {
    document.querySelectorAll(".features").forEach((block) => {
      const listWrap = block.querySelector(".features__list-wrap");
      const list = block.querySelector(".features__list");
      const toggleBtn = block.querySelector(".open-all2");

      let expanded = false;

      toggleBtn.addEventListener("click", (e) => {
        const href = toggleBtn.getAttribute("href");

        if (toggleBtn.tagName.toUpperCase() == "A" && href && href.length > 1 && href.substr(0, 1) == "#") return;

        e.preventDefault();
        const h = list.offsetHeight;

        expanded = !expanded;
        if (expanded) {
          listWrap.style.height = `${h}px`;
          list.classList.add("_expanded");
          listWrap.style.height = `${list.offsetHeight}px`;
          toggleBtn.classList.add("active");
          setTimeout(() => {
            listWrap.style.height = "";
          }, 300);
        } else {
          listWrap.style.height = `${h}px`;
          list.classList.remove("_expanded");
          listWrap.style.height = `${list.offsetHeight}px`;
          toggleBtn.classList.remove("active");
          setTimeout(() => {
            listWrap.style.height = "";
          }, 300);
        }
      });
    });
  })();

  /* Generated flat navigation */
  var initPageNav = function (cb) {
    const content = document.querySelector(".page--index .main-content2");

    if (content == null) return false;

    const list = document.querySelector("#page-nav"),
      itemTemplate = document.querySelector("#page-nav-item");

    if (list === null || itemTemplate === null) return false;

    const blocks = Array.from(content.querySelectorAll(".main-content2__block_before-index-nav~.main-content2__block[id]")).filter((el) => {
      const attr = el.getAttribute("data-link-text");
      return attr !== null && attr.trim().length > 0;
    });

    const blocksParams = {
      length: blocks.length,
      isEmpty: blocks.length === 0,
      get error() {
        if (this.isEmpty) return "blocks is empty";
      },
    };

    if (blocksParams.isEmpty) return removeContents();

    let i = 0;

    mf(blocks);

    function mf(arr) {
      if (arr.length <= i) {
        if (cb && typeof cb === "function") cb();
        return;
      }

      const el = arr[i],
        item = itemTemplate.content.cloneNode(true),
        a = item.querySelector(".btn"),
        aText = item.querySelector(".btn__text");

      i++;
      a.href = `#${el.id}`;
      aText.textContent = el.getAttribute("data-link-text");
      list.append(item);
      mf(blocks);
    }

    function removeContents() {
      const contents = list.closest(".main-content2__block_index-nav");
      if (contents) contents.remove();
    }
  };
  initPageNav(() => {
    new ChangeNavCurrentLink({
      pagePartSelector: ".page--index .page__main",
      targetSelectors: ".main-content2__block[id]",
      navBlockSelector: ".main-content2__nav-btns",
      navBlockContainerSelector: ".main-content2__nav-btns",
      navBlockScrollXSelector: "[data-is-ready-to-grab]",
      navItemSelector: "li",
      activeClass: "active",
      offsetBlocksSelectors: [".main-content2__nav-btns"],
      throttle: throttle,
    });
  });

  /* classes for Validation and sending form */
  class SafeSvgUseManager {
    constructor(useElem) {
      this.useElem = useElem instanceof Element ? useElem : null;
    }
    get href() {
      return this.useElem ? this.useElem.getAttribute("xlink:href") || "" : "";
    }
    set href(value) {
      if (this.useElem) {
        this.useElem.setAttribute("xlink:href", value);
      }
    }
    get base() {
      return this.href.split("#")[0];
    }
    get symbol() {
      const parts = this.href.split("#");
      return parts.length > 1 ? parts[1] : "";
    }
    setSymbol(name) {
      if (this.useElem) {
        this.href = `${this.base}#${name}`;
      }
    }
    removeSymbol() {
      if (this.useElem) {
        this.href = this.base;
      }
    }
  }

  class AlertManager {
    constructor(alertElem) {
      this.alertCustom = alertElem;
      this.alertCustomText = alertElem?.querySelector(".alert__text");
      const useElem = alertElem?.querySelector("svg use");
      this.svgManager = new SafeSvgUseManager(useElem);

      if (this.alertCustom) {
        this.alertCustom.setAttribute("aria-live", "polite");
      }
    }

    showAlert(mod = "", icon, text) {
      if (this.alertCustom && this.alertCustomText) {
        this.svgManager.setSymbol(icon);
        if (mod) this.alertCustom.classList.add(mod);
        this.alertCustom.classList.add("active");
        this.alertCustomText.textContent = text;
        setTimeout(() => {
          this.alertCustom.classList.remove("active");
          setTimeout(() => {
            if (mod) this.alertCustom.classList.remove(mod);
            this.alertCustomText.textContent = "";
            this.svgManager.removeSymbol();
          }, 300);
        }, 3000);
      } else {
        alert(text);
      }
    }
  }

  class FileInputManager {
    constructor(form, alertManager, allowedExtensions = [], allowedMimeTypes = []) {
      this.form = form;
      this.alertManager = alertManager;

      this.allowedExtensions = allowedExtensions.map((ext) => ext.toLowerCase());
      this.allowedMimeTypes = allowedMimeTypes;

      this.fileItem = form.querySelector(".file__item");
      this.fileInput = form.querySelector(".file__input");
      this.fileItemText = this.fileItem?.querySelector(".file__item-text");
      this.fileItemCloseBtn = this.fileItem?.querySelector(".file__item-closebtn");
      this.dropArea = form.querySelector(".file__label-inner");

      this.currentFileUrl = null;

      this.init();
    }

    init() {
      if (!this.fileItem || !this.fileInput || !this.fileItemText) return;

      this.fileItemCloseBtn?.addEventListener("click", () => this.clearFile());

      this.fileInput.addEventListener("change", () => {
        if (this.fileInput.files.length > 0) {
          this.handleFile(this.fileInput.files[0]);
        } else {
          this.clearFile();
        }
      });

      if (this.dropArea) {
        this.dropArea.addEventListener("dragover", (e) => {
          e.preventDefault();
          this.dropArea.classList.add("file__label-inner_dragover");
        });

        this.dropArea.addEventListener("dragleave", (e) => {
          e.preventDefault();
          this.dropArea.classList.remove("file__label-inner_dragover");
        });

        this.dropArea.addEventListener("drop", (e) => {
          e.preventDefault();
          this.dropArea.classList.remove("file__label-inner_dragover");

          const files = e.dataTransfer.files;
          if (files.length === 0) return;
          if (files.length > 1) {
            this.alertManager.showAlert("alert--error", "check-error", "Пожалуйста, загрузите только один файл.");
            return;
          }

          const file = files[0];
          this.handleFile(file);

          if (typeof DataTransfer !== "undefined") {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            this.fileInput.files = dataTransfer.files;
          }
        });
      }
    }

    clearFile() {
      if (!this.fileItemText) return;
      this.fileItem.style.display = "none";
      this.fileItemText.textContent = "";
      this.fileItemText.removeAttribute("href");
      this.fileItemText.removeAttribute("target");
      if (this.currentFileUrl) {
        URL.revokeObjectURL(this.currentFileUrl);
        this.currentFileUrl = null;
      }
      this.fileInput.value = "";
    }

    handleFile(file) {
      if (file.size > 10 * 1024 * 1024) {
        this.alertManager.showAlert("alert--error", "check-error", "Размер файла не должен превышать 10 МБ.");
        this.clearFile();
        return;
      }

      const fileExt = file.name.split(".").pop().toLowerCase();

      if (!this.allowedExtensions.includes(fileExt)) {
        this.alertManager.showAlert(
          "alert--error",
          "check-error",
          "Недопустимый формат файла. Разрешены: " + this.allowedExtensions.join(", ") + "."
        );
        this.clearFile();
        return;
      }

      if (!this.allowedMimeTypes.includes(file.type)) {
        this.alertManager.showAlert(
          "alert--error",
          "check-error",
          "Недопустимый формат файла. Разрешены: " + this.allowedExtensions.join(", ") + "."
        );
        this.clearFile();
        return;
      }

      if (this.currentFileUrl) {
        URL.revokeObjectURL(this.currentFileUrl);
      }

      this.currentFileUrl = URL.createObjectURL(file);
      this.fileItemText.textContent = file.name;
      this.fileItemText.href = this.currentFileUrl;
      this.fileItemText.target = "_blank";
      this.fileItem.style.display = "";
    }
  }

  /** Инструкция по использованию CustomFormValidator:
   *
   * 1) В HTML форма и контейнер:
   *    <div class="request__container">
   *      <form id="requestForm" name="requestForm">
   *        ...
   *      </form>
   *    </div>
   *
   * 2) Для каждого валидируемого поля укажите:
   *    - data-val="true"
   *    - data-required="Текст ошибки при пустом значении" (если поле обязательно)
   *    - data-pattern="регулярное_выражение" (например, ^\d{10}$)
   *    - data-error="Текст ошибки при несоответствии шаблону"
   *    - data-error-label="id_элемента_ошибки"
   *
   *    Пример:
   *    <input id="email" name="email" type="text"
   *      data-val="true"
   *      data-required="Введите email"
   *      data-pattern="^[\\w.-]+@[\\w.-]+\\.\\w{2,}$"
   *      data-error="Неверный формат email"
   *      data-error-label="emailError"
   *    />
   *
   *    <span id="emailError" class="error-message"></span>
   *
   * 3) В JS инициализируйте валидатор:
   *    const validator = new CustomFormValidator('#requestForm');
   */

  class CustomFormValidator {
    constructor(formSelector, options = {}) {
      this.form = typeof formSelector === "string" ? document.querySelector(formSelector) : formSelector;
      if (!this.form) throw new Error("Форма не найдена");

      this.options = {
        inputErrorClass: "_error",
        errorLabelSelector: (id) => (id ? document.querySelector(`#${id}`) : null),
        formSentClass: "_sent",
        mainSentClass: "_container-sent",
        mainContainerSelector: null,
        ...options,
      };

      this.mainContainer = this.options.mainContainerSelector ? this.form.closest(this.options.mainContainerSelector) : this.form;

      this.reqElements = this.form.querySelectorAll("[data-val='true']");
      this.validators = this._initValidators();
      this._bindEvents();
    }

    _bindEvents() {
      this.reqElements.forEach((el) => {
        el.addEventListener("input", this._onChangeHandler.bind(this));
        el.addEventListener("blur", this._onChangeHandler.bind(this));
      });
    }

    _onChangeHandler(e) {
      const element = e.target;
      const isValid = this.validateElement(element);
      const errorLabel = this.options.errorLabelSelector(element.dataset.errorLabel);

      if (isValid) {
        if (errorLabel) errorLabel.style.display = "none";
        element.classList.remove(this.options.inputErrorClass);
        return;
      }

      if (element.value.trim().length > 0) {
        if (errorLabel) errorLabel.style.display = "none";
        element.classList.remove(this.options.inputErrorClass);
      } else {
        if (errorLabel) errorLabel.style.display = "block";
        element.classList.add(this.options.inputErrorClass);
      }
    }

    validateElement(element) {
      if (!element.dataset.val || element.dataset.val !== "true") return true;

      for (const key in this.validators) {
        if (typeof this.validators[key] === "object" && typeof this.validators[key].isValid === "function") {
          if (element.dataset[key] !== undefined) {
            const validator = this.validators[key];
            if (!validator.isValid(element)) {
              return false;
            }
          }
        }
      }
      return true;
    }

    validateForm() {
      let allValid = true;
      this.reqElements.forEach((el) => {
        if (!this.validateElement(el)) allValid = false;
      });
      return allValid;
    }

    _initValidators() {
      const self = this;
      return {
        validate: function (element, message, predicate) {
          const errorLabel = self.options.errorLabelSelector(element.dataset.errorLabel);
          if (errorLabel) {
            errorLabel.innerHTML = message;
            errorLabel.style.display = "none";
          }
          element.classList.remove(self.options.inputErrorClass);

          if (typeof predicate === "function" && predicate()) {
            return true;
          } else {
            element.classList.add(self.options.inputErrorClass);
            if (errorLabel) errorLabel.style.display = "block";
            return false;
          }
        },

        required: {
          isValid: function (element) {
            const message = element.dataset.required;
            if (!message) return true;
            return self.validators.validate(element, message, () => element.value.trim().length > 0);
          },
        },

        pattern: {
          isValid: function (element) {
            const message = element.dataset.error;
            const pattern = element.dataset.pattern;
            if (!pattern) return true;

            const regex = new RegExp(pattern);
            if (element.value.trim().length === 0 && !element.dataset.required) return true;

            return self.validators.validate(element, message, () => regex.test(element.value));
          },
        },
      };
    }
  }

  /* Validation and sending form */
  (function ValidationAndSendingForm() {
    const form = document.querySelector("#careerForm");
    if (!form) return;
    const validator = new CustomFormValidator("#careerForm", {
      inputErrorClass: "error",
      mainContainerSelector: ".career-form",
      mainSentClass: "career-form_sent",
    });
    const alertCustom = form.querySelector(".alert");
    const alertManager = new AlertManager(alertCustom);
    const allowedExtensions = ["pdf", "doc", "docx", "txt", "odt", "rtf"];
    const allowedMimeTypes = [
      "application/pdf",
      "application/msword",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      "text/plain",
      "application/vnd.oasis.opendocument.text",
      "application/rtf",
    ];
    const fileManager = new FileInputManager(form, alertManager, allowedExtensions, allowedMimeTypes);

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validator.validateForm()) {
        return;
      }

      const formData = new FormData(form);

      formData.append("url", window.location.href);

      formData.append("form_id", "career");

      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
      }

      fetch(form.action || "/", {
        method: form.method || "POST",
        cache: "no-cache",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => {
          if (!response.ok) throw new Error("Ошибка сети");
          return response.json();
        })
        .then((data) => {
          validator.form.classList.add(validator.options.formSentClass);
          if (validator.mainContainer) {
            validator.mainContainer.classList.add(validator.options.mainSentClass);
          }
          form.reset();
          fileManager.clearFile();
          if (submitButton) {
            submitButton.disabled = false;
          }
        })
        .catch((error) => {
          alertManager.showAlert("alert--error", "check-error", "При отправке формы произошла ошибка. Попробуйте ещё раз.");
          if (submitButton) {
            submitButton.disabled = false;
          }
        });
    });
  })();

  /* Mask for input type tel */
  (function inputTelMask() {
    const selector = 'form.career-form__part input[type="tel"]';
    [].forEach.call(document.querySelectorAll(selector), function (input) {
      var keyCode;

      function mask(event) {
        event.keyCode && (keyCode = event.keyCode);
        var pos = this.selectionStart;
        // Запрет вводить 7 или 8 на позицию после "+7 "
        if (event.type === "keydown") {
          if (pos === 3 && (keyCode === 55 || keyCode === 56 || keyCode === 103 || keyCode === 104)) {
            event.preventDefault();
            return;
          }
        }
        if (pos < 3) event.preventDefault();
        var matrix = "+7 ___ ___ - __ - __",
          i = 0,
          def = matrix.replace(/\D/g, ""),
          val = this.value.replace(/\D/g, ""),
          new_value = matrix.replace(/[_\d]/g, function (a) {
            return i < val.length ? val.charAt(i++) || def.charAt(i) : a;
          });
        i = new_value.indexOf("_");
        if (i != -1) {
          i < 3 && (i = 3);
          new_value = new_value.slice(0, i);
        }
        var reg = matrix
          .substr(0, this.value.length)
          .replace(/_+/g, function (a) {
            return "\\d{1," + a.length + "}";
          })
          .replace(/[+ \-]/g, "\\$&");
        reg = new RegExp("^" + reg + "$");
        if (!reg.test(this.value) || this.value.length < 5 || (keyCode > 47 && keyCode < 58)) this.value = new_value;
        if (event.type == "blur" && this.value.length < 5) this.value = "";
      }

      input.addEventListener("input", mask, false);
      input.addEventListener("focus", mask, false);
      input.addEventListener("blur", mask, false);
      input.addEventListener("keydown", mask, false);
    });
  })();

  /* b24FormSecondaryPartHide */
  (function b24FormSecondaryPartHide() {
    function initFormLogic() {
      const formContent = document.querySelector(".b24-form-content");
      if (!formContent) return false;

      const layoutSection = [...formContent.querySelectorAll(".b24-form-field-layout-section")].find((el) =>
        el.textContent.trim().includes("Можете сразу добавить данные для бронирования или прислать их позже")
      );
      if (!layoutSection) return false;

      const checkboxField = formContent.querySelector(".b24-form-field-bool");
      if (!checkboxField) return false;

      const layoutField = layoutSection.closest(".b24-form-field");
      if (!layoutField) return false;

      const allFields = [...formContent.querySelectorAll(".b24-form-field")];
      const startIndex = allFields.indexOf(layoutField);
      const endIndex = allFields.indexOf(checkboxField);
      if (startIndex === -1 || endIndex === -1 || endIndex <= startIndex) return false;

      const fieldsToHide = allFields.slice(startIndex, endIndex);
      const parent = layoutField.parentNode;

      const hiddenContainer = document.createElement("div");
      hiddenContainer.className = "hidden";

      fieldsToHide.forEach((field) => {
        hiddenContainer.appendChild(field);
      });

      parent.insertBefore(hiddenContainer, checkboxField);

      const toggleBtn = document.createElement("button");
      toggleBtn.type = "button";
      toggleBtn.textContent = "Открыть дополнительные поля";
      toggleBtn.className = "booking-toggle-btn2 btn2 btn2_sec";
      hiddenContainer.insertAdjacentElement("afterend", toggleBtn);

      toggleBtn.addEventListener("click", () => {
        hiddenContainer.classList.remove("hidden");
        toggleBtn.classList.add("hidden");
      });

      return true;
    }

    if (!initFormLogic()) {
      const observer = new MutationObserver((mutations, obs) => {
        if (initFormLogic()) {
          obs.disconnect();
          clearTimeout(timeoutId);
        }
      });

      observer.observe(document.body, { childList: true, subtree: true });

      const timeoutId = setTimeout(() => {
        observer.disconnect();
      }, 60000);
    }
  })();
});

function initSwiperSimple() {
  swiperSimple = new Swiper(".slider-simple__inner", {
    wrapperClass: "slider-simple__list",
    slideClass: "slider-simple__item",
    speed: 400,
    spaceBetween: 0,
    loop: true,

    pagination: {
      el: ".slider-pagination",
      type: "bullets",
    },
    navigation: {
      nextEl: ".slider-simple .slider-btn--next",
      prevEl: ".slider-simple .slider-btn--prev",
    },
  });
}

/* filterBar2 */
(function filterBar2() {
  const classes = {
    block: ".filter-bar2",
    btn: ".filter-bar2__btn",
    input: ".filter-bar2__input",
    list: ".filter-bar2__list",
    item: ".filter-bar2__item",
    menu: ".filter-bar2__menu",
    link: ".form-drop-list2__link",
    btnCloseMobile: ".form-drop-list2__mobile-close",
    clear: ".filter-bar2__clear",
    clearAll: ".filter-bar2__clear-all",
    section: ".filter-bar2-filterable-section",
    sectionList: ".filter-bar2-filterable-list",
    sectionMoreBtn: ".filter-bar2-filterable-more-btn",
    testCount: () => {
      const c = Math.round(Math.random() * 10) / 10 > 0.5 ? 1 : 0;
      return c;
    },
  };

  const filterBar2Handler = (event) => {
    const e = event;
    const block = e.target.closest(classes.block);
    if (!block) {
      document
        .querySelector("body")
        .querySelectorAll(classes.btn + ".active")
        .forEach((btn) => {
          btn.classList.remove("active");
          btn.closest(classes.item).querySelector(classes.menu).classList.remove("active");
        });
      return;
    }

    if (!e.target.closest(classes.item)) closeAll(block);

    btnHandler(e);
    clearHandler(e);
    clearAllHandler(e);
    linkHandler(e);
    btnCloseMobileHandler(e);

    function checkAnyChanged() {
      const btn = block.querySelector(classes.clearAll);
      const inputs = block.querySelectorAll(classes.input);

      let isChanged = () => {
        for (const input of inputs) {
          if (input.dataset.default !== input.value) return true;
        }
        return false;
      };

      btn.classList.toggle("active", isChanged());
    }

    function btnCloseMobileHandler(e) {
      const btnCloseMobile = e.target.closest(classes.btnCloseMobile);
      if (!btnCloseMobile) return;

      const menu = btnCloseMobile.closest(classes.menu);
      const btn = menu.closest(classes.item).querySelector(classes.btn);
      closeItem(btn, menu);
    }

    function linkHandler(e) {
      const link = e.target.closest(classes.link);
      if (!link) return;
      e.preventDefault();

      if (link.classList.contains("active") || !link.hasAttribute("href")) return;

      const menu = link.closest(classes.menu);
      const text = link.textContent.trim();
      const item = link.closest(classes.item);
      const input = item.querySelector(classes.input);

      let dataValue = link.dataset.value ?? "";
      let group = link.dataset.group;
      group = group ? group.trim() + " " : "";

      input.value = group + text;
      input.dataset.value = dataValue;
      menu.querySelector(classes.link + ".active").classList.remove("active");
      link.classList.add("active");

      updateMediaList();

      checkItem(item);
      checkAnyChanged();
      closeAll(block);
    }

    function clearHandler(e) {
      const btn = e.target.closest(classes.clear);
      if (!btn) return;

      const item = btn.closest(classes.item);
      setDefault(item);
    }

    function clearAllHandler(e) {
      const btn = e.target.closest(classes.clearAll);
      if (!btn) return;

      block.querySelectorAll(classes.item).forEach((item) => {
        setDefault(item);
      });
    }

    function btnHandler(e) {
      const btn = e.target.closest(classes.btn);
      if (!btn) return;

      const item = btn.closest(classes.item);
      const menu = item.querySelector(classes.menu);

      if (btn.classList.contains("active")) {
        closeItem(btn, menu);
      } else {
        closeAll(block);
        openItem(btn, menu);
        runIfMobile(lockScroll);
      }
    }

    function checkItem(item) {
      loading();
      const input = item.querySelector(classes.input);

      item.classList.toggle("_changed", input.dataset.default !== input.value);

      setTimeout(() => {
        loading(false);
        checkMoreItems();
      }, 1000);
    }

    function closeAll(block) {
      block.querySelectorAll(classes.btn + ".active").forEach((btn) => {
        closeItem(btn, btn.closest(classes.item).querySelector(classes.menu));
      });
    }

    function closeItem(btn, menu) {
      btn.classList.remove("active");
      menu.classList.remove("active");
      unLockScroll();
    }

    function openItem(btn, menu) {
      btn.classList.add("active");
      menu.classList.add("active");
    }

    function setDefault(item) {
      item.querySelector(classes.link + "[data-default]").click();
    }

    function runIfMobile(cb) {
      if (window.innerWidth >= 720) return;
      cb();
    }

    function lockScroll() {
      document.querySelector("body").style.overflow = "hidden";
    }

    function unLockScroll() {
      document.querySelector("body").style.removeProperty("overflow");
    }

    function loading(condition = true) {
      getProcessedEls().forEach((el) => {
        el.classList.toggle("_loading", condition);
      });
    }

    function getProcessedEls() {
      const startEl = block.closest(classes.section) || block;
      const selectors = [classes.list, classes.clearAll, classes.sectionList, classes.sectionMoreBtn];
      const elList = [];

      if (startEl) {
        selectors.forEach((selector) => {
          const els = startEl.querySelectorAll(selector);
          if (els.length > 0) {
            els.forEach((el) => {
              elList.push(el);
            });
          }
        });
      }

      return elList;
    }

    function checkMoreItems() {
      const section = block.closest(classes.section);
      if (!section) return;

      const $btn = $(section).find(classes.sectionMoreBtn);
      let offset = parseInt($btn.data("offset")),
        total = parseInt($btn.data("total"));
      offset = isNaN(offset) ? 0 : offset;
      total = isNaN(total) ? 0 : total;

      if (offset >= total) {
        $btn.addClass("btn-row--hide _loading").closest(".js-btn-hidebox").addClass("hidden");
      } else {
        $btn.removeClass("btn-row--hide _loading").closest(".js-btn-hidebox").removeClass("hidden");
      }
    }
  };

  document.querySelector("body").addEventListener("click", (e) => {
    filterBar2Handler(e);
  });
})();

function getMediaListQuery() {
  let queryParams = {};

  $("ul.filter-bar2__list input.filter-bar2__input").each(function (idx) {
    queryParams[this.name] = this.dataset.value;
  });

  return queryParams;
}

function updateMediaList() {
  let $btn = $(".main-content--press .filter-bar2-filterable-more-btn");

  const loadingBar = document.querySelector(".loading-bar");

  $btn.prop("disabled", true);

  $.ajax({
    url: $("ul.filter-bar2__list").data("url"),
    data: getMediaListQuery(),
    cache: false,
    dataType: "json",
    type: "get",
    async: true,
    xhr: function () {
      var xhr = $.ajaxSettings.xhr();

      xhr.upload.onprogress = function (event) {
        initLoadingBar(loadingBar, (100 * event.loaded) / event.total);
      };

      return xhr;
    },
    error: function (xhr, status, err) {
      alert("Ошибка получения данных! Попробуйте перезагрузить страницу");

      $btn.prop("disabled", false);

      hideLoadingBar(loadingBar);
    },
    success: function (data) {
      let offset = parseInt(data["offset"]);

      $(".news2__list.filter-bar2-filterable-list").html(data["content"]);

      $btn.data("offset", offset);

      $btn.data("total", parseInt(data["total"]));

      if (offset >= parseInt($btn.data("total"))) {
        $btn.hide();
      } else {
        if ($btn.is(":hidden")) {
          $btn.show();
        }
      }

      $("div.filter-bar2 .filter-bar2__item").each(function (idx) {
        const f_key = $(this).find(".filter-bar2__input").attr("name");

        $(this)
          .find(".form-drop-list2__list li > a")
          .each(function () {
            if (!$(this).data("value")) {
              return;
            }

            $(this).removeAttr("href");
            if ($.inArray($(this).data("value").toString(), data["active"][f_key]) > -1) {
              $(this).attr("href", "#");
            }

            //console.log($(this).text() + ': ' + $(this).data('value'));
          });
      });

      //data.

      $btn.prop("disabled", false);

      hideLoadingBar(loadingBar);
    },
  });
}

function initLoadingBar(loadingBar, percentComplete) {
  loadingBar.classList.remove("hidden");
  loadingBar.style.width = percentComplete + "%";
}

function hideLoadingBar(loadingBar) {
  loadingBar.removeAttribute("style");
  loadingBar.classList.add("hidden");
}

function filterVipHotels($list, resort_id, category_id, filter_var) {
  var valid = {};
  $list.each(function () {
    if (filter_var == "resort_id") {
      if (category_id && this.getAttribute("data-category").split(",").indexOf(category_id) < 0) {
        this.classList.add("hidden");
        return;
      }
      valid[this.getAttribute("data-resort")] = 1;
      if (resort_id && this.getAttribute("data-resort") != resort_id) {
        this.classList.add("hidden");
      } else {
        this.classList.remove("hidden");
      }
    } else {
      if (resort_id && this.getAttribute("data-resort") != resort_id) {
        this.classList.add("hidden");
        return;
      }
      if (filter_var == "category_id") {
        this.getAttribute("data-category")
          .split(",")
          .forEach((id) => {
            valid[id] = 1;
          });
      }
      if (category_id && this.getAttribute("data-category").split(",").indexOf(category_id) < 0) {
        this.classList.add("hidden");
        return;
      } else {
        this.classList.remove("hidden");
      }
    }
  });

  return valid;
}

window.addEventListener("load", () => {
  $('.js-enable-form [type="submit"]:disabled').prop("disabled", null);

  // закрытие календаря
  const daterangepickerNode = document.querySelectorAll(".daterangepicker");
  if (daterangepickerNode.length > 0) {
    for (const node of daterangepickerNode) {
      let observer = new MutationObserver((mutationRecords) => {
        const observerMutStyle = node.getAttribute("style");
        setTimeout(closeCalendar, 250);

        function closeCalendar() {
          if (observerMutStyle && observerMutStyle.includes("none")) {
            const calendarBody = document.querySelectorAll(".form-item--calendar");
            for (const body of calendarBody) {
              body.classList.remove("form-item--active");
            }
            const headerForms = document.querySelectorAll(".header-form");
            for (const headerForm of headerForms) {
              headerForm.classList.remove("active");
            }

            const header = document.querySelector("header");
            if (header) {
              header.classList.remove("active");
            }

            unLockScroll();
          }
        }
      });

      observer.observe(node, {
        attributes: true,
      });
    }
  }

  function unLockScroll() {
    const body = document.querySelector("body");
    body.removeAttribute("style");
  }

  // контентный аккардион обнуление высоты
  // const accordionHideboxs = document.querySelectorAll('.accordion > li > div');
  // if (accordionHideboxs.length > 0) {
  //     for (const box of accordionHideboxs) {
  //         box.setAttribute('height-content', box.offsetHeight);
  //         box.setAttribute('style', 'height: 0; padding-top: 0; padding-bottom: 0');
  //     }
  // }

  // контентный аккордион без фиксации высоты
  (function accordion() {
    $(".accordion > li > div").hide();

    $(".right-link").each(function () {
      if ($(this).find("a.open-all").length === 0) {
        var openAllHtml =
          '<a class="open-all" href="">' +
          '<span class="open-all__text">Раскрыть всё</span>' +
          '<span class="open-all__icon"></span>' +
          "</a>";
        $(this).append(openAllHtml);
      }
    });

    $(".accordion > li > a").on("click", function (e) {
      e.preventDefault();
      var $thisDiv = $(this).next();
      var $thisLink = $(this);
      var duration = 300;

      if ($thisDiv.is(":visible")) {
        $thisDiv.slideUp(duration);
        $thisLink.removeClass("active");
        $thisDiv.removeClass("active");
      } else {
        $thisDiv.slideDown(duration);
        $thisLink.addClass("active");
        $thisDiv.addClass("active");
      }
    });

    $(document).on("click", ".open-all", function (e) {
      e.preventDefault();
      var $this = $(this);
      var $thisText = $this.find(".open-all__text");
      var isActive = $this.hasClass("active");
      var $allDivs = $this.parents().eq(1).find(".accordion > li > div");
      var duration = 300;

      if (isActive) {
        $allDivs.slideUp(duration);
        $this.parents().eq(1).find(".accordion > li > a").removeClass("active");
        $allDivs.removeClass("active");
        $this.removeClass("active");
        $thisText.text("Раскрыть всё");
      } else {
        $allDivs.slideDown(duration);
        $this.parents().eq(1).find(".accordion > li > a").addClass("active");
        $allDivs.addClass("active");
        $this.addClass("active");
        $thisText.text("Свернуть все");
      }
    });
  })();

  function accardionOn(
    selectorBtn,
    selectorParrent,
    selectorHideBox,
    openAllBtn = "",
    paddingTop = "",
    paddingBottom = "",
    data = false,
    noHideAll = false
  ) {
    const accordionBtns = document.querySelectorAll(selectorBtn);

    if (accordionBtns.length > 0) {
      for (const btn of accordionBtns) {
        btn.addEventListener("click", function (e) {
          e.preventDefault();
          const hideBox = this.closest(selectorParrent).querySelector(selectorHideBox);
          let hideBoxHeight;

          if (data) {
            hideBoxHeight = hideBox.getAttribute("height-content");
          } else if (selectorBtn === ".header-form__formclose-btn") {
            hideBoxHeight = hideBox.querySelector(`${selectorHideBox} > *`).offsetHeight;
          } else {
            hideBoxHeight = hideBox.querySelector(`${selectorHideBox} > *`).offsetHeight;
          }

          this.classList.toggle("active");

          if (this.classList.contains("active")) {
            if (!noHideAll) {
              const hideBoxes = document.querySelectorAll(selectorHideBox);
              for (const i of hideBoxes) {
                i.setAttribute("style", `height: 0; padding-top: 0; padding-bottom: 0`);
                i.classList.remove("active");
              }

              for (const btn of accordionBtns) {
                btn.classList.remove("active");
              }
            }

            hideBox.setAttribute(
              "style",
              `height: ${
                Number(hideBoxHeight) + Number(paddingTop) + Number(paddingBottom)
              }px; padding-bottom: ${paddingBottom}px; padding-top: ${paddingTop}px`
            );
            hideBox.classList.add("active");
            this.classList.add("active");
          } else {
            if (!noHideAll) {
              const hideBoxes = document.querySelectorAll(selectorHideBox);
              for (const i of hideBoxes) {
                i.classList.remove("active");
                if (data) {
                  i.setAttribute("style", `height: 0; padding-top: 0; padding-bottom: 0;`);
                } else {
                  i.setAttribute("style", `height: 0;`);
                }

                for (const btn of accordionBtns) {
                  btn.classList.remove("active");
                }
              }
            }

            if (data) {
              hideBox.setAttribute("style", `height: 0; padding-top: 0; padding-bottom: 0;`);
            } else {
              hideBox.setAttribute("style", `height: 0;`);
            }

            hideBox.classList.remove("active");
            this.classList.remove("active");
          }
        });
      }

      if (openAllBtn !== "") {
        const openBtn = document.querySelector(openAllBtn);
        if (openBtn) {
          openBtn.addEventListener("click", (e) => {
            e.preventDefault();
            openBtn.classList.toggle("active");
            const hideBoxes = document.querySelectorAll(selectorHideBox);

            if (openBtn.classList.contains("active")) {
              openBtn.textContent = "Свернуть все";
              let hideBoxHeight;

              for (const hideBox of hideBoxes) {
                if (data) {
                  hideBoxHeight = hideBox.getAttribute("height-content");
                } else {
                  hideBoxHeight = hideBox.querySelector(`${selectorHideBox} > *`).offsetHeight;
                }
                hideBox.setAttribute(
                  "style",
                  `height: ${
                    Number(hideBoxHeight) + Number(paddingTop) + Number(paddingBottom)
                  }px; padding-bottom: ${paddingTop}px; padding-top: ${paddingBottom}px`
                );
                hideBox.classList.add("active");
              }

              for (const btn of accordionBtns) {
                btn.classList.add("active");
              }
            } else {
              openBtn.textContent = "Раскрыть всё";
              for (const hideBox of hideBoxes) {
                hideBox.setAttribute("style", `height: 0; padding-top: 0; padding-bottom: 0;`);
                hideBox.classList.remove("active");
              }

              const allSelectors = document.querySelectorAll(selectorBtn);
              for (const selector of allSelectors) {
                selector.classList.remove("active");
              }
            }
          });
        }
      }
    }
  }

  function getFinalScrollPosition(scrollTarget) {
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
    const elementPositionInWindow = scrollTarget.getBoundingClientRect().top;
    const targetPosition = elementPositionInWindow + currentScroll;

    const htmlCss = getComputedStyle(document.documentElement);
    const headerHeight = parseFloat(htmlCss.getPropertyValue("--re-page-header-height"));
    const headerFormHeight = parseFloat(htmlCss.getPropertyValue("--re-page-header-form-height"));
    const margin = 16 * 2;
    const navEl = document.querySelector(".main-content2__nav-btns") || document.querySelector(".main-content2__column+.blog-nav");
    const navElHeight = navEl ? navEl.offsetHeight : 0;
    const addedSize = window.innerWidth < 1000 && targetPosition < currentScroll ? headerHeight : 0;
    const finalScrollPosition = elementPositionInWindow - headerFormHeight - addedSize - navElHeight - margin;
    return finalScrollPosition;
  }

  /* Плавный скролл к элементу */
  document.querySelectorAll('a[href^="#"').forEach((link) => {
    if (link.getAttribute("href").length > 1) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        scrollToElement(this.getAttribute("href").substring(1));
      });
    }
  });

  function scrollToElement(href) {
    const scrollTarget = document.getElementById(href);
    if (scrollTarget) {
      const finalScrollPosition = getFinalScrollPosition(scrollTarget);

      window.scrollBy({ top: finalScrollPosition, behavior: "smooth" });

      if (scrollTarget.closest(".tabs-menu") || scrollTarget.closest(".btns_new"))
        setTimeout(() => {
          scrollTarget.click();
        }, 1);
    }
  }

  if (location.hash.length > 1) {
    scrollToElement(location.hash.substring(1));
  }

  if (document.querySelector(".filter-bar3.js-scroll-results") && document.querySelector(".tours-in2__list-header")) {
    scrollToElement("tours-list-header");
  }

  // выпадашка в главной форме добавление детей
  addChilds();

  function addChilds() {
    const inputsChilds = $(".input-number__input--child");

    // $.each(inputsChilds, function (index, inputsChild) {
    const inputsBtns = $(".input-number__btn");

    $.each(inputsBtns, function (index, inputsBtn) {
      $(inputsBtn).click(function () {
        let thisInput = $(this).closest(".input-number").find(".input-number__input--child");
        if (thisInput.length > 0) {
          const childRows = $(this).closest(".members").find(".members__row");
          const childRowsWrapper = $(this).closest(".members").find(".members__add");
          childRowsWrapper.attr("style", `height: ${childRowsWrapper.find(".members__add-inner").height()}px`);

          let countChild = Number($(thisInput).val());

          let count = 0;

          $.each(childRows, function (index, childRow) {
            $(childRow).addClass("d-none");
          });

          while (count < countChild) {
            $(childRows[count]).removeClass("d-none");
            count++;
          }

          childRowsWrapper.attr("style", `overflow: hidden; height: ${childRowsWrapper.find(".members__add-inner").height()}px`);

          setTimeout(() => {
            childRowsWrapper.attr("style", `overflow: unset; height: ${childRowsWrapper.find(".members__add-inner").height()}px`);
          }, 1000);
        }
      });
    });
    // });
  }

  // выпадашка в главной форме количество людей передать значение в текст
  chengeHowManyPeople();

  function chengeHowManyPeople() {
    const inputsBtns = $(".input-number__btn");

    if (inputsBtns.length > 0) {
      function change(btn) {
        const howManyInput = $(btn).closest(".form-item").find(".form-item__input");
        const howManyPeopleInputs = $(btn).closest(".form-item").find(".input-number__input");
        let summ = 0;

        $.each(howManyPeopleInputs, function (index, input) {
          summ += Number($(input).val());
        });

        const lastNumber = summ.toString().slice(-1);
        let word;

        if ((lastNumber == 4 || lastNumber == 3 || lastNumber == 2) && !(summ == 11 || summ == 12 || summ == 13 || summ == 14)) {
          word = "человека";
        } else {
          word = "человек";
        }

        $(howManyInput).val(summ + " " + word);
      }

      $.each(inputsBtns, function (index, btn) {
        change(btn);

        $(btn).click(function () {
          change(this);
        });
      });
    }
  }
  /**
    if (location.href.includes('#')) {
        const urlId = location.href.split('#')[1];
        const idBtn = document.getElementById(urlId);
        if (idBtn) {
            idBtn.click();
            const topOffset = 50;
            const elementPosition = idBtn.getBoundingClientRect().top;
            const offsetPosition = elementPosition - topOffset - 70;

            window.scrollBy({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    }
*/
  setTimeout(function () {
    $("input[data-default]").each(function () {
      if (this.value != this.dataset["default"]) this.value = this.dataset["default"];
    });
  }, 10);

  $(".js-excursions-form-block form").on("submit", function (e) {
    e.preventDefault();

    var $this = $(this),
      params = [],
      val = $this.find('input[name="resort_id"]').attr("data-value");
    if (val) params.push("resort_id=" + val);
    val = $this.find('input[name="type_id"]').attr("data-value");
    if (val) params.push("type_id=" + val);

    location.href = val = $this.find('input[name="country"]').attr("data-url") + (params.length ? "?" + params.join("&") : "");

    return false;
  });

  $(".reviews-list__btn-row").on("click", function () {
    let btn = this,
      hidebox = btn.closest(".js-btn-hidebox");

    $.ajax({
      url: $(btn).data("url"),
      data: {
        page: $(btn).data("page") + 1,
      },
      cache: false,
      dataType: "html",
      type: "post",
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");

        btn.classList.remove("btn--load-loading");
      },
      success: function (data) {
        var page = parseInt($(btn).data("page")) + 1;

        if (page >= parseInt($(btn).data("pages"))) {
          $(btn).addClass("btn-row--hide");
          if (hidebox) $(hidebox).addClass("hidden");

          //$(btn).parent().remove();
        } else {
          $(btn).removeClass("btn-row--hide").data("page", page);
          if (hidebox) $(hidebox).removeClass("hidden");
        }

        $(data).appendTo(".reviews-list__inner");

        btn.classList.remove("btn--load-loading");
      },
    });
  });

  $(".news-list-section__btn").on("click", function () {
    let btn = this,
      hidebox = btn.closest(".js-btn-hidebox");

    $.ajax({
      url: $(btn).data("url"),
      data: {
        page: $(btn).data("page") + 1,
      },
      cache: false,
      dataType: "html",
      type: "get",
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");

        btn.classList.remove("btn--load-loading");
      },
      success: function (data) {
        var page = parseInt($(btn).data("page")) + 1;

        if (page >= parseInt($(btn).data("pages"))) {
          $(btn).addClass("btn-row--hide");
          if (hidebox) $(hidebox).addClass("hidden");

          //$(btn).parent().remove();
        } else {
          $(btn).removeClass("btn-row--hide").data("page", page);
          if (hidebox) $(hidebox).removeClass("hidden");
        }

        $(data).appendTo(".news3 .news3__list");

        btn.classList.remove("btn--load-loading");
      },
    });
  });

  if ($(".page--press").length) {
    $(window).on("popstate", function (e) {
      let $btn = $('.btn--press[href="' + document.location.href + '"]');
      $btn = $btn.length ? $btn : $('.press-header__back[href="' + document.location.href + '"]');
      if (!$btn.length && document.location.href.substr(0, 4) === "http") {
        $btn = $('.btn--press[href="' + document.location.href.substr(document.location.href.indexOf("/", 8)) + '"]');
        $btn = $btn.length
          ? $btn
          : $('.press-header__back[href="' + document.location.href.substr(document.location.href.indexOf("/", 8)) + '"]');
      }
      if ($btn.length) pressLink($btn, true);
    });
  }

  $(".page--press").on("click", ".btn--press, .press-header__back", function (e) {
    e.preventDefault();

    pressLink($(this));

    return false;
  });

  function pressLink($btn, no_history) {
    const loadingBar = document.querySelector(".loading-bar");

    $.ajax({
      url: $btn.prop("href"),
      data: { init_load: true },
      cache: false,
      dataType: "json",
      type: "post",
      async: true,
      xhr: function () {
        var xhr = $.ajaxSettings.xhr();
        // set the onprogress event handler
        xhr.upload.onprogress = function (event) {
          initLoadingBar(loadingBar, (100 * event.loaded) / event.total);
        };
        // set the onload event handler
        // xhr.upload.onload = function(){ console.log('DONE!') } ;

        return xhr;
      },
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");

        hideLoadingBar(loadingBar);
      },
      success: function (data) {
        let $block = $(".main-content--press > .main-content__block"),
          $nav = $(".btns--press-nav").closest(".main-content__block-header");
        if (!$block.length) $block = $(".main-content--press-post > .main-content__block");

        $block.children().not(":first").remove();

        $btn.closest(".btns__list").find(".active").removeClass("active");

        $btn.addClass("active");

        if ($nav.is(":hidden")) {
          $nav.show();
          $(".main-content.main-content--press-post").removeClass("main-content--press-post").addClass("main-content--press");
        }

        $(".press-header__part-back").hide();

        $(data["content"]).appendTo($block);

        if ($block.find(".slider-simple__inner").length) initSwiperSimple();

        $(".press-header__title.h1").text("Пресс-центр");

        $("aside.main__aside").hide();

        $("main").removeClass("main--press-post");

        document.querySelector("title").textContent = data["title"];

        $("html, body").animate(
          {
            scrollTop: $(".press-header").offset().top,
          },
          500
        );

        if (!no_history) window.history.pushState({}, null, $btn.prop("href"));

        hideLoadingBar(loadingBar);
      },
    });
  }

  $(".main-content--press").on("click", ".news-list__link, .card-news3__inner", function (e) {
    return;

    e.preventDefault();

    let $btn = $(this);

    const loadingBar = document.querySelector(".loading-bar");

    $.ajax({
      url: $btn.prop("href"),
      data: { init_load: true },
      cache: false,
      dataType: "json",
      type: "post",
      async: true,
      xhr: function () {
        var xhr = $.ajaxSettings.xhr();

        xhr.upload.onprogress = function (event) {
          initLoadingBar(loadingBar, (100 * event.loaded) / event.total);
        };

        return xhr;
      },
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");

        hideLoadingBar(loadingBar);
      },
      success: function (data) {
        let $block = $(".main-content--press > .main-content__block"),
          $nav = $(".btns--press-nav").closest(".main-content__block-header");

        $block.children().not(":first").remove();

        if ($nav.length) {
          $nav.hide();
          $(".main-content.main-content--press").removeClass("main-content--press").addClass("main-content--press-post");
        }

        $(".press-header__part-back").show().find(".press-header__back").prop("href", data["href_chapter"]);

        $btn.closest(".btns__list").find(".active").removeClass("active");

        $btn.addClass("active");

        $(data["content"]).appendTo($block);

        $(".press-header__title.h1").text(data["h1_parent"]);

        document.querySelector("title").textContent = data["title"];

        if (data["is_aside_form"]) {
          $("main").addClass("main--press-post");
          $("aside.main__aside").show();
        }

        $("html, body").animate(
          {
            scrollTop: $(".press-header").offset().top,
          },
          500
        );

        window.history.pushState({}, null, $btn.prop("href"));

        hideLoadingBar(loadingBar);
      },
    });

    return false;
  });

  $(".main-content--press").on("click", ".press-releases-list-section__btn, .filter-bar2-filterable-more-btn", function (e) {
    e.preventDefault();

    let $btn = $(this);

    const loadingBar = document.querySelector(".loading-bar");

    $btn.addClass("btn-row--load").prop("disabled", true);

    let params = getMediaListQuery();
    params["offset"] = $btn.data("offset");

    if ($btn.hasClass("filter-bar2-filterable-more-btn")) {
      params["is_show_more"] = 1;
    }

    $.ajax({
      url: $btn.data("url"),
      data: params,
      cache: false,
      dataType: "json",
      type: "get",
      async: true,
      xhr: function () {
        var xhr = $.ajaxSettings.xhr();

        xhr.upload.onprogress = function (event) {
          initLoadingBar(loadingBar, (100 * event.loaded) / event.total);
        };

        return xhr;
      },
      error: function (xhr, status, err) {
        alert("Ошибка получения данных! Попробуйте перезагрузить страницу");

        $btn.removeClass("btn-row--load").prop("disabled", false);

        hideLoadingBar(loadingBar);
      },
      success: function (data) {
        var offset = parseInt(data["offset"]);

        $(data["content"]).appendTo($btn.parents(".main-content__block").find("ul.content-ul"));

        $btn.data("offset", offset);

        if (offset >= parseInt($btn.data("total"))) {
          $btn.hide();
        } else {
          $btn.removeClass("btn-row--load").prop("disabled", false);

          if ($btn.is(":hidden")) {
            $btn.show();
          }
        }

        hideLoadingBar(loadingBar);
      },
    });
  });

  $(".currency__choice-link").on("click", function (e) {
    e.preventDefault();

    $(".currency__list .currency__item").removeClass(function (index, className) {
      return (className.match(/(^|\s)currency__item--\S+/g) || []).join(" ");
    });

    let curr = {
      usd: $('.currency__value[data-currency="usd"]'),
      eur: $('.currency__value[data-currency="eur"]'),
      rub: $('.currency__value[data-currency="rub"]'),
    };

    curr["usd"].html($(this).data("usd"));

    curr["usd"].closest("li").addClass($(this).data("usd-class"));

    curr["eur"].html($(this).data("eur"));

    curr["eur"].closest("li").addClass($(this).data("eur-class"));

    curr["rub"].html($(this).data("rub"));

    curr["rub"].closest("li").addClass($(this).data("rub-class"));

    curr["rub"].siblings(".currency__name").html($(this).data("h3"));

    let currency_choice = $(this).text().substr(0, 3),
      expires = new Date();

    expires.setTime(expires.getTime() + 10 * 24 * 60 * 60 * 1000);

    document.cookie = "currency_choice=" + currency_choice + "; expires=" + expires.toUTCString() + "; path=/";

    $(".currency__choice .currency__choice-on").text(currency_choice);

    $(".currency__choice-btn.active").click();
  });

  var $mobile_menu = $("#mobile_page_menu");

  if ($mobile_menu.length) {
    $mobile_menu.find(".tours-popup__title").text($(".main-content h1").text());

    $mobile_menu.find(".tours-popup__aside-list.aside-list--popup").html($(".main__aside .aside-list").html());

    $mobile_menu.find(".tours-popup__aside-links.aside-links").html($(".main__aside .aside-links").html());
  }

  // аккардионы селекты (всплывашки)
  // accardionOn('.accordion > li > *:first-child', '.accordion > li', '.accordion > li > div', '.open-all', 16, 24, true, true);
  accardionOn(".aside-list__link--menu", "li", ".aside-list__hide-box", "", "", "", false, true);
  accardionOn(".currency__choice-btn", ".currency__choice", ".currency__hide-box");
  accardionOn(".header-form__formclose-btn", ".header-form__wrapper", ".header-form__container");

  const scrollToBlock = (e) => {
    const innerBlockPosition = document.querySelector(
      '.drop-country2__group-title[data-worldpart_id="' + e.currentTarget.dataset.worldpart_id + '"]'
    )?.offsetTop;

    if (!innerBlockPosition) return;

    let scrollBlock = e.currentTarget.closest(".header__main");

    if (!scrollBlock) scrollBlock = e.currentTarget.closest(".nav-item__container");

    if (!scrollBlock) return;

    scrollBlock.scrollTo({ top: innerBlockPosition, behavior: "smooth" });
  };

  document.querySelectorAll(".drop-country2 .btn[data-worldpart_id]")?.forEach((btn) => btn.addEventListener("click", scrollToBlock));

  $(".tours-in2 button[data-total]").on("click", function (e) {
    $(".tours-in2__list .tours-in2__item.hidden").slice(0, $(this).data("limit")).removeClass("hidden");

    if (!$(".tours-in2__list .tours-in2__item.hidden").length) $(this).parent().remove();
  });
});

/* Optimizing a Frequently Called Function */
function throttle(func, delay = 1000) {
  if (typeof func != "function") return;
  let canRun = true;
  return function () {
    if (!canRun) return;

    canRun = false;
    setTimeout(() => {
      func();
      canRun = true;
    }, delay);
  };
}

/* fix IOS Img Src Copy */
function fixIosImgSrcCopy(img) {
  function isIOS() {
    return /iP(hone|od|ad)/.test(navigator.platform) || (navigator.userAgent.includes("Mac") && "ontouchend" in document);
  }
}

/* moving elements */
function moveElement(element, targetContainer, position = "append") {
  if (!element || !targetContainer) {
    return;
  }

  switch (position) {
    case "append":
      targetContainer.appendChild(element);
      break;
    case "prepend":
      targetContainer.insertBefore(element, targetContainer.firstChild);
      break;
    case "before":
      targetContainer.parentNode.insertBefore(element, targetContainer);
      break;
    case "after":
      targetContainer.parentNode.insertBefore(element, targetContainer.nextSibling);
      break;
    default:
      // Invalid position
  }
}
