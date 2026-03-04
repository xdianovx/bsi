export const initAgencyEventsFilter = () => {
  const root = document.querySelector("[data-agency-education]");
  if (!root) return;

  const list = root.querySelector("[data-agency-education-list]");
  if (!list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const tabs = root.querySelectorAll("[data-agency-kind]");
  const directionSelect = root.querySelector("[data-agency-direction]");

  let currentKind =
    root.querySelector("[data-agency-kind].is-active")?.dataset.agencyKind || "";
  let currentDirection = directionSelect ? directionSelect.value : "";

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
      if (currentKind) body.set("kind", currentKind);
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

  if (directionSelect) {
    directionSelect.addEventListener("change", () => {
      currentDirection = directionSelect.value;
      loadEvents();
    });
  }
};
