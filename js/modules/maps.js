export const initHotelMap = () => {
  const el = document.querySelector(".hotel-map");
  if (!el) return;

  // важно: в v3 порядок координат: [lng, lat]
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

  // если скрипт уже есть — стартуем, если нет — просто выходим
  if (window.ymaps3) start();
};
