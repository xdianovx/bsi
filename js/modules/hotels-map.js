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

  try {
    await ymaps3.ready;
  } catch (error) {
    return;
  }

  const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker } = ymaps3;
  const single = points.length === 1;

  try {
    const map = new YMap(container, {
      location: single ? { center: [points[0].lng, points[0].lat], zoom: 14 } : { bounds: boundsOf(points) },
      behaviors: ["drag", "dblClick"],
    });

    map.addChild(new YMapDefaultSchemeLayer());
    map.addChild(new YMapDefaultFeaturesLayer());

    points.forEach((point) => {
      map.addChild(new YMapMarker({ coordinates: [point.lng, point.lat], mapFollowsOnClick: false }, markerElement(point)));
    });

    container.dataset.ready = "1";
  } catch (error) {
    container.remove();
  }
};
