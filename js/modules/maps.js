export const initMaps = () => {
  const mapElements = document.querySelectorAll("[data-lat][data-lng]");
  if (!mapElements.length) return;

  const initMap = (el) => {
    const lng = parseFloat(el.dataset.lng);
    const lat = parseFloat(el.dataset.lat);
    const zoom = parseInt(el.dataset.zoom || "14", 10);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    const start = () => {
      ymaps3.ready.then(() => {
        const { YMap, YMapDefaultSchemeLayer } = ymaps3;

        const map = new YMap(el, {
          location: { center: [lng, lat], zoom },
        });

        map.addChild(new YMapDefaultSchemeLayer());
      });
    };

    if (window.ymaps3) {
      start();
    } else {
      window.addEventListener("load", () => {
        if (window.ymaps3) start();
      });
    }
  };

  mapElements.forEach(initMap);

  const mapButtons = document.querySelectorAll(".hotel-widget__btn-map");
  mapButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const targetId = btn.getAttribute("href");
      if (targetId && targetId.startsWith("#")) {
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          targetElement.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }
    });
  });
};

export const initHotelMap = initMaps;
