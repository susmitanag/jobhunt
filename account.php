<?php
require __DIR__ . '/config.php';
require_login('seeker');

$user = current_user();
$pageTitle = 'My applications';
$currentPage = 'account';

$stmt = $pdo->prepare(
    'SELECT a.status, a.applied_at, j.id AS job_id, j.title, c.name AS company_name, c.slug AS company_slug, c.color, c.shape
     FROM applications a
     JOIN jobs j ON j.id = a.job_id
     JOIN companies c ON c.id = j.company_id
     WHERE a.user_id = ? ORDER BY a.applied_at DESC'
);
$stmt->execute([$user['id']]);
$apps = $stmt->fetchAll();
$savedCount = count(saved_ids());

require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="wrap">
        <h1>Hello, <?= e(explode(' ', $user['name'])[0]) ?></h1>
        <p><?= e($user['email']) ?> / <a href="saved.php"><?= $savedCount ?> saved <?= $savedCount === 1 ? 'job' : 'jobs' ?></a></p>
    </div>
</section>
<div class="wrap" style="padding-top:36px;padding-bottom:72px">
    <h2>My applications</h2>
    <?php if (!$apps): ?>
        <div class="empty-state">
            <h3>You have not applied to any jobs yet</h3>
            <p>When you apply, you can follow the status of each application here.</p>
            <a href="jobs.php" class="btn btn-primary">Browse jobs</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Job</th><th>Company</th><th>Applied</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($apps as $a): ?>
                    <tr>
                        <td><a href="job.php?id=<?= (int) $a['job_id'] ?>"><?= e($a['title']) ?></a></td>
                        <td><a href="company.php?slug=<?= e($a['company_slug']) ?>"><?= e($a['company_name']) ?></a></td>
                        <td><?= e(date('j M Y', strtotime($a['applied_at']))) ?></td>
                        <td><span class="status-pill status-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
