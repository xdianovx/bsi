export const archiveProjects = () => {
  const root = document.querySelector(".projects-archive");
  if (!root) return;

  const filter = root.querySelector(".projects-filter");
  const list = root.querySelector(".js-projects-list");
  if (!filter || !list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const setActive = (btn) => {
    filter.querySelectorAll(".js-projects-filter-btn").forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  };

  const setLoading = (on) => root.classList.toggle("is-loading", !!on);

  const load = async (countryId) => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "projects_by_country");
      body.set("country_id", String(countryId || ""));

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      list.innerHTML = json.data?.html || "";
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  filter.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-projects-filter-btn");
    if (!btn) return;

    e.preventDefault();
    setActive(btn);

    const countryId = btn.getAttribute("data-country") || "";
    load(countryId);
  });
};
