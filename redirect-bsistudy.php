<?php
/**
 * Template Name: BSI Study Redirect
 * 
 * Промежуточная страница для редиректа на bsistudy.ru с правильным referrer
 */

// Получаем URL из параметра
$target_url = isset($_GET['url']) ? urldecode($_GET['url']) : 'https://www.bsistudy.ru/';

// Валидация URL
$parsed = parse_url($target_url);
if (empty($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
    wp_die('Invalid URL format');
}

// Проверяем, что это bsistudy.ru
$allowed_hosts = ['bsistudy.ru', 'www.bsistudy.ru'];
$target_host = !empty($parsed['host']) ? str_replace('www.', '', $parsed['host']) : '';
if (!in_array($target_host, ['bsistudy.ru'])) {
    wp_die('This redirect page is only for bsistudy.ru');
}

// Экранируем URL для безопасности
$safe_url = esc_url_raw($target_url);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting...</title>
    <script>
        // Используем window.open с пустым окном, затем устанавливаем location
        // Это позволяет установить referrer от текущего домена
        (function() {
            var url = <?php echo json_encode($safe_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            
            // Открываем новое окно с текущим доменом как referrer
            var newWindow = window.open('', '_blank', 'noopener');
            if (newWindow) {
                // Устанавливаем location после небольшой задержки, чтобы referrer был установлен
                setTimeout(function() {
                    newWindow.location.href = url;
                    // Закрываем текущее окно
                    window.close();
                }, 50);
            } else {
                // Fallback - прямой редирект
                window.location.href = url;
            }
        })();
    </script>
</head>
<body>
    <p>Redirecting...</p>
</body>
</html>
