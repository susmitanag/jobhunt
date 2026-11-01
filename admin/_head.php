<?php
// Shared admin header. Expects $adminTitle and $adminPage.
require_once __DIR__ . '/../config.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($adminTitle ?? 'Admin') ?> | Jobhunt admin</title>
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="site-header admin-bar">
    <div class="wrap header-inner">
        <a href="index.php" class="brand"><img src="../assets/img/mark.svg" width="34" height="34" alt=""><span class="brand-name">Jobhunt admin</span></a>
        <nav class="site-nav is-open" style="display:flex;position:static;flex-direction:row;background:none;box-shadow:none;padding:0">
            <a href="index.php">Jobs</a>
            <a href="applications.php">Applications</a>
            <a href="../index.php">View site</a>
            <a href="logout.php" class="btn btn-ghost btn-sm" style="color:#fff;border-color:rgba(255,255,255,.4)">Log out</a>
        </nav>
    </div>
</header>
<main class="wrap" style="padding-top:36px;padding-bottom:72px">
