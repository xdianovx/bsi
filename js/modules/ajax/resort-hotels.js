export const initResortHotelsAjax = () => {
  function qs(root, sel) {
    return (root || document).querySelector(sel);
  }

  async function loadHotels(state) {
    if (state.loading) return;
    state.loading = true;

    state.wrap.classList.add("is-loading");
    if (state.btn) state.btn.disabled = true;

    try {
      const body = new URLSearchParams();
      body.set("action", "bsi_resort_hotels");
      body.set("term_id", String(state.termId));
      body.set("page", String(state.page));
      body.set("per_page", String(state.perPage));
      body.set("orderby", state.orderby);
      body.set("order", state.order);

      const res = await fetch(ajax.url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) {
        throw new Error(json?.data?.message || "AJAX error");
      }

      // сервер возвращает уже html карточек
      const html = json.data.html || "";
      if (html) state.list.insertAdjacentHTML("beforeend", html);

      state.hasMore = !!json.data.has_more;
      if (state.btn) state.btn.style.display = state.hasMore ? "" : "none";

      state.page += 1;
    } catch (e) {
      // Error handling without console output
      if (!state.list.hasChildNodes()) state.list.textContent = "Не удалось загрузить отели.";
    } finally {
      state.loading = false;
      state.wrap.classList.remove("is-loading");
      if (state.btn) state.btn.disabled = false;
    }
  }

  const wrap = document.querySelector(".resort-hotels");
  if (!wrap) return;

  const termId = parseInt(wrap.getAttribute("data-term-id") || "0", 10);
  if (!termId) return;

  const state = {
    wrap,
    termId,
    perPage: 12,
    page: 1,
    orderby: "title",
    order: "ASC",
    hasMore: false,
    loading: false,
    list: qs(wrap, ".resort-hotels__list"),
    btn: qs(wrap, ".resort-hotels__more"),
  };

  if (!state.list) return;

  loadHotels(state);

  if (state.btn) {
    state.btn.addEventListener("click", () => loadHotels(state));
  }
};
