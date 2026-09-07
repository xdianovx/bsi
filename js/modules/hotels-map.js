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

/** Точки ближе этого считаем одной: примерно десяток метров. */
const SAME_SPOT = 0.0002;

/** Разброс координат в наборе — по нему видно, распадётся ли кластер. */
const spread = (features) => {
  const lngs = features.map((f) => f.geometry.coordinates[0]);
  const lats = features.map((f) => f.geometry.coordinates[1]);

  return Math.max(Math.max(...lngs) - Math.min(...lngs), Math.max(...lats) - Math.min(...lats));
};

/** Прямоугольник, в который попадают все метки. */
const boundsOf = (points) => {
  const lats = points.map((p) => p.lat);
  const lngs = points.map((p) => p.lng);

  return [
    [Math.min(...lngs), Math.max(...lats)],
    [Math.max(...lngs), Math.min(...lats)],
  ];
};

/** Короткая цена для метки: «от 94 $ за ночь» → «94 $». */
const shortPrice = (price) => String(price || "").replace(/^от\s*/, "").replace(/\s*за ночь$/, "");

const markerElement = (point) => {
  const el = document.createElement("a");
  el.className = point.price ? "hotels-map__pin hotels-map__pin--price" : "hotels-map__pin";
  el.href = point.url || "#";

  // Цена стоит прямо на метке, пока метки не сгрудились: плотные группы
  // прячет кластеризация, поэтому отдельная метка почти всегда свободна.
  const label = point.price
    ? `<span class="hotels-map__pin-label">${escapeHtml(shortPrice(point.price))}</span>`
    : '<span class="hotels-map__pin-dot"></span>';

  el.innerHTML = `
    ${label}
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

  /* Карта охватывает все метки выборки: одна точка — крупный план, разбросаны
     по стране — общий. Отступы, чтобы крайние метки не липли к краю. */
  const lngs = points.map((point) => point.lng);
  const lats = points.map((point) => point.lat);
  const tight = Math.max(Math.max(...lngs) - Math.min(...lngs), Math.max(...lats) - Math.min(...lats)) < SAME_SPOT;

  const location = tight
    ? { center: [points[0].lng, points[0].lat], zoom: 15 }
    : { bounds: boundsOf(points), padding: { top: 48, bottom: 48, left: 48, right: 48 } };

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

  /* Список отелей кластера, который не разъезжается по зуму. */
  const popup = document.createElement("div");
  popup.className = "hotels-map__popup";
  popup.hidden = true;
  container.append(popup);

  const showList = (list) => {
    popup.innerHTML = `
      <button class="hotels-map__popup-close" type="button" aria-label="Закрыть">&times;</button>
      <p class="hotels-map__popup-title">Отелей в этой точке: ${list.length}</p>
      <div class="hotels-map__popup-list">
        ${list
          .map(
            (point) => `
          <a class="hotels-map__popup-item" href="${escapeHtml(point.url || "#")}">
            <b>${escapeHtml(point.name)}${point.stars ? ` ${point.stars}*` : ""}</b>
            ${point.price ? `<span>${escapeHtml(point.price)}</span>` : ""}
          </a>`,
          )
          .join("")}
      </div>
    `;
    popup.hidden = false;
  };

  popup.addEventListener("click", (event) => {
    if (event.target.closest(".hotels-map__popup-close")) popup.hidden = true;
  });

  try {
    const map = new YMap(container, { location, behaviors: CALM });

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
              /* Отели одного комплекса стоят в одной точке, и увеличение такую
                 группу не разбивает — показываем список прямо на карте. */
              if (spread(features) < SAME_SPOT) {
                showList(features.map((feature) => feature.properties));
                return;
              }

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
