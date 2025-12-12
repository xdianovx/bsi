<div class="country-page__top-info">

    <?php if (get_field('stolicza', get_the_ID())): ?>
        <div class="country-attr-item">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-landmark-icon lucide-landmark">
                <path d="M10 18v-7" />
                <path
                      d="M11.12 2.198a2 2 0 0 1 1.76.006l7.866 3.847c.476.233.31.949-.22.949H3.474c-.53 0-.695-.716-.22-.949z" />
                <path d="M14 18v-7" />
                <path d="M18 18v-7" />
                <path d="M3 22h18" />
                <path d="M6 18v-7" />
            </svg>

            <p> <?= get_field('stolicza', get_the_ID()) ?></p>
        </div>
    <?php endif ?>

    <?php if (get_field('chislennost', get_the_ID())): ?>
        <div class="country-attr-item">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-users-round-icon lucide-users-round">
                <path d="M18 21a8 8 0 0 0-16 0" />
                <circle cx="10"
                        cy="8"
                        r="5" />
                <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3" />
            </svg>

            <p><?= get_field('chislennost', get_the_ID()) ?></p>
        </div>

    <?php endif ?>

    <?php if (get_field('yazyk', get_the_ID())): ?>
        <div class="country-attr-item">
            <svg xmlns="http://www.w3.org/2000/svg"
                 <svg
                 xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-languages-icon lucide-languages">
                <path d="m5 8 6 6" />
                <path d="m4 14 6-6 2-3" />
                <path d="M2 5h12" />
                <path d="M7 2h1" />
                <path d="m22 22-5-10-5 10" />
                <path d="M14 18h6" />
            </svg>

            <p><?= get_field('yazyk', get_the_ID()) ?></p>
        </div>
    <?php endif ?>

    <?php if (get_field('chasovoj_poyas', get_the_ID())): ?>
        <div class="country-attr-item">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-history-icon lucide-history">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                <path d="M3 3v5h5" />
                <path d="M12 7v5l4 2" />
            </svg>
            <p><?= get_field('chasovoj_poyas', get_the_ID()) ?></p>
        </div>
    <?php endif ?>

    <?php if (get_field('valyuta', get_the_ID())): ?>
        <div class="country-attr-item">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-wallet-icon lucide-wallet">
                <path
                      d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1" />
                <path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4" />
            </svg>

            <p><?= get_field('valyuta', get_the_ID()) ?></p>
        </div>
    </div>
<?php endif ?>