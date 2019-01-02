<?php
$secure = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
setcookie( '_wpnonce', wp_create_nonce( 'wp_rest' ), time() + 2 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure );

echo file_get_contents( get_stylesheet_directory() . '/app/dist/index.html' );