<?php
/**
 * Router for PHP's built-in development server. It blocks direct URL access to
 * private folders (Apache uses the .htaccess files instead).
 * Run from the project root:   php -S localhost:8000 -t . php-module/router.php
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#/php-module/(uploads|config|includes|bin)(/|$)#i', $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;          // request handled
}
return false;             // let the built-in server serve the file normally
