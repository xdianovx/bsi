/**
 * Карта каталога отелей: метки всех отелей текущей выдачи.
 *
 * Координаты приходят вместе с карточками списка, поэтому карта рисуется
 * из тех же данных, что и сетка — дополнительных запросов нет.
 * Каталог подгружается без перезагрузки, поэтому карта пересобирается
 * после каждой подмены разметки (см. js/modules/ajax/hotels-api-catalog.js).
 */

const escapeHtml = (value) =>
  String(value ?? "").replace(/[&<>"']/g, (char) => {
    const map = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" };
    return map[char];
  });

/** Прямоугольник, в который попадают все метки. */
const boundsOf = (points) => {
  const lats = points.map((p) => p.lat);
  const lngs = points.map((p) => p.lng);

  return [
    [Math.min(...lngs), Math.max(...lats)],
    [Math.max(...lngs), Math.min(...lats)],
  ];
};

const markerElement = (point) => {
  const el = document.createElement("a");
  el.className = "hotels-map__pin";
  el.href = point.url || "#";
  el.innerHTML = `
    <span class="hotels-map__pin-dot"></span>
    <span class="hotels-map__pin-card">
      <b>${escapeHtml(point.name)}${point.stars ? ` ${point.stars}*` : ""}</b>
      ${point.city ? `<span>${escapeHtml(point.city)}</span>` : ""}
      ${point.price ? `<span class="hotels-map__pin-price">${escapeHtml(point.price)}</span>` : ""}
    </span>
  `;

  return el;
};

/** Начиная со скольких точек метки группируются в кластеры. */
const CLUSTER_FROM = 40;

/** Слушатели прошлой карты: каталог перерисовывается, старые надо снимать. */
let listeners = null;

export const initHotelsMap = async () => {
  const container = document.querySelector(".js-hotels-map");
  const data = document.querySelector(".js-hotels-map-data");

  if (!container || !data || container.dataset.ready === "1") return;

  let points;
  try {
    points = JSON.parse(data.textContent || "[]");
  } catch (error) {
    return;
  }

  if (!points.length || typeof ymaps3 === "undefined") return;

  listeners?.abort();
  listeners = new AbortController();
  const { signal } = listeners;

  try {
    await ymaps3.ready;
  } catch (error) {
    return;
  }

  const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker, YMapControls } = ymaps3;
  const single = points.length === 1;

  // Кнопки зума живут в отдельном пакете темы — без них останется только
  // перетаскивание и двойной клик.
  let YMapZoomControl;
  try {
    if (typeof ymaps3.import.registerCdn === "function") {
      ymaps3.import.registerCdn("https://cdn.jsdelivr.net/npm/{package}", "@yandex/ymaps3-default-ui-theme@0.0");
    }
    ({ YMapZoomControl } = await ymaps3.import("@yandex/ymaps3-default-ui-theme"));
  } catch (error) {
    YMapZoomControl = null;
  }

  /* Точек бывает больше тысячи: без группировки они сливаются в пятно.
     Пакет внешний — не загрузился, рисуем метки поштучно. */
  let clusterer = null;
  if (points.length > CLUSTER_FROM) {
    try {
      if (typeof ymaps3.import.registerCdn === "function") {
        ymaps3.import.registerCdn("https://cdn.jsdelivr.net/npm/{package}", "@yandex/ymaps3-clusterer@0.0");
      }
      clusterer = await ymaps3.import("@yandex/ymaps3-clusterer");
    } catch (error) {
      clusterer = null;
    }
  }

  // Наведённая метка встаёт над соседними — иначе они закрывают её карточку.
  const BASE_Z = 1000;
  const HOVER_Z = 9999;

  // Колесо перехватываем только после клика по карте, иначе страница
  // перестаёт прокручиваться над ней.
  const CALM = ["drag", "dblClick", "pinchZoom"];
  const ACTIVE = ["drag", "dblClick", "pinchZoom", "scrollZoom"];

  try {
    const map = new YMap(container, {
      location: single ? { center: [points[0].lng, points[0].lat], zoom: 14 } : { bounds: boundsOf(points) },
      behaviors: CALM,
    });

    map.addChild(new YMapDefaultSchemeLayer());
    map.addChild(new YMapDefaultFeaturesLayer());

    if (YMapZoomControl && YMapControls) {
      map.addChild(new YMapControls({ position: "right" }).addChild(new YMapZoomControl({})));
    }

    container.addEventListener("mousedown", () => map.setBehaviors?.(ACTIVE), { signal });
    document.addEventListener(
      "click",
      (event) => {
        if (!container.contains(event.target)) map.setBehaviors?.(CALM);
      },
      { signal },
    );

    const createMarker = (point) => {
      const element = markerElement(point);
      const marker = new YMapMarker(
        { coordinates: [point.lng, point.lat], mapFollowsOnClick: false, zIndex: BASE_Z },
        element,
      );

      /* Метки лежат в общем слое карты: соседние точки перекрывают раскрытую
         карточку. Поднимаем наведённую метку над остальными — и в слое карты,
         и в самом DOM, потому что каждый маркер сидит в своей обёртке. */
      const lift = (up) => {
        marker.update({ zIndex: up ? HOVER_Z : BASE_Z });
        if (element.parentElement) {
          element.parentElement.style.zIndex = up ? String(HOVER_Z) : "";
        }
      };

      element.addEventListener("mouseenter", () => lift(true), { signal });
      element.addEventListener("mouseleave", () => lift(false), { signal });

      return marker;
    };

    if (clusterer) {
      map.addChild(
        new clusterer.YMapClusterer({
          method: clusterer.clusterByGrid({ gridSize: 64 }),
          features: points.map((point, index) => ({
            type: "Feature",
            id: String(index),
            geometry: { type: "Point", coordinates: [point.lng, point.lat] },
            properties: point,
          })),
          marker: (feature) => createMarker(feature.properties),
          cluster: (coordinates, features) => {
            const element = document.createElement("div");
            element.className = "hotels-map__cluster";
            element.textContent = String(features.length);

            element.addEventListener("click", () => {
              map.setLocation({ center: coordinates, zoom: map.zoom + 2, duration: 300 });
            });

            return new YMapMarker({ coordinates, zIndex: BASE_Z }, element);
          },
        }),
      );
    } else {
      points.forEach((point) => map.addChild(createMarker(point)));
    }

    container.dataset.ready = "1";
  } catch (error) {
    container.remove();
  }
};
