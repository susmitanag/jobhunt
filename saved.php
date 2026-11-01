<?php
require __DIR__ . '/config.php';

$pageTitle = 'Saved jobs';
$currentPage = 'saved';

$ids = saved_ids();
$jobs = [];
if ($ids) {
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(JOB_SELECT . " WHERE j.id IN ($marks) ORDER BY j.posted_at DESC");
    $stmt->execute($ids);
    $jobs = $stmt->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="wrap">
        <h1>Saved jobs</h1>
        <p>Jobs you bookmarked. They are kept for this browser session.</p>
    </div>
</section>

<div class="wrap" style="padding-top:36px;padding-bottom:72px" data-saved-page>
    <?php if (!$jobs): ?>
        <div class="empty-state">
            <h3>Nothing saved yet</h3>
            <p>Tap the bookmark on any job and it will show up here.</p>
            <a href="jobs.php" class="btn btn-primary">Browse jobs</a>
        </div>
    <?php else: ?>
        <div class="job-list">
            <?php foreach ($jobs as $job): ?>
                <?php require __DIR__ . '/includes/job_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
