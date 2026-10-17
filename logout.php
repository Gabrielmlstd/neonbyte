<?php
require_once 'includes/auth.php';
logout_user();
header('Location: '.SITE_URL.'/index.php');
exit;
