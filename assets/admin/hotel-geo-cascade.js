(function () {
  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function createBtn(text) {
    var b = document.createElement("button");
    b.type = "button";
    b.className = "button button-secondary";
    b.textContent = text;
    return b;
  }

  function getFieldWrap(fieldKey) {
    return qs('[data-key="' + fieldKey + '"]');
  }

  function getSelect(fieldKey) {
    var wrap = getFieldWrap(fieldKey);
    if (!wrap) return null;
    return qs("select", wrap);
  }

  function getValue(fieldKey) {
    var sel = getSelect(fieldKey);
    return sel ? sel.value : "";
  }

  function clearSelect(select) {
    if (!select) return;
    select.innerHTML = "";
    var opt = document.createElement("option");
    opt.value = "";
    opt.textContent = "Выбрать";
    select.appendChild(opt);
    select.value = "";
    select.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function fillSelect(select, items) {
    if (!select) return;
    clearSelect(select);

    items.forEach(function (it) {
      var opt = document.createElement("option");
      opt.value = String(it.id);
      opt.textContent = it.text;
      select.appendChild(opt);
    });

    // обновить select2 если он есть (ACF использует select2)
    if (window.jQuery && window.jQuery(select).data("select2")) {
      window.jQuery(select).trigger("change.select2");
    } else {
      select.dispatchEvent(new Event("change", { bubbles: true }));
    }
  }

  function postAjax(dataObj) {
    var url = window.BSI_GEO && BSI_GEO.ajaxUrl ? BSI_GEO.ajaxUrl : "/wp-admin/admin-ajax.php";
    var fd = new FormData();
    Object.keys(dataObj).forEach(function (k) {
      fd.append(k, dataObj[k]);
    });

    return fetch(url, {
      method: "POST",
      credentials: "same-origin",
      body: fd,
    }).then(function (r) {
      return r.json();
    });
  }

  function injectUI() {
    var countryWrap = getFieldWrap("field_hotel_country");
    var regionWrap = getFieldWrap("field_hotel_region");
    var resortWrap = getFieldWrap("field_hotel_resort");

    if (!countryWrap || !regionWrap || !resortWrap) return;

    // очистим всё, чтобы не было “дефолтного списка” ACF
    clearSelect(getSelect("field_hotel_region"));
    clearSelect(getSelect("field_hotel_resort"));
    // панель кнопок под ГЕО
    var panel = document.createElement("div");
    panel.style.display = "flex";
    panel.style.gap = "8px";
    panel.style.margin = "8px 0 0";

    var btnRegions = createBtn("Загрузить регионы");
    var btnResorts = createBtn("Загрузить курорты");

    panel.appendChild(btnRegions);
    panel.appendChild(btnResorts);

    // вставим после поля Страна (чтобы рядом было)
    countryWrap.appendChild(panel);

    function updateButtonsState() {
      btnRegions.disabled = !getValue("field_hotel_country");
      btnResorts.disabled = !getValue("field_hotel_region");
    }

    // стартовое состояние
    updateButtonsState();

    // при смене страны чистим списки и блокируем курорты
    countryWrap.addEventListener("change", function () {
      clearSelect(getSelect("field_hotel_region"));
      clearSelect(getSelect("field_hotel_resort"));
      updateButtonsState();
    });

    // при смене региона чистим курорты
    regionWrap.addEventListener("change", function () {
      clearSelect(getSelect("field_hotel_resort"));
      updateButtonsState();
    });

    btnRegions.addEventListener("click", function () {
      var countryId = getValue("field_hotel_country");
      if (!countryId) return;

      btnRegions.disabled = true;
      btnRegions.textContent = "Загрузка...";

      postAjax({
        action: "bsi_geo_terms",
        nonce: window.BSI_GEO && BSI_GEO.nonce ? BSI_GEO.nonce : "",
        taxonomy: "region",
        country_id: countryId,
      })
        .then(function (json) {
          var items = json && json.success && Array.isArray(json.data) ? json.data : [];
          fillSelect(getSelect("field_hotel_region"), items);
          clearSelect(getSelect("field_hotel_resort"));
        })
        .finally(function () {
          btnRegions.textContent = "Загрузить регионы";
          updateButtonsState();
        });
    });

    btnResorts.addEventListener("click", function () {
      var regionId = getValue("field_hotel_region");
      if (!regionId) return;

      btnResorts.disabled = true;
      btnResorts.textContent = "Загрузка...";

      postAjax({
        action: "bsi_geo_terms",
        nonce: window.BSI_GEO && BSI_GEO.nonce ? BSI_GEO.nonce : "",
        taxonomy: "resort",
        region_id: regionId,
      })
        .then(function (json) {
          var items = json && json.success && Array.isArray(json.data) ? json.data : [];
          fillSelect(getSelect("field_hotel_resort"), items);
        })
        .finally(function () {
          btnResorts.textContent = "Загрузить курорты";
          updateButtonsState();
        });
    });
  }

  // ACF может рендерить поля позже → ждём готовности
  if (window.acf && acf.add_action) {
    acf.add_action("ready", injectUI);
    acf.add_action("append", injectUI);
  } else {
    document.addEventListener("DOMContentLoaded", injectUI);
  }
})();
