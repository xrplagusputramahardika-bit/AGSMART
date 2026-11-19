<?php
require_once __DIR__ . '/../config/helper.php';
requireCustomer();
header('Location: ../customer_checkout.php');
exit;
