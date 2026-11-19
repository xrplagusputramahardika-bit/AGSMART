<?php
/**
 * Customer Logout
 */
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', time()-3600, '/');
}
session_destroy();
header('Location: pelanggan/login.php');
exit;