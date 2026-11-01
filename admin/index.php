<?php
$adminTitle = 'Jobs';
require __DIR__ . '/_head.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = (int) ($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM jobs WHERE id = ?')->execute([$jobId]);
    } elseif ($action === 'feature') {
        $pdo->prepare('UPDATE jobs SET is_featured = 1 - is_featured WHERE id = ?')->execute([$jobId]);
    }
    header('Location: index.php');
    exit;
}

$stats = [
    'Jobs' => (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn(),
    'Companies' => (int) $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
    'Applications' => (int) $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn(),
    'New applications' => (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'New'")->fetchColumn(),
];

$statusRows = $pdo->query('SELECT status, COUNT(*) AS n FROM applications GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$statusData = [];
foreach (['New', 'Reviewed', 'Shortlisted', 'Rejected'] as $st) { $statusData[$st] = (int) ($statusRows[$st] ?? 0); }
$catRows = $pdo->query('SELECT cat.name, COUNT(j.id) AS n FROM categories cat LEFT JOIN jobs j ON j.category_id = cat.id GROUP BY cat.id, cat.name ORDER BY n DESC, cat.name')->fetchAll();

$jobs = $pdo->query(
    'SELECT j.id, j.title, j.is_featured, j.posted_at, c.name AS company_name,
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS apps
     FROM jobs j JOIN companies c ON c.id = j.company_id ORDER BY j.posted_at DESC'
)->fetchAll();
?>
<h1 style="font-size:2rem">Dashboard</h1>
<div class="admin-stats">
    <?php foreach ($stats as $label => $n): ?>
        <div class="stat"><span class="stat-num"><?= $n ?></span><span class="stat-label"><?= e($label) ?></span></div>
    <?php endforeach; ?>
</div>

<div class="chart-grid">
    <div class="chart-card"><h3>Applications by status</h3><div class="chart-box"><canvas id="chart-status" aria-label="Applications by status" role="img"></canvas></div></div>
    <div class="chart-card"><h3>Jobs by category</h3><div class="chart-box"><canvas id="chart-category" aria-label="Jobs by category" role="img"></canvas></div></div>
</div>
<script src="../assets/vendor/chart.umd.js"></script>
<script>
(function () {
    var statusData = <?= json_encode($statusData) ?>;
    var cats = <?= json_encode($catRows) ?>;
    Chart.defaults.font.family = 'Manrope, sans-serif';
    new Chart(document.getElementById('chart-status'), {
        type: 'doughnut',
        data: { labels: Object.keys(statusData), datasets: [{ data: Object.values(statusData), backgroundColor: ['#5B3DF5', '#9C8CF9', '#C8F25A', '#D9D6EE'], borderWidth: 0 }] },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } }
    });
    new Chart(document.getElementById('chart-category'), {
        type: 'bar',
        data: { labels: cats.map(function (c) { return c.name; }), datasets: [{ label: 'Jobs', data: cats.map(function (c) { return Number(c.n); }), backgroundColor: '#5B3DF5', borderRadius: 4 }] },
        options: { maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Job</th><th>Company</th><th>Applications</th><th>Posted</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $j): ?>
            <tr>
                <td><a href="../job.php?id=<?= (int) $j['id'] ?>"><?= e($j['title']) ?></a> <?= $j['is_featured'] ? '<span class="badge badge-featured">Featured</span>' : '' ?></td>
                <td><?= e($j['company_name']) ?></td>
                <td><a href="applications.php?job=<?= (int) $j['id'] ?>"><?= (int) $j['apps'] ?></a></td>
                <td><?= e(date('j M Y', strtotime($j['posted_at']))) ?></td>
                <td>
                    <form class="inline-form" method="post">
                        <input type="hidden" name="job_id" value="<?= (int) $j['id'] ?>">
                        <input type="hidden" name="action" value="feature">
                        <button class="btn btn-ghost btn-sm"><?= $j['is_featured'] ? 'Unfeature' : 'Feature' ?></button>
                    </form>
                    <form class="inline-form" method="post" data-confirm="Delete this job and its applications?">
                        <input type="hidden" name="job_id" value="<?= (int) $j['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
