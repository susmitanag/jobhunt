<?php
require __DIR__ . '/config.php';

$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare('SELECT * FROM companies WHERE slug = ?');
$stmt->execute([$slug]);
$company = $stmt->fetch();

$currentPage = 'companies';
if (!$company) {
    http_response_code(404);
    $pageTitle = 'Company not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap narrow" style="padding:64px 0"><div class="empty-state"><h3>Company not found</h3><a class="btn btn-primary" href="companies.php">All companies</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$jobsStmt = $pdo->prepare(JOB_SELECT . ' WHERE j.company_id = ? ORDER BY j.posted_at DESC');
$jobsStmt->execute([$company['id']]);
$jobs = $jobsStmt->fetchAll();

$pageTitle = $company['name'];
require __DIR__ . '/includes/header.php';
?>
<section class="job-hero page-head">
    <div class="wrap">
        <p class="breadcrumb"><a href="companies.php">Companies</a> / <?= e($company['name']) ?></p>
        <div class="job-hero-main">
            <?= logo_html($company['name'], $company['slug'], $company['color'], (int) $company['shape'], 80) ?>
            <div>
                <h1><?= e($company['name']) ?></h1>
                <div class="tag-row">
                    <span class="tag"><?= e($company['industry']) ?></span>
                    <span class="tag"><?= e($company['location']) ?></span>
                    <span class="tag"><?= e($company['size_label']) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="wrap detail-layout">
    <div>
        <h2><?= count($jobs) ?> open <?= count($jobs) === 1 ? 'role' : 'roles' ?></h2>
        <?php if (!$jobs): ?>
            <div class="empty-state"><p>No open roles right now.</p></div>
        <?php else: ?>
            <div class="job-list">
                <?php foreach ($jobs as $job): ?>
                    <?php require __DIR__ . '/includes/job_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <aside class="sidebar">
        <div class="panel">
            <h2>About</h2>
            <p><?= e($company['about']) ?></p>
            <?php if (!empty($company['website'])): ?>
                <p class="muted"><?= icon('globe', 16) ?> <?= e($company['website']) ?></p>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
