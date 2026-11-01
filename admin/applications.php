<?php
$adminTitle = 'Applications';
require __DIR__ . '/_head.php';

$statuses = ['New', 'Reviewed', 'Shortlisted', 'Rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId = (int) ($_POST['app_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, $statuses, true)) {
        $pdo->prepare('UPDATE applications SET status = ? WHERE id = ?')->execute([$status, $appId]);
    }
    header('Location: applications.php' . (!empty($_GET['job']) ? '?job=' . (int) $_GET['job'] : ''));
    exit;
}

$jobFilter = (int) ($_GET['job'] ?? 0);
$sql = 'SELECT a.*, j.title, c.name AS company_name FROM applications a
        JOIN jobs j ON j.id = a.job_id JOIN companies c ON c.id = j.company_id';
$params = [];
if ($jobFilter) { $sql .= ' WHERE a.job_id = ?'; $params[] = $jobFilter; }
$sql .= ' ORDER BY a.applied_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apps = $stmt->fetchAll();
?>
<h1 style="font-size:2rem">Applications</h1>
<?php if ($jobFilter): ?><p><a href="applications.php">Show all applications</a></p><?php endif; ?>

<?php if (!$apps): ?>
    <div class="empty-state"><h3>No applications yet</h3><p>Apply to a job on the site and it will show up here.</p></div>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Applicant</th><th>Job</th><th>Note</th><th>Applied</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($apps as $a): ?>
            <tr>
                <td>
                    <strong><?= e($a['name']) ?></strong><br>
                    <a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a>
                    <?php if ($a['phone']): ?><br><span class="muted"><?= e($a['phone']) ?></span><?php endif; ?>
                    <?php if ($a['portfolio_url']): ?><br><a href="<?= e($a['portfolio_url']) ?>" target="_blank" rel="noopener noreferrer">Portfolio</a><?php endif; ?>
                </td>
                <td><?= e($a['title']) ?><br><span class="muted"><?= e($a['company_name']) ?></span></td>
                <td style="max-width:320px"><?= e(mb_strimwidth($a['cover_note'], 0, 160, '...')) ?></td>
                <td><?= e(date('j M Y', strtotime($a['applied_at']))) ?></td>
                <td>
                    <form class="inline-form" method="post">
                        <input type="hidden" name="app_id" value="<?= (int) $a['id'] ?>">
                        <select name="status" class="status-select" onchange="this.form.submit()">
                            <?php foreach ($statuses as $s): ?><option <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                        </select>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/_foot.php'; ?>
