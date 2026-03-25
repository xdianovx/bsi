export const initAgencyEventsFilter = () => {
  const root = document.querySelector("[data-agency-education]");
  if (!root) return;

  const list = root.querySelector("[data-agency-education-list]");
  if (!list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const tabs = root.querySelectorAll("[data-agency-kind]");
  const directionSelect = root.querySelector("[data-agency-direction]");
  const archiveCheckbox = document.querySelector(
    "[data-agency-archive-toggle]"
  );

  let currentKind =
    root.querySelector("[data-agency-kind].is-active")?.dataset.agencyKind || "";
  let currentDirection = directionSelect ? directionSelect.value : "";
  let isArchive = archiveCheckbox ? archiveCheckbox.checked : false;

  const syncArchiveInUrl = (checked) => {
    if (!archiveCheckbox) return;
    try {
      const url = new URL(window.location.href);
      if (checked) {
        url.searchParams.set("archive", "1");
      } else {
        url.searchParams.delete("archive");
      }
      // Сохраняем адрес без перезагрузки страницы.
      window.history.replaceState({}, "", url.toString());
    } catch (e) {
      // URL может не парситься в некоторых окружениях — в таком случае просто молча игнорируем.
    }
  };

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  const setActiveTab = (slug) => {
    tabs.forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.agencyKind === slug);
    });
  };

  const loadEvents = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "agency_events_filter");
      if (isArchive) body.set("archive", "1");
      if (!isArchive && currentKind) {
        body.set("kind", currentKind);
      } else if (isArchive && currentKind) {
        body.set("kind", currentKind);
      }
      if (currentDirection) body.set("direction", currentDirection);

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      list.innerHTML = json.data.html || "";
    } catch (e) {
      list.innerHTML =
        '<div class="agency-page__empty">Ошибка загрузки. Попробуйте ещё раз.</div>';
    } finally {
      setLoading(false);
    }
  };

  tabs.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const kind = btn.dataset.agencyKind;
      if (kind === currentKind) return;
      currentKind = kind;
      setActiveTab(kind);
      loadEvents();
    });
  });

  if (archiveCheckbox) {
    const onToggle = () => {
      isArchive = archiveCheckbox.checked;
      syncArchiveInUrl(isArchive);
      loadEvents();
    };

    archiveCheckbox.addEventListener("change", onToggle);
    archiveCheckbox.addEventListener("input", onToggle);
  }

  if (directionSelect) {
    directionSelect.addEventListener("change", () => {
      currentDirection = directionSelect.value;
      loadEvents();
    });
  }
};
