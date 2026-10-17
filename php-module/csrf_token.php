<?php
/**
 * Returns the session's CSRF token as JSON. The static registration page
 * (frontend/index.html) calls this with fetch() and copies the token into its
 * hidden field. Same-origin only: no CORS headers are sent, so other websites
 * cannot read the token.
 */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['token' => csrf_token()]);
