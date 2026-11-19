<?php
require_once __DIR__ . '/../config/helper.php';
requireCashier();
header('Location: ../pos.php');
exit;
