<?php
/** Logout: POST only (with CSRF token) so it cannot be triggered by a simple link or image. */
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_verify();
logout_user();
session_start();                       // fresh empty session just to carry the message
flash('success', 'You have been logged out.');
redirect('login.php');
