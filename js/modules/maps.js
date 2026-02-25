function showMapFallback(container, lat, lng, zoom) {
  const z = Math.min(17, Math.max(1, parseInt(zoom, 10) || 14));
  const yandexMapUrl = `https://yandex.ru/maps/?ll=${lng}%2C${lat}&z=${z}&pt=${lng},${lat}`;
  container.innerHTML = `
    <div class="hotel-map-fallback" style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1rem;width:100%;height:100%;min-height:300px;background:#f5f5f5;border-radius:8px;padding:1.5rem;text-align:center;">
      <p style="margin:0;color:#666;">Карта временно недоступна</p>
      <a href="${yandexMapUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-black sm">Открыть в Яндекс.Картах</a>
    </div>
  `;
}

function applyFallbackToAllMaps() {
  if (applyFallbackToAllMaps.done) return;
  applyFallbackToAllMaps.done = true;
  document.querySelectorAll(".hotel-map[data-lat][data-lng]").forEach((el) => {
    const lat = el.dataset.lat;
    const lng = el.dataset.lng;
    const zoom = el.dataset.zoom || "14";
    if (lat && lng && !el.querySelector(".hotel-map-fallback")) {
      showMapFallback(el, lat, lng, zoom);
    }
  });
}

export const initMaps = async () => {
  const mapElements = document.querySelectorAll("[data-lat][data-lng]");
  if (!mapElements.length) {
    return;
  }

  if (typeof ymaps3 === "undefined") {
    console.warn("Yandex Maps API v3 is not loaded");
    mapElements.forEach((el) => {
      const lng = parseFloat(el.dataset.lng);
      const lat = parseFloat(el.dataset.lat);
      const zoom = el.dataset.zoom || "14";
      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        showMapFallback(el, lat, lng, zoom);
      }
    });
    return;
  }

  try {
    await ymaps3.ready;
  } catch (e) {
    console.warn("Yandex Maps API failed to load", e);
    mapElements.forEach((el) => {
      const lng = parseFloat(el.dataset.lng);
      const lat = parseFloat(el.dataset.lat);
      const zoom = el.dataset.zoom || "14";
      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        showMapFallback(el, lat, lng, zoom);
      }
    });
    return;
  }

  const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker } = ymaps3;

  const BEHAVIORS_NO_SCROLL = ["drag", "dblClick"];
  const BEHAVIORS_WITH_SCROLL = ["drag", "dblClick", "scrollZoom"];

  const mapInstances = [];

  let YMapDefaultMarker;
  try {
    if (typeof ymaps3.import.registerCdn === "function") {
      ymaps3.import.registerCdn(
        "https://cdn.jsdelivr.net/npm/{package}",
        "@yandex/ymaps3-default-ui-theme@0.0"
      );
    }
    const defaultUiTheme = await ymaps3.import("@yandex/ymaps3-default-ui-theme");
    YMapDefaultMarker = defaultUiTheme.YMapDefaultMarker;
  } catch (e) {
    console.warn("YMapDefaultMarker (pin) not loaded", e);
  }

  function createCustomMarker(iconUrl, lng, lat) {
    const container = document.createElement("div");
    container.className = "hotel-map-marker";
    const img = document.createElement("img");
    img.src = iconUrl;
    img.alt = "";
    img.className = "hotel-map-marker__icon";
    container.appendChild(img);
    return new YMapMarker(
      { coordinates: [lng, lat], mapFollowsOnClick: false },
      container
    );
  }

  mapElements.forEach((el) => {
    const lng = parseFloat(el.dataset.lng);
    const lat = parseFloat(el.dataset.lat);
    const zoom = parseInt(el.dataset.zoom || "14", 10);
    const markerIconUrl = el.dataset.markerIcon || "";

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return;
    }

    try {
      const map = new YMap(el, {
        location: { center: [lng, lat], zoom },
        behaviors: BEHAVIORS_NO_SCROLL,
      });

      map.addChild(new YMapDefaultSchemeLayer());
      map.addChild(new YMapDefaultFeaturesLayer());

      if (markerIconUrl) {
        const marker = createCustomMarker(markerIconUrl, lng, lat);
        map.addChild(marker);
      } else if (YMapDefaultMarker) {
        const marker = new YMapDefaultMarker({
          coordinates: [lng, lat],
        });
        map.addChild(marker);
      }

      mapInstances.push({ map, container: el });
    } catch (err) {
      console.warn("Hotel map init failed", err);
      showMapFallback(el, lat, lng, el.dataset.zoom || "14");
    }
  });

  // Зум колёсиком включается после клика по карте, отключается при клике вне карты
  mapInstances.forEach(({ map, container }) => {
    container.addEventListener("mousedown", () => {
      if (typeof map.setBehaviors === "function") {
        map.setBehaviors(BEHAVIORS_WITH_SCROLL);
      }
    });
  });

  document.addEventListener("click", (e) => {
    const isInsideMap = mapInstances.some(({ container }) => container.contains(e.target));
    if (!isInsideMap) {
      mapInstances.forEach(({ map }) => {
        if (typeof map.setBehaviors === "function") {
          map.setBehaviors(BEHAVIORS_NO_SCROLL);
        }
      });
    }
  });

  // При асинхронной ошибке загрузки тайлов (ERR_SOCKET_NOT_CONNECTED / Failed to fetch) показываем запасной вариант
  window.addEventListener("unhandledrejection", function onMapRejection(event) {
    const msg = event.reason?.message || String(event.reason || "");
    if (msg.includes("Failed to fetch")) {
      applyFallbackToAllMaps();
      window.removeEventListener("unhandledrejection", onMapRejection);
    }
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
