export const promoPageAjax = () => {
  if (typeof ajax === "undefined" || !ajax.url) {
    return;
  }

  const filterRoot = document.querySelector(".promo-filter--page");
  const buttons = filterRoot
    ? filterRoot.querySelectorAll(".js-promo-filter-btn")
    : [];
  const list = document.querySelector(".js-promo-list");
  const archivedToggle = document.querySelector(".js-promo-archived-toggle");

  if (!(filterRoot instanceof HTMLElement) || !buttons.length || !list) {
    return;
  }

  const setLoading = (state) => {
    if (state) {
      list.classList.add("is-loading");
    } else {
      list.classList.remove("is-loading");
    }
  };

  const isArchived = () =>
    archivedToggle instanceof HTMLInputElement && archivedToggle.checked;

  const updateFilterCountsUi = () => {
    const archived = isArchived();

    buttons.forEach((btn) => {
      if (!(btn instanceof HTMLElement)) {
        return;
      }

      const isAll = btn.classList.contains("--all");
      const raw = archived
        ? btn.getAttribute("data-count-archived")
        : btn.getAttribute("data-count-active");
      const n = parseInt(raw || "0", 10);

      if (isAll) {
        btn.textContent = `Все (${n})`;
        return;
      }

      const countEl = btn.querySelector(".promo-filter__count");
      if (n > 0) {
        if (countEl) {
          countEl.textContent = String(n);
        } else {
          const span = document.createElement("span");
          span.className = "promo-filter__count";
          span.textContent = String(n);
          btn.appendChild(span);
        }
      } else if (countEl) {
        countEl.remove();
      }
    });
  };

  const fetchPromosForCurrentFilter = () => {
    const activeBtn = filterRoot.querySelector(".js-promo-filter-btn.active");
    const btnEl =
      activeBtn instanceof HTMLElement
        ? activeBtn
        : filterRoot.querySelector(".js-promo-filter-btn");

    const country =
      btnEl instanceof HTMLElement && btnEl.getAttribute("data-country") !== null
        ? btnEl.getAttribute("data-country") || ""
        : "";

    setLoading(true);

    const formData = new FormData();
    formData.append("action", "bsi_filter_promos");
    formData.append("country", country);
    formData.append("archived", isArchived() ? "1" : "0");

    fetch(ajax.url, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((html) => {
        list.innerHTML = html;
      })
      .catch(() => {})
      .finally(() => {
        setLoading(false);
      });
  };

  const handleClick = (event) => {
    event.preventDefault();

    const btn = event.currentTarget;
    buttons.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");

    fetchPromosForCurrentFilter();
  };

  if (archivedToggle) {
    archivedToggle.addEventListener("change", () => {
      updateFilterCountsUi();
      fetchPromosForCurrentFilter();
    });
  }

  buttons.forEach((btn) => {
    btn.addEventListener("click", handleClick);
  });
};
