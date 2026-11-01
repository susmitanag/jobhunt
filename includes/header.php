<?php
$pageTitle = $pageTitle ?? '';
$currentPage = $currentPage ?? '';
$savedCount = count(saved_ids());
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle !== '' ? e($pageTitle) . ' | Jobhunt' : 'Jobhunt | Find work that fits' ?></title>
<meta name="description" content="Jobhunt is a job board for tech, design, healthcare, education and more. Search roles, save the ones you like and apply in minutes.">
<link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" x-data="{ open: false }">
    <div class="wrap header-inner">
        <a href="index.php" class="brand" aria-label="Jobhunt home">
            <img src="assets/img/mark.svg" width="34" height="34" alt="">
            <span class="brand-name">Jobhunt</span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Open menu" :aria-expanded="open.toString()" aria-controls="site-nav" @click="open = !open">
            <?= icon('menu', 22) ?>
        </button>
        <nav class="site-nav" id="site-nav" aria-label="Main" :class="{ 'is-open': open }">
            <a href="jobs.php" class="<?= $currentPage === 'jobs' ? 'is-current' : '' ?>">Find jobs</a>
            <a href="companies.php" class="<?= $currentPage === 'companies' ? 'is-current' : '' ?>">Companies</a>
            <?php if (!is_employer()): ?>
            <a href="saved.php" class="<?= $currentPage === 'saved' ? 'is-current' : '' ?>">
                Saved <span class="count-pill" id="saved-count"><?= $savedCount ?></span>
            </a>
            <?php endif; ?>
            <?php if (is_seeker()): ?>
                <a href="account.php" class="<?= $currentPage === 'account' ? 'is-current' : '' ?>">My applications</a>
                <a href="logout.php">Log out</a>
            <?php elseif (is_employer()): ?>
                <a href="employer.php" class="<?= $currentPage === 'employer' ? 'is-current' : '' ?>">Dashboard</a>
                <a href="logout.php">Log out</a>
                <a href="post_job.php" class="btn btn-accent btn-sm">Post a job</a>
            <?php else: ?>
                <a href="login.php" class="<?= $currentPage === 'login' ? 'is-current' : '' ?>">Log in</a>
                <a href="register.php" class="btn btn-accent btn-sm">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main id="main">
