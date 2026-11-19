<?php
require_once __DIR__ . '/../config/helper.php';
$_SESSION = [];
session_destroy();
header('Location: ../welcome.php');
exit;
