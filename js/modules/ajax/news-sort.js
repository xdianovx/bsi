export function initNewsFilter() {
  var buttons = document.querySelectorAll(".js-news-filter-btn");
  var list = document.querySelector(".js-news-list");
  var paginationContainer = document.querySelector(".js-news-pagination");
  var currentTerm = "";
  var currentPage = 1;

  function setLoading(state) {
    if (state) {
      list.classList.add("is-loading");
    } else {
      list.classList.remove("is-loading");
    }
  }

  function loadNews(term, page) {
    setLoading(true);

    var formData = new FormData();
    formData.append("action", "bsi_filter_news");
    formData.append("term", term);
    formData.append("paged", page);

    fetch(ajax.url, {
      method: "POST",
      body: formData,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.success && data.data) {
          list.innerHTML = data.data.html || "";

          if (paginationContainer) {
            if (data.data.pagination) {
              paginationContainer.innerHTML = data.data.pagination;
              paginationContainer.style.display = "";
              initPaginationHandlers();
            } else {
              paginationContainer.innerHTML = "";
              paginationContainer.style.display = "none";
            }
          }
        }
      })
      .catch(function (error) {
        // Error handling without console output
      })
      .finally(function () {
        setLoading(false);
      });
  }

  function initPaginationHandlers() {
    if (!paginationContainer) return;

    var paginationLinks = paginationContainer.querySelectorAll("a");
    paginationLinks.forEach(function (link) {
      // Удаляем старые обработчики, если они есть
      var newLink = link.cloneNode(true);
      link.parentNode.replaceChild(newLink, link);

      newLink.addEventListener("click", function (e) {
        e.preventDefault();
        var href = this.getAttribute("href");
        if (!href) return;

        var page = 1;
        // Проверяем формат ?paged=2
        var pageMatch = href.match(/[?&]paged=(\d+)/);
        if (pageMatch) {
          page = parseInt(pageMatch[1], 12);
        } else {
          // Проверяем формат /page/2/
          var pageMatch2 = href.match(/\/page\/(\d+)\//);
          if (pageMatch2) {
            page = parseInt(pageMatch2[1], 12);
          }
        }

        if (page > 0) {
          currentPage = page;
          loadNews(currentTerm, page);

          // Прокрутка к началу списка новостей
          if (list) {
            list.scrollIntoView({ behavior: "smooth", block: "start" });
          }
        }
      });
    });
  }

  function handleFilterClick(event) {
    event.preventDefault();

    var btn = event.currentTarget;
    var term = btn.getAttribute("data-term") || "";

    buttons.forEach(function (b) {
      b.classList.remove("is-active");
    });
    btn.classList.add("is-active");

    currentTerm = term;
    currentPage = 1;
    loadNews(term, 1);
  }

  buttons.forEach(function (btn) {
    btn.addEventListener("click", handleFilterClick);
  });

  // Инициализируем обработчики пагинации при загрузке страницы
  if (paginationContainer) {
    initPaginationHandlers();
  }
}
