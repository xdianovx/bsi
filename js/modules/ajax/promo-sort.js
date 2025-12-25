export const promoPageAjax = () => {
  if (typeof ajax === "undefined" || !ajax.url) {
    return;
  }

  const buttons = document.querySelectorAll(".js-promo-filter-btn");
  const list = document.querySelector(".js-promo-list");

  if (!buttons.length || !list) {
    return;
  }

  const setLoading = (state) => {
    if (state) {
      list.classList.add("is-loading");
    } else {
      list.classList.remove("is-loading");
    }
  };

  const handleClick = (event) => {
    event.preventDefault();

    const btn = event.currentTarget;
    const country = btn.getAttribute("data-country") || "";

    buttons.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");

    setLoading(true);

    const formData = new FormData();
    formData.append("action", "bsi_filter_promos");
    formData.append("country", country);

    fetch(ajax.url, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((html) => {
        list.innerHTML = html;
      })
      .catch((error) => {
        // Error handling without console output
      })
      .finally(() => {
        setLoading(false);
      });
  };

  buttons.forEach((btn) => {
    btn.addEventListener("click", handleClick);
  });
};
