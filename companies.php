<?php
require __DIR__ . '/config.php';

$pageTitle = 'Companies';
$currentPage = 'companies';

$companies = $pdo->query(
    'SELECT c.*, COUNT(j.id) AS open_roles
     FROM companies c LEFT JOIN jobs j ON j.company_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="wrap">
        <h1>Companies</h1>
        <p>Learn about the teams behind the jobs.</p>
    </div>
</section>
<div class="wrap" style="padding-top:36px;padding-bottom:72px">
    <div class="company-grid">
        <?php foreach ($companies as $co): ?>
            <a class="company-card" href="company.php?slug=<?= e($co['slug']) ?>">
                <div class="company-card-top">
                    <?= logo_html($co['name'], $co['slug'], $co['color'], (int) $co['shape'], 52) ?>
                    <div>
                        <h3><?= e($co['name']) ?></h3>
                        <p><?= e($co['industry']) ?> in <?= e($co['location']) ?></p>
                    </div>
                </div>
                <span class="open-roles"><?= (int) $co['open_roles'] ?> open <?= (int) $co['open_roles'] === 1 ? 'role' : 'roles' ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
