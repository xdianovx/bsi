export const initMaps = async () => {
  const mapElements = document.querySelectorAll("[data-lat][data-lng]");
  if (!mapElements.length) {
    return;
  }

  if (typeof ymaps3 === "undefined") {
    console.error("Yandex Maps API v3 is not loaded");
    return;
  }

  await ymaps3.ready;

  const { YMap, YMapDefaultSchemeLayer, YMapMarker } = ymaps3;

  mapElements.forEach((el) => {
    const lng = parseFloat(el.dataset.lng);
    const lat = parseFloat(el.dataset.lat);
    const zoom = parseInt(el.dataset.zoom || "14", 10);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return;
    }

    const map = new YMap(el, {
      location: { center: [lng, lat], zoom },
    });

    map.addChild(new YMapDefaultSchemeLayer());

    const marker = new YMapMarker({
      coordinates: [lng, lat],
      mapFollowsOnClick: false,
    });

    map.addChild(marker);
  });

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
