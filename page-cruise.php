<?php

/**
 * Template Name: Круизы
 */
get_header();
?>

<main class="cruise-page">
    <?php if (function_exists('yoast_breadcrumb')): ?>
        <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
    <?php endif; ?>

    <section>
        <div class="container">
            <?php the_title('<h1 class="h1 bonus-page__title">', '</h1>'); ?>
        </div>
    </section>


    <div class="container">
        <div class="content">
            <h1><span style="color: #9d0a0f; font-size: large; ">
                    <?= $title ?>
                </span></h1>
            <div class="infoflotWidget"
                data-id="YTo0OntzOjI6IklEIjtzOjQ6IjM3MTIiO3M6NDoiVVNFUiI7czoyODoiWlM1cmJHbHRiM1poUUdKemFXZHliM1Z3TG5KMSI7czo2OiJSQU5ET00iO3M6ODoid2JkMnRhaGgiO3M6MTU6IklORk9GTE9ULUFQSUtFWSI7czo0MDoiMWNjM2Q1ZjkyY2Y1NDZlOWMyNzUyYjE2NjU3ZjJmM2ZjMzhlZmJlNCI7fQ=="
                data-index="1"></div>
            <script async>(function (d, w) {
                    var h = d.getElementsByTagName("script")[0];
                    s = d.createElement("script");
                    s.src = "https://bitrix.infoflot.com/local/templates/infoflot/frontend/js/infoflotIframe.js";
                    s.async = !0;
                    s.onload = function () {
                        w.createInfoflotWidget("https://bitrix.infoflot.com/rest/api/search.filter/", {
                            key: "YTo0OntzOjI6IklEIjtzOjQ6IjM3MTIiO3M6NDoiVVNFUiI7czoyODoiWlM1cmJHbHRiM1poUUdKemFXZHliM1Z3TG5KMSI7czo2OiJSQU5ET00iO3M6ODoid2JkMnRhaGgiO3M6MTU6IklORk9GTE9ULUFQSUtFWSI7czo0MDoiMWNjM2Q1ZjkyY2Y1NDZlOWMyNzUyYjE2NjU3ZjJmM2ZjMzhlZmJlNCI7fQ==",
                            referer: encodeURIComponent(location.href)
                        })
                    };
                    h.parentNode.insertBefore(s, h);
                })(document, window);
            </script>
        </div>
    </div>

</main>


<?php get_footer(); ?>