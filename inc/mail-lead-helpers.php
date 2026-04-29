<?php

/**
 * Общие фрагменты для писем с лид-форм (подпись, заголовки).
 */

if (!function_exists('bsi_mail_lead_signature_html')) {
    /**
     * HTML-блок подписи в конце письма (после контента, до служебного футера с датой/IP).
     */
    function bsi_mail_lead_signature_html(): string
    {
        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $url = esc_url(home_url('/'));
        $host = parse_url(home_url(), PHP_URL_HOST);
        $host_h = $host !== null && $host !== '' ? esc_html($host) : '';

        return '<div class="signature" style="margin-top:28px;padding-top:16px;border-top:1px solid #ddd;font-size:13px;color:#444;">'
            . '<p style="margin:0 0 10px;line-height:1.5;"><strong>С уважением,<br>команда ' . esc_html($site) . '</strong></p>'
            . '<p style="margin:0;font-size:12px;color:#666;line-height:1.45;">Письмо сформировано автоматически с сайта '
            . '<a href="' . $url . '" style="color:#dc2626;">' . $host_h . '</a>.</p>'
            . '</div>';
    }
}

if (!function_exists('bsi_mail_lead_headers')) {
    /**
     * @param string $reply_to E-mail для ответа (например клиента); пусто — заголовок не ставится.
     * @return string[]
     */
    function bsi_mail_lead_headers(string $reply_to = ''): array
    {
        $site_name = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $from = sanitize_email((string) get_bloginfo('admin_email'));
        if ($from === '') {
            $host = parse_url(home_url(), PHP_URL_HOST);
            $host = $host !== null ? preg_replace('/^www\./', '', $host) : '';
            if ($host !== '') {
                $from = 'noreply@' . $host;
            }
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $from . '>',
        ];

        $reply_to = trim($reply_to);
        if ($reply_to !== '' && is_email($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        return $headers;
    }
}
