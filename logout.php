<?php
require __DIR__ . '/config.php';
unset($_SESSION['user_id'], $_SESSION['saved']);
session_regenerate_id(true);
header('Location: index.php');
exit;
