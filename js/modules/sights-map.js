import { waitYmaps3 } from "./services/ymaps-loader";

/**
 * Общая карта каталога достопримечательностей — /country/{slug}/dostoprimechatelnosti/
 *
 * Контейнер: [data-sights-map] с атрибутом data-points (JSON: lat, lng, title, url).
 * Точечная карта на странице места рисуется общим модулем maps.js по data-lat/data-lng.
 */

function readPoints(el) {
  try {
    const parsed = JSON.parse(el.dataset.points || "[]");
    if (!Array.isArray(parsed)) return [];
    return parsed.filter((p) => Number.isFinite(parseFloat(p.lat)) && Number.isFinite(parseFloat(p.lng)));
  } catch (e) {
    console.warn("Sights map: broken data-points", e);
    return [];
  }
}

function fitBounds(points) {
  const lats = points.map((p) => parseFloat(p.lat));
  const lngs = points.map((p) => parseFloat(p.lng));

  const minLat = Math.min(...lats);
  const maxLat = Math.max(...lats);
  const minLng = Math.min(...lngs);
  const maxLng = Math.max(...lngs);

  const center = [(minLng + maxLng) / 2, (minLat + maxLat) / 2];
  const span = Math.max(maxLat - minLat, maxLng - minLng);

  let zoom = 10;
  if (span > 20) zoom = 3;
  else if (span > 10) zoom = 4;
  else if (span > 5) zoom = 5;
  else if (span > 2) zoom = 6;
  else if (span > 1) zoom = 7;
  else if (span > 0.5) zoom = 8;
  else if (span > 0.1) zoom = 10;
  else zoom = 12;

  return { center, zoom };
}

function readIcons(el) {
  try {
    const parsed = JSON.parse(el.dataset.icons || "{}");
    return parsed && typeof parsed === "object" ? parsed : {};
  } catch (e) {
    console.warn("Sights map: broken data-icons", e);
    return {};
  }
}

function createMarkerElement(point, icons) {
  const link = document.createElement("a");
  link.className = "sights-map-marker";
  link.href = point.url || "#";

  const pin = document.createElement("span");
  pin.className = "sights-map-marker-pin";

  // Иконка типа достопримечательности; без неё — точка
  const inner = icons[point.icon];
  if (inner) {
    pin.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" ' +
      'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" ' +
      'stroke-linejoin="round">' +
      inner +
      "</svg>";
  } else {
    pin.classList.add("sights-map-marker-pin-empty");
  }

  link.appendChild(pin);

  // Подсказка под точкой: фото (если есть) и название — появляется по наведению
  const card = document.createElement("span");
  card.className = "sights-map-marker-card";

  if (point.image) {
    const image = document.createElement("img");
    image.className = "sights-map-marker-image";
    image.src = point.image;
    image.alt = "";
    image.loading = "lazy";
    card.appendChild(image);
  }

  const label = document.createElement("span");
  label.className = "sights-map-marker-label";
  label.textContent = point.title || "";
  card.appendChild(label);

  link.appendChild(card);

  // Ymaps кладёт каждый маркер в свою обёртку со своим z-index, поэтому
  // z-index внутри маркера не спасает: поднимаем саму обёртку на время ховера
  const markerHolder = () => {
    const parent = link.parentElement;
    if (!parent) return null;
    return parent.closest('[class*="ymaps3"]') || parent;
  };

  link.addEventListener("mouseenter", () => {
    const holder = markerHolder();
    if (!holder) return;
    holder.dataset.prevZIndex = holder.style.zIndex || "";
    holder.style.zIndex = "1000";
  });

  link.addEventListener("mouseleave", () => {
    const holder = markerHolder();
    if (!holder) return;
    holder.style.zIndex = holder.dataset.prevZIndex || "";
    delete holder.dataset.prevZIndex;
  });

  return link;
}

// pinchZoom/oneFingerZoom — зум на тачах: без них на мобилке карту не масштабировать.
const BEHAVIORS_NO_SCROLL = ["drag", "dblClick", "pinchZoom", "oneFingerZoom"];
const BEHAVIORS_WITH_SCROLL = [...BEHAVIORS_NO_SCROLL, "scrollZoom"];

export const initSightsMap = async () => {
  const containers = document.querySelectorAll("[data-sights-map]");
  if (!containers.length) return;

  // API недоступен (блокировка, оффлайн) — пустой серый блок хуже, чем его отсутствие
  const dropContainers = () => containers.forEach((el) => el.remove());

  const ymaps3 = await waitYmaps3();
  if (!ymaps3) {
    dropContainers();
    return;
  }

  const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker } = ymaps3;

  containers.forEach((el) => {
    const points = readPoints(el);
    if (!points.length) {
      el.remove();
      return;
    }

    const icons = readIcons(el);
    const { center, zoom } = fitBounds(points);

    try {
      const map = new YMap(el, {
        location: { center, zoom },
        behaviors: BEHAVIORS_NO_SCROLL,
      });

      map.addChild(new YMapDefaultSchemeLayer());
      map.addChild(new YMapDefaultFeaturesLayer());

      points.forEach((point) => {
        map.addChild(
          new YMapMarker(
            {
              coordinates: [parseFloat(point.lng), parseFloat(point.lat)],
              mapFollowsOnClick: false,
            },
            createMarkerElement(point, icons)
          )
        );
      });

      // Зум колёсиком — только после клика по карте, чтобы не перехватывать скролл страницы
      el.addEventListener("mousedown", () => {
        if (typeof map.setBehaviors === "function") {
          map.setBehaviors(BEHAVIORS_WITH_SCROLL);
        }
      });

      document.addEventListener("click", (e) => {
        if (!el.contains(e.target) && typeof map.setBehaviors === "function") {
          map.setBehaviors(BEHAVIORS_NO_SCROLL);
        }
      });
    } catch (err) {
      console.warn("Sights map init failed", err);
      el.remove();
    }
  });
};
