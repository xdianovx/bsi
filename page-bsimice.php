<?php
get_header('mice');
?>

<main class="bsimice-page">
    <div class="hero-wrapper">
        <?php if (function_exists('yoast_breadcrumb')): ?>
        <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
        <?php endif; ?>

        <section class="hero">
            <div class="container">
                <div class="hero__top">
                    <h1 class="hero__title">организация MICE мероприятий</h1>
                    <p class="hero__subtitle">Более 35 лет организуем конференции, инсентив-туры, тимбилдинг
                        и деловые мероприятия для корпоративных клиентов по России и за рубежом.</p>

                    <div class="hero__buttons">
                        <button class="hero__btn hero__btn--primary">Обсудить</button>
                        <a href="/proekty" class="hero__btn hero__btn--secondary">Посмотреть проекты</a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section>
        <div class="container">
            <div class="plates">
                <div class="plate">
                    <span class="plate__text">
                        Единая онлайн-система управления поездками, персональный менеджер и полная документальная
                        отчётность — всё, чтобы деловые поездки не отвлекали вас от бизнеса.
                    </span>
                </div>
                <div class="plate">
                    <span class="plate__text">
                        BSI Group — надёжный партнёр для корпоративного
                        travel-менеджмента. Мы работаем с компаниями любого масштаба: от небольшого бизнеса до
                        крупных корпораций.
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="mice-about">
        <div class="container">
            <h2 class="mice-about__heading">Что такое MICE?</h2>
            <p class="mice-about__subtitle">MICE — международная аббревиатура для индустрии корпоративных мероприятий.
                Расшифровывается как Meetings (встречи), Incentives (инсентив-туры), Conferences (конференции)
                и Events (мероприятия).</p>

            <div class="mice-about__items">
                <div class="mice-about__item">
                    <p class="mice-about__item-title"><span>M</span>eetings</p>
                    <span class="mice-about__item-desc">
                        Деловые встречи, переговоры, рабочие сессии. Организуем переговорные комнаты, технику
                        и кейтеринг.
                    </span>
                </div>
                <div class="mice-about__item">
                    <p class="mice-about__item-title"><span>I</span>ncentives</p>
                    <span class="mice-about__item-desc">
                        Мотивационные туры и программы вознаграждения для сотрудников и партнёров. Россия и зарубежные
                        направления.
                    </span>
                </div>
                <div class="mice-about__item">
                    <p class="mice-about__item-title"><span>C</span>onferences</p>
                    <span class="mice-about__item-desc">
                        Конференции, форумы, съезды. Подбор площадок, техническое обеспечение, регистрация участников.
                    </span>
                </div>
                <div class="mice-about__item">
                    <p class="mice-about__item-title"><span>E</span>vents</p>
                    <span class="mice-about__item-desc">
                        Корпоративные праздники, тимбилдинг, событийный маркетинг, презентации и церемонии награждения.
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="mice-proove">
        <div class="container">
            <div class="mice-proove__top">
                <div class="mice-proove__content">
                    <h2>Более 35 лет сопровождаем
                        вас в деловых поездках</h2>
                    <div class="mice-proove__body">
                        <p>
                            BSI Group — один из старейших игроков рынка корпоративных мероприятий России. С 1990 года мы
                            организуем MICE-проекты любой сложности: от переговорной на 10 человек до международного
                            форума с тысячей участников.
                        </p>
                        <p>
                            Наша команда — профессионалы, влюблённые в своё дело. Мы не перекладываем
                            задачи на субподрядчиков без контроля — каждый проект ведёт персональный менеджер от первого
                            звонка до финальной отчётности.
                        </p>
                    </div>
                </div>
                <div class="mice-proove__media">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/mice/5.png" alt="">
                </div>
            </div>
            <div class="mice-proove__stats">
                <div class="mice-proove__stat">
                    <p class="mice-proove__stat-title">35 лет</p>
                    <p class="mice-proove__stat-text">на рынке</p>
                    <span class="mice-proove__stat-bg">35 лет</span>
                </div>
                <div class="mice-proove__stat">
                    <p class="mice-proove__stat-title">35 лет</p>
                    <p class="mice-proove__stat-text">на рынке</p>
                    <span class="mice-proove__stat-bg">500 +</span>
                </div>
                <div class="mice-proove__stat">
                    <p class="mice-proove__stat-title">35 лет</p>
                    <p class="mice-proove__stat-text">на рынке</p>
                    <span class="mice-proove__stat-bg">35 лет</span>
                </div>
                <div class="mice-proove__stat">
                    <p class="mice-proove__stat-title">35 лет</p>
                    <p class="mice-proove__stat-text">на рынке</p>
                    <span class="mice-proove__stat-bg">35 лет</span>
                </div>
            </div>
        </div>
    </section>

    <section class="services">
        <div class="container">
            <h2 class="services__heading">
                наши услуги
            </h2>
            <div class="services__list">
                <div class="services__item">
                    <div class="services__item-title">
                        <div class="services__item-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11 7.601L5.006 15.791C4.86496 15.9837 4.79751 16.2204 4.81585 16.4585C4.83419 16.6966 4.93711 16.9202 5.106 17.089L5.923 17.907C6.09435 18.0782 6.32199 18.1813 6.56366 18.1973C6.80532 18.2133 7.04458 18.1411 7.237 17.994L15.09 12M16.5 21.174C15.5 20.5 14.372 20 13 20C10.942 20 9.072 22.356 7 22C4.928 21.644 4.225 18.631 5.5 17.5M21 7C21 9.76142 18.7614 12 16 12C13.2386 12 11 9.76142 11 7C11 4.23858 13.2386 2 16 2C18.7614 2 21 4.23858 21 7Z"
                                    stroke="#EF0101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        Конференции и форумы
                    </div>
                    <div class="services__item-desc">
                        Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.
                    </div>
                </div>
                <div class="services__item">
                    <div class="services__item-title">
                        <div class="services__item-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11 7.601L5.006 15.791C4.86496 15.9837 4.79751 16.2204 4.81585 16.4585C4.83419 16.6966 4.93711 16.9202 5.106 17.089L5.923 17.907C6.09435 18.0782 6.32199 18.1813 6.56366 18.1973C6.80532 18.2133 7.04458 18.1411 7.237 17.994L15.09 12M16.5 21.174C15.5 20.5 14.372 20 13 20C10.942 20 9.072 22.356 7 22C4.928 21.644 4.225 18.631 5.5 17.5M21 7C21 9.76142 18.7614 12 16 12C13.2386 12 11 9.76142 11 7C11 4.23858 13.2386 2 16 2C18.7614 2 21 4.23858 21 7Z"
                                    stroke="#EF0101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        Конференции и форумы
                    </div>
                    <div class="services__item-desc">
                        Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.
                    </div>
                </div>
                <div class="services__item">
                    <div class="services__item-title">
                        <div class="services__item-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11 7.601L5.006 15.791C4.86496 15.9837 4.79751 16.2204 4.81585 16.4585C4.83419 16.6966 4.93711 16.9202 5.106 17.089L5.923 17.907C6.09435 18.0782 6.32199 18.1813 6.56366 18.1973C6.80532 18.2133 7.04458 18.1411 7.237 17.994L15.09 12M16.5 21.174C15.5 20.5 14.372 20 13 20C10.942 20 9.072 22.356 7 22C4.928 21.644 4.225 18.631 5.5 17.5M21 7C21 9.76142 18.7614 12 16 12C13.2386 12 11 9.76142 11 7C11 4.23858 13.2386 2 16 2C18.7614 2 21 4.23858 21 7Z"
                                    stroke="#EF0101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        Конференции и форумы
                    </div>
                    <div class="services__item-desc">
                        Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.
                    </div>
                </div>
                <div class="services__item">
                    <div class="services__item-title">
                        <div class="services__item-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11 7.601L5.006 15.791C4.86496 15.9837 4.79751 16.2204 4.81585 16.4585C4.83419 16.6966 4.93711 16.9202 5.106 17.089L5.923 17.907C6.09435 18.0782 6.32199 18.1813 6.56366 18.1973C6.80532 18.2133 7.04458 18.1411 7.237 17.994L15.09 12M16.5 21.174C15.5 20.5 14.372 20 13 20C10.942 20 9.072 22.356 7 22C4.928 21.644 4.225 18.631 5.5 17.5M21 7C21 9.76142 18.7614 12 16 12C13.2386 12 11 9.76142 11 7C11 4.23858 13.2386 2 16 2C18.7614 2 21 4.23858 21 7Z"
                                    stroke="#EF0101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        Конференции и форумы
                    </div>
                    <div class="services__item-desc">
                        Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.
                    </div>
                </div>
                <div class="services__item">
                    <div class="services__item-title">
                        <div class="services__item-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11 7.601L5.006 15.791C4.86496 15.9837 4.79751 16.2204 4.81585 16.4585C4.83419 16.6966 4.93711 16.9202 5.106 17.089L5.923 17.907C6.09435 18.0782 6.32199 18.1813 6.56366 18.1973C6.80532 18.2133 7.04458 18.1411 7.237 17.994L15.09 12M16.5 21.174C15.5 20.5 14.372 20 13 20C10.942 20 9.072 22.356 7 22C4.928 21.644 4.225 18.631 5.5 17.5M21 7C21 9.76142 18.7614 12 16 12C13.2386 12 11 9.76142 11 7C11 4.23858 13.2386 2 16 2C18.7614 2 21 4.23858 21 7Z"
                                    stroke="#EF0101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        Конференции и форумы
                    </div>
                    <div class="services__item-desc">
                        Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.
                    </div>
                </div>
                <div class="services__item">
                    <div class="services__item-title">
                        <div class="services__item-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11 7.601L5.006 15.791C4.86496 15.9837 4.79751 16.2204 4.81585 16.4585C4.83419 16.6966 4.93711 16.9202 5.106 17.089L5.923 17.907C6.09435 18.0782 6.32199 18.1813 6.56366 18.1973C6.80532 18.2133 7.04458 18.1411 7.237 17.994L15.09 12M16.5 21.174C15.5 20.5 14.372 20 13 20C10.942 20 9.072 22.356 7 22C4.928 21.644 4.225 18.631 5.5 17.5M21 7C21 9.76142 18.7614 12 16 12C13.2386 12 11 9.76142 11 7C11 4.23858 13.2386 2 16 2C18.7614 2 21 4.23858 21 7Z"
                                    stroke="#EF0101" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        Конференции и форумы
                    </div>
                    <div class="services__item-desc">
                        Организация под ключ: площадка, техника, регистрация, программа. До нескольких тысяч участников.
                    </div>
                </div>
            </div>
            <div class="services__guarantees">
                <div class="services__guarantee">
                    <div class="services__guarantee-title">
                        Проверенные поставщики
                    </div>
                    <div class="services__guarantee-box">
                        Единая онлайн-система управления поездками, персональный менеджер и полная документальная
                        отчётность — всё, чтобы деловые поездки не отвлекали вас от бизнеса.
                    </div>
                </div>
                <div class="services__guarantee">
                    <div class="services__guarantee-title">
                        Полное сопровождение
                    </div>
                    <div class="services__guarantee-box">
                        Единая онлайн-система управления поездками, персональный менеджер и полная документальная
                        отчётность — всё, чтобы деловые поездки не отвлекали вас от бизнеса.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mice-reviews">
        <div class="container">
            <div class="section_head">
                <h2 class="reviews__heading">
                    Нас Благодарят
                </h2>
                <div class="slider-arrow-wrap new-reviews-slider__arrows-wrap">
                    <div class="slider-arrow slider-arrow-prev projects-section-arrow-prev" tabindex="0" role="button"
                        aria-label="Previous slide"></div>
                    <div class="slider-arrow slider-arrow-next projects-section-arrow-next" tabindex="0" role="button"
                        aria-label="Next slide"></div>
                </div>
            </div>
        </div>
        <div class="reviews-swiper-outer">
            <div class="swiper new-reviews-swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="slide_box">
                            <p class="review_text">
                                Разнообразный и богатый опыт говорит нам, что выбранный нами инновационный путь в
                                значительной степени обусловливает важность своевременного выполнения сверхзадачи.
                                Равным
                                образом, курс на социально-ориентированный национальный проект позволяет выполнить
                                важные
                                задания по разработке благоприятных перспектив.
                            </p>

                            <div class="review_user">
                                <p class="review_user-name">
                                    Иванов Иван Иванович
                                </p>
                                <span class="review_user-title">
                                    Генеральный директор “Суперкомпашка”
                                </span>
                            </div>

                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="slide_box">
                            <p class="review_text">
                                Разнообразный и богатый опыт говорит нам, что выбранный нами инновационный путь в
                                значительной степени обусловливает важность своевременного выполнения сверхзадачи.
                                Равным
                                образом, курс на социально-ориентированный национальный проект позволяет выполнить
                                важные
                                задания по разработке благоприятных перспектив.
                            </p>

                            <div class="review_user">
                                <p class="review_user-name">
                                    Иванов Иван Иванович
                                </p>
                                <span class="review_user-title">
                                    Генеральный директор “Суперкомпашка”
                                </span>
                            </div>

                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="slide_box">
                            <p class="review_text">
                                Разнообразный и богатый опыт говорит нам, что выбранный нами инновационный путь в
                                значительной степени обусловливает важность своевременного выполнения сверхзадачи.
                                Равным
                                образом, курс на социально-ориентированный национальный проект позволяет выполнить
                                важные
                                задания по разработке благоприятных перспектив.
                            </p>

                            <div class="review_user">
                                <p class="review_user-name">
                                    Иванов Иван Иванович
                                </p>
                                <span class="review_user-title">
                                    Генеральный директор “Суперкомпашка”
                                </span>
                            </div>

                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="slide_box">
                            <p class="review_text">
                                Разнообразный и богатый опыт говорит нам, что выбранный нами инновационный путь в
                                значительной степени обусловливает важность своевременного выполнения сверхзадачи.
                                Равным
                                образом, курс на социально-ориентированный национальный проект позволяет выполнить
                                важные
                                задания по разработке благоприятных перспектив.
                            </p>

                            <div class="review_user">
                                <p class="review_user-name">
                                    Иванов Иван Иванович
                                </p>
                                <span class="review_user-title">
                                    Генеральный директор “Суперкомпашка”
                                </span>
                            </div>

                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="slide_box">
                            <p class="review_text">
                                Разнообразный и богатый опыт говорит нам, что выбранный нами инновационный путь в
                                значительной степени обусловливает важность своевременного выполнения сверхзадачи.
                                Равным
                                образом, курс на социально-ориентированный национальный проект позволяет выполнить
                                важные
                                задания по разработке благоприятных перспектив.
                            </p>

                            <div class="review_user">
                                <p class="review_user-name">
                                    Иванов Иван Иванович
                                </p>
                                <span class="review_user-title">
                                    Генеральный директор “Суперкомпашка”
                                </span>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>



</main>

<?php get_footer(); ?>