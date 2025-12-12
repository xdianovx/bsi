export function initNewsFilter() {
  var buttons = document.querySelectorAll(".js-news-filter-btn");
  var list = document.querySelector(".js-news-list");

  function setLoading(state) {
    if (state) {
      list.classList.add("is-loading");
    } else {
      list.classList.remove("is-loading");
    }
  }

  function handleClick(event) {
    event.preventDefault();

    var btn = event.currentTarget;
    var term = btn.getAttribute("data-term") || "";

    buttons.forEach(function (b) {
      b.classList.remove("is-active");
    });
    btn.classList.add("is-active");

    setLoading(true);

    var formData = new FormData();
    formData.append("action", "bsi_filter_news");
    formData.append("term", term);

    fetch(ajax.url, {
      method: "POST",
      body: formData,
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        list.innerHTML = html;
      })
      .catch(function (error) {
        console.error(error);
      })
      .finally(function () {
        setLoading(false);
      });
  }

  buttons.forEach(function (btn) {
    btn.addEventListener("click", handleClick);
  });
}
