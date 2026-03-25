<?php
require_once 'config.php';

session_destroy();
header('Location: admin_login.php');
exit;
?>