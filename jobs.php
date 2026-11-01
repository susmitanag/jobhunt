<?php
require __DIR__ . '/config.php';

$pageTitle = 'Find jobs';
$currentPage = 'jobs';

$q        = trim($_GET['q'] ?? '');
$location = trim($_GET['location'] ?? '');
$category = trim($_GET['category'] ?? '');
$mode     = trim($_GET['mode'] ?? '');
$type     = trim($_GET['type'] ?? '');
$level    = trim($_GET['level'] ?? '');
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 8;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(j.title LIKE :q OR j.skills LIKE :q OR j.summary LIKE :q OR c.name LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($location !== '') { $where[] = 'j.location = :location'; $params[':location'] = $location; }
if ($category !== '') { $where[] = 'cat.slug = :category';   $params[':category'] = $category; }
if ($mode !== '')     { $where[] = 'j.work_mode = :mode';    $params[':mode'] = $mode; }
if ($type !== '')     { $where[] = 'j.job_type = :type';     $params[':type'] = $type; }
if ($level !== '')    { $where[] = 'j.level = :level';       $params[':level'] = $level; }

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$orders = [
    'newest'      => 'j.posted_at DESC',
    'salary_high' => 'j.salary_max DESC, j.posted_at DESC',
    'salary_low'  => 'j.salary_min ASC, j.posted_at DESC',
];
if (!isset($orders[$sort])) { $sort = 'newest'; }

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM jobs j JOIN companies c ON c.id = j.company_id JOIN categories cat ON cat.id = j.category_id' . $whereSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(JOB_SELECT . $whereSql . ' ORDER BY ' . $orders[$sort] . ' LIMIT ' . $perPage . ' OFFSET ' . $offset);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$locations = $pdo->query('SELECT DISTINCT location FROM jobs ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);

$chips = [];
if ($q !== '')        { $chips[] = ['Search: ' . $q, 'q']; }
if ($location !== '') { $chips[] = [$location, 'location']; }
if ($category !== '') {
    foreach ($categories as $c) { if ($c['slug'] === $category) { $chips[] = [$c['name'], 'category']; } }
}
if ($mode !== '')  { $chips[] = [$mode, 'mode']; }
if ($type !== '')  { $chips[] = [$type, 'type']; }
if ($level !== '') { $chips[] = [$level . ' level', 'level']; }

function radio_group(string $name, array $options, string $current): void {
    echo '<div class="radio-list">';
    echo '<label><input type="radio" name="' . e($name) . '" value=""' . ($current === '' ? ' checked' : '') . '> Any</label>';
    foreach ($options as $value => $label) {
        echo '<label><input type="radio" name="' . e($name) . '" value="' . e($value) . '"' . ($current === (string) $value ? ' checked' : '') . '> ' . e($label) . '</label>';
    }
    echo '</div>';
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="wrap">
        <h1>Find jobs</h1>
        <p><?= $total ?> <?= $total === 1 ? 'job matches' : 'jobs match' ?> your search.</p>
    </div>
</section>

<div class="wrap jobs-layout">
    <form class="filters" method="get" action="jobs.php" data-autosubmit>
        <h3>Filters <?php if ($chips): ?><a href="jobs.php" class="muted" style="font-size:.85rem">Clear all</a><?php endif; ?></h3>
        <input type="hidden" name="sort" value="<?= e($sort) ?>">

        <div class="filter-group">
            <label class="group-label" for="q">Keyword</label>
            <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Title, skill or company">
        </div>
        <div class="filter-group">
            <label class="group-label" for="category">Category</label>
            <select id="category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['slug']) ?>" <?= $category === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="group-label" for="location">Location</label>
            <select id="location" name="location">
                <option value="">Any location</option>
                <?php foreach ($locations as $l): ?>
                    <option value="<?= e($l) ?>" <?= $location === $l ? 'selected' : '' ?>><?= e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <span class="group-label">Work mode</span>
            <?php radio_group('mode', ['On-site' => 'On-site', 'Hybrid' => 'Hybrid', 'Remote' => 'Remote'], $mode); ?>
        </div>
        <div class="filter-group">
            <span class="group-label">Job type</span>
            <?php radio_group('type', ['Full-time' => 'Full-time', 'Part-time' => 'Part-time', 'Contract' => 'Contract', 'Internship' => 'Internship'], $type); ?>
        </div>
        <div class="filter-group">
            <span class="group-label">Experience</span>
            <?php radio_group('level', ['Entry' => 'Entry level', 'Mid' => 'Mid level', 'Senior' => 'Senior level'], $level); ?>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Apply filters</button>
    </form>

    <section>
        <div class="results-bar">
            <strong><?= $total ?> <?= $total === 1 ? 'job' : 'jobs' ?></strong>
            <label>Sort by
                <select class="sort-select" data-sort>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="salary_high" <?= $sort === 'salary_high' ? 'selected' : '' ?>>Highest salary</option>
                    <option value="salary_low" <?= $sort === 'salary_low' ? 'selected' : '' ?>>Lowest salary</option>
                </select>
            </label>
        </div>

        <?php if ($chips): ?>
            <div class="active-filters">
                <?php foreach ($chips as [$label, $key]): ?>
                    <a class="chip-remove" href="jobs.php?<?= e(build_query([$key => '', 'page' => ''])) ?>"><?= e($label) ?> <?= icon('x', 14) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$jobs): ?>
            <div class="empty-state">
                <h3>No jobs found</h3>
                <p>Try removing a filter or searching with a different word.</p>
                <a href="jobs.php" class="btn btn-primary">Clear filters</a>
            </div>
        <?php else: ?>
            <div class="job-list">
                <?php foreach ($jobs as $job): ?>
                    <?php require __DIR__ . '/includes/job_card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="pagination" aria-label="Pages">
                    <?php if ($page > 1): ?>
                        <a href="jobs.php?<?= e(build_query(['page' => $page - 1])) ?>">Previous</a>
                    <?php else: ?>
                        <span class="is-disabled">Previous</span>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="is-active" aria-current="page"><?= $i ?></span>
                        <?php else: ?>
                            <a href="jobs.php?<?= e(build_query(['page' => $i])) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $pages): ?>
                        <a href="jobs.php?<?= e(build_query(['page' => $page + 1])) ?>">Next</a>
                    <?php else: ?>
                        <span class="is-disabled">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
