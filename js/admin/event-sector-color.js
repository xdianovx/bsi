/**
 * Пипетка цвета сектора: кнопка у «Цвет на схеме» открывает схему зала,
 * клик по схеме записывает цвет пикселя в поле сектора.
 * Подключение — inc/admin/event-admin.php.
 */
(function ($) {
  if (typeof acf === "undefined") return;

  const SCHEME_KEY = "field_event_venue_scheme";
  const COLOR_KEY = "field_event_sector_color";
  const NAME_KEY = "field_event_sector_name";

  function schemeUrl() {
    const field = acf.getField(SCHEME_KEY);
    if (!field || !field.$el) return Promise.resolve("");
    const id = parseInt(field.val(), 10);
    const fallback = field.$el.find(".image-wrap img").attr("src") || "";
    if (!id || !window.wp || !wp.media || !wp.media.attachment) return Promise.resolve(id ? fallback : "");
    const att = wp.media.attachment(id);
    return Promise.resolve(att.fetch())
      .then(() => att.get("url") || fallback)
      .catch(() => fallback);
  }

  function toHex(r, g, b) {
    return "#" + [r, g, b].map((v) => v.toString(16).padStart(2, "0")).join("");
  }

  function setColor(field, hex) {
    const $text = field.$el.find("input.wp-color-picker");
    if ($text.length && $text.wpColorPicker) {
      $text.wpColorPicker("color", hex);
    } else {
      field.val(hex);
    }
  }

  function sectorName(field) {
    const $row = field.$el.closest(".acf-row");
    return $.trim(
      $row
        .find('.acf-field[data-key="' + NAME_KEY + '"] input')
        .first()
        .val() || ""
    );
  }

  function openPipette(field) {
    schemeUrl().then((url) => {
      if (!url) {
        window.alert("Сначала загрузите схему зала — поле над секторами.");
        return;
      }

      const name = sectorName(field);
      const $overlay = $(
        '<div class="bsi-pipette">' +
          '<div class="bsi-pipette__bar">' +
          '<span class="bsi-pipette__title"></span>' +
          '<span class="bsi-pipette__swatch"></span>' +
          '<span class="bsi-pipette__hex"></span>' +
          '<button type="button" class="button bsi-pipette__close">Отмена</button>' +
          "</div>" +
          '<div class="bsi-pipette__stage"><canvas class="bsi-pipette__canvas"></canvas></div>' +
          "</div>"
      );
      $overlay.find(".bsi-pipette__title").text("Нажмите на цвет " + (name ? "сектора «" + name + "»" : "сектора") + " на схеме");
      $("body").append($overlay);

      const canvas = $overlay.find("canvas")[0];
      const ctx = canvas.getContext("2d", { willReadFrequently: true });
      const $stage = $overlay.find(".bsi-pipette__stage");
      const $swatch = $overlay.find(".bsi-pipette__swatch");
      const $hex = $overlay.find(".bsi-pipette__hex");

      const close = () => {
        $overlay.remove();
        $(document).off("keydown.bsiPipette");
      };
      $overlay.on("click", ".bsi-pipette__close", close);
      $overlay.on("click", (e) => {
        if (e.target === $overlay[0] || e.target === $stage[0]) close();
      });
      $(document).on("keydown.bsiPipette", (e) => {
        if (e.key === "Escape") close();
      });

      const img = new Image();
      img.crossOrigin = "anonymous";
      img.onload = () => {
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        ctx.drawImage(img, 0, 0);
        // Вписать схему в экран: CSS-размер, пиксели берутся из натурального.
        const scale = Math.min(1, $stage.width() / img.naturalWidth, $stage.height() / img.naturalHeight);
        canvas.style.width = Math.round(img.naturalWidth * scale) + "px";
        canvas.style.height = Math.round(img.naturalHeight * scale) + "px";
      };
      img.onerror = () => {
        close();
        window.alert("Не удалось открыть схему зала.");
      };
      img.src = url;

      const pick = (e) => {
        const rect = canvas.getBoundingClientRect();
        const x = Math.floor(((e.clientX - rect.left) * canvas.width) / rect.width);
        const y = Math.floor(((e.clientY - rect.top) * canvas.height) / rect.height);
        try {
          const d = ctx.getImageData(x, y, 1, 1).data;
          return toHex(d[0], d[1], d[2]);
        } catch (err) {
          return null;
        }
      };

      $(canvas).on("mousemove", (e) => {
        const hex = pick(e);
        if (!hex) return;
        $swatch.css("background", hex);
        $hex.text(hex);
      });
      $(canvas).on("click", (e) => {
        const hex = pick(e);
        if (!hex) {
          close();
          window.alert("Браузер не дал прочитать цвет со схемы. Укажите цвет вручную.");
          return;
        }
        setColor(field, hex);
        close();
      });
    });
  }

  function addButton(field) {
    if (field.$el.find(".bsi-pipette-btn").length) return;
    const $btn = $(
      '<button type="button" class="button bsi-pipette-btn">' +
        '<span class="dashicons dashicons-color-picker"></span>Со схемы' +
        "</button>"
    );
    $btn.on("click", () => openPipette(field));
    field.$el.find(".acf-input").append($btn);
  }

  acf.addAction("ready_field/key=" + COLOR_KEY, addButton);
  acf.addAction("append_field/key=" + COLOR_KEY, addButton);
})(jQuery);
