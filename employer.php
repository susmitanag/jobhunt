<?php
require __DIR__ . '/config.php';
require_login('employer');

$user = current_user();
$companyId = (int) $user['company_id'];
$statuses = ['New', 'Reviewed', 'Shortlisted', 'Rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, $statuses, true)) {
            // The join makes sure an employer can only change applications for their own jobs.
            $pdo->prepare('UPDATE applications a JOIN jobs j ON j.id = a.job_id SET a.status = ? WHERE a.id = ? AND j.company_id = ?')
                ->execute([$status, (int) ($_POST['app_id'] ?? 0), $companyId]);
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM jobs WHERE id = ? AND company_id = ?')->execute([(int) ($_POST['job_id'] ?? 0), $companyId]);
    }
    header('Location: employer.php' . (!empty($_GET['job']) ? '?job=' . (int) $_GET['job'] : ''));
    exit;
}

$co = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$co->execute([$companyId]);
$company = $co->fetch();

$jobsStmt = $pdo->prepare(
    'SELECT j.id, j.title, j.posted_at, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS apps
     FROM jobs j WHERE j.company_id = ? ORDER BY j.posted_at DESC'
);
$jobsStmt->execute([$companyId]);
$jobs = $jobsStmt->fetchAll();

$jobFilter = (int) ($_GET['job'] ?? 0);
$sql = 'SELECT a.*, j.title FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.company_id = ?';
$params = [$companyId];
if ($jobFilter) { $sql .= ' AND a.job_id = ?'; $params[] = $jobFilter; }
$sql .= ' ORDER BY a.applied_at DESC';
$appsStmt = $pdo->prepare($sql);
$appsStmt->execute($params);
$apps = $appsStmt->fetchAll();

$stCounts = ['New' => 0, 'Reviewed' => 0, 'Shortlisted' => 0, 'Rejected' => 0];
$allApps = $pdo->prepare('SELECT a.status, COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.company_id = ? GROUP BY a.status');
$allApps->execute([$companyId]);
foreach ($allApps->fetchAll(PDO::FETCH_KEY_PAIR) as $st => $n) { $stCounts[$st] = (int) $n; }
$jobApps = [];
foreach ($jobs as $jb) { $jobApps[] = ['title' => $jb['title'], 'n' => (int) ($jb['apps'] ?? 0)]; }

$pageTitle = 'Employer dashboard';
$currentPage = 'employer';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="wrap job-hero-grid">
        <div class="job-hero-main">
            <?= logo_html($company['name'], $company['slug'], $company['color'], (int) $company['shape'], 60) ?>
            <div>
                <h1><?= e($company['name']) ?></h1>
                <p>Employer dashboard</p>
            </div>
        </div>
        <a href="post_job.php" class="btn btn-primary">Post a job</a>
    </div>
</section>

<div class="wrap" style="padding-top:36px;padding-bottom:72px">
    <?php if (array_sum($stCounts) > 0): ?>
    <div class="chart-grid">
        <div class="chart-card"><h3>Applications by status</h3><div class="chart-box"><canvas id="chart-status" aria-label="Applications by status" role="img"></canvas></div></div>
        <div class="chart-card"><h3>Applications per job</h3><div class="chart-box"><canvas id="chart-jobs" aria-label="Applications per job" role="img"></canvas></div></div>
    </div>
    <script src="assets/vendor/chart.umd.js"></script>
    <script>
    (function () {
        var st = <?= json_encode($stCounts) ?>;
        var jobs = <?= json_encode($jobApps) ?>;
        Chart.defaults.font.family = 'Manrope, sans-serif';
        new Chart(document.getElementById('chart-status'), {
            type: 'doughnut',
            data: { labels: Object.keys(st), datasets: [{ data: Object.values(st), backgroundColor: ['#5B3DF5', '#9C8CF9', '#C8F25A', '#D9D6EE'], borderWidth: 0 }] },
            options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } }
        });
        new Chart(document.getElementById('chart-jobs'), {
            type: 'bar',
            data: { labels: jobs.map(function (j) { return j.title; }), datasets: [{ label: 'Applications', data: jobs.map(function (j) { return j.n; }), backgroundColor: '#5B3DF5', borderRadius: 4 }] },
            options: { maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    })();
    </script>
    <?php endif; ?>
    <h2>Your jobs</h2>
    <?php if (!$jobs): ?>
        <div class="empty-state"><h3>No jobs yet</h3><p>Post your first job to start receiving applications.</p><a href="post_job.php" class="btn btn-primary">Post a job</a></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Job</th><th>Posted</th><th>Applications</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td><a href="job.php?id=<?= (int) $j['id'] ?>"><?= e($j['title']) ?></a></td>
                        <td><?= e(date('j M Y', strtotime($j['posted_at']))) ?></td>
                        <td><a href="employer.php?job=<?= (int) $j['id'] ?>#applications"><?= (int) $j['apps'] ?></a></td>
                        <td>
                            <form class="inline-form" method="post" data-confirm="Delete this job and its applications?">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="job_id" value="<?= (int) $j['id'] ?>">
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2 id="applications" style="margin-top:40px">Applications <?php if ($jobFilter): ?><a class="link-arrow" style="font-size:.95rem" href="employer.php#applications">Show all</a><?php endif; ?></h2>
    <?php if (!$apps): ?>
        <div class="empty-state"><p>No applications yet.</p></div>
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
                        <td><?= e($a['title']) ?></td>
                        <td style="max-width:320px"><?= e(mb_strimwidth($a['cover_note'], 0, 160, '...')) ?></td>
                        <td><?= e(date('j M Y', strtotime($a['applied_at']))) ?></td>
                        <td>
                            <form class="inline-form" method="post">
                                <input type="hidden" name="action" value="status">
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
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
