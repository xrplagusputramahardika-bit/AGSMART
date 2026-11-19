<?php
require_once __DIR__ . '/../config/helper.php';
requireCustomer();
$query = isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : '';
header('Location: ../customer_catalog.php' . $query);
exit;
