export const initMaps = () => {
  const mapElements = document.querySelectorAll("[data-lat][data-lng]");
  if (!mapElements.length) {
    return;
  }

  const initMap = async (el) => {
    const lng = parseFloat(el.dataset.lng);
    const lat = parseFloat(el.dataset.lat);
    const zoom = parseInt(el.dataset.zoom || "14", 10);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      console.warn("Invalid coordinates:", { lat, lng, element: el });
      return;
    }

    if (!el.offsetWidth || !el.offsetHeight) {
      console.warn("Map container has no dimensions:", {
        width: el.offsetWidth,
        height: el.offsetHeight,
        element: el,
      });
      el.style.minHeight = "400px";
      el.style.width = "100%";
    }

    const waitForYmaps3 = () => {
      return new Promise((resolve, reject) => {
        if (typeof window.ymaps3 !== "undefined" && window.ymaps3.ready) {
          resolve();
          return;
        }

        const checkYmaps3 = () => {
          if (typeof window.ymaps3 !== "undefined" && window.ymaps3.ready) {
            resolve();
            return true;
          }
          return false;
        };

        if (checkYmaps3()) {
          return;
        }

        let attempts = 0;
        const maxAttempts = 150;
        const checkInterval = setInterval(() => {
          attempts++;
          if (checkYmaps3()) {
            clearInterval(checkInterval);
            return;
          }
          if (attempts >= maxAttempts) {
            clearInterval(checkInterval);
            reject(
              new Error(
                `Yandex Maps API v3 failed to load after ${maxAttempts * 0.1} seconds. Script tag present: ${document.querySelector('script[src*="api-maps.yandex.ru"]') !== null}`
              )
            );
          }
        }, 100);

        window.addEventListener("load", () => {
          setTimeout(() => {
            if (checkYmaps3()) {
              clearInterval(checkInterval);
            }
          }, 1000);
        });
      });
    };

    try {
      await waitForYmaps3();

      if (typeof window.ymaps3 === "undefined") {
        throw new Error("window.ymaps3 is undefined after wait");
      }

      if (!window.ymaps3.ready) {
        throw new Error("ymaps3.ready is not available");
      }

      await window.ymaps3.ready;

      const { YMap, YMapDefaultSchemeLayer, YMapMarker } = window.ymaps3;

      if (!YMap || !YMapDefaultSchemeLayer) {
        throw new Error("YMap or YMapDefaultSchemeLayer is not available");
      }

      const map = new YMap(el, {
        location: { center: [lng, lat], zoom },
      });

      map.addChild(new YMapDefaultSchemeLayer());

      if (YMapMarker) {
        const marker = new YMapMarker({
          coordinates: [lng, lat],
          mapFollowsOnClick: false,
        });
        map.addChild(marker);
      }
    } catch (error) {
      console.error("Error initializing Yandex Map:", error, {
        lat,
        lng,
        zoom,
        element: el,
        ymaps3Available: typeof ymaps3 !== "undefined",
      });
      el.style.backgroundColor = "#f0f0f0";
      el.style.display = "flex";
      el.style.alignItems = "center";
      el.style.justifyContent = "center";
      el.style.minHeight = "400px";
      el.innerHTML = '<p style="color: #999; padding: 20px;">Карта временно недоступна</p>';
    }
  };

  mapElements.forEach((el) => {
    initMap(el);
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
