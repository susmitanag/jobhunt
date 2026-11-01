<?php
require __DIR__ . '/config.php';

$pageTitle = 'Post a job';
$currentPage = 'post';

if (!current_user()) {
    header('Location: register.php');
    exit;
}
if (!is_employer()) {
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap narrow" style="padding:64px 0"><div class="empty-state"><h3>Employer accounts only</h3><p>Posting jobs needs an employer account. You are logged in as a job seeker.</p><a class="btn btn-primary" href="jobs.php">Browse jobs</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$companyId = (int) current_user()['company_id'];
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$errors = [];
$old = $_POST;
$postedId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $mode = $_POST['work_mode'] ?? '';
    $type = $_POST['job_type'] ?? '';
    $level = $_POST['level'] ?? '';
    $min = (int) ($_POST['salary_min'] ?? 0);
    $max = (int) ($_POST['salary_max'] ?? 0);
    $summary = trim($_POST['summary'] ?? '');
    $resp = trim($_POST['responsibilities'] ?? '');
    $req = trim($_POST['requirements'] ?? '');
    $skills = trim($_POST['skills'] ?? '');

    if ($title === '') { $errors[] = 'Job title is required.'; }
    if ($location === '') { $errors[] = 'Location is required.'; }
    if (!in_array($mode, ['On-site', 'Hybrid', 'Remote'], true)) { $errors[] = 'Choose a work mode.'; }
    if (!in_array($type, ['Full-time', 'Part-time', 'Contract', 'Internship'], true)) { $errors[] = 'Choose a job type.'; }
    if (!in_array($level, ['Entry', 'Mid', 'Senior'], true)) { $errors[] = 'Choose an experience level.'; }
    if ($min < 0 || $max < $min) { $errors[] = 'The maximum salary must be at least the minimum salary.'; }
    if (mb_strlen($summary) < 30) { $errors[] = 'Write a short description (at least 30 characters).'; }
    if ($resp === '') { $errors[] = 'Add at least one responsibility (one per line).'; }
    if ($req === '') { $errors[] = 'Add at least one requirement (one per line).'; }

    $catCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
    $catCheck->execute([$categoryId]);
    if (!$catCheck->fetchColumn()) { $errors[] = 'Choose a category.'; }

    if (!$errors) {
        $ins = $pdo->prepare(
            'INSERT INTO jobs (company_id, category_id, title, location, work_mode, job_type, level, salary_min, salary_max, summary, responsibilities, requirements, skills, deadline)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))'
        );
        $ins->execute([$companyId, $categoryId, $title, $location, $mode, $type, $level, $min, $max, $summary, $resp, $req, $skills]);
        $postedId = (int) $pdo->lastInsertId();
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="wrap">
        <h1>Post a job</h1>
        <p>Fill in the details below. The job is posted under your company and goes live straight away.</p>
    </div>
</section>

<div class="wrap narrow" style="padding-top:36px;padding-bottom:72px">
<?php if ($postedId): ?>
    <div class="panel form-card">
        <div class="success-box">
            <div class="success-icon"><?= icon('check', 30) ?></div>
            <h2>Your job is live</h2>
            <p class="muted">Candidates can find it and apply now.</p>
            <a class="btn btn-primary" href="job.php?id=<?= $postedId ?>">View the job</a> <a class="btn btn-ghost" href="employer.php">Back to dashboard</a>
        </div>
    </div>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form class="panel form-card form" method="post" action="post_job.php">
        <label for="title">Job title</label>
        <input type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>" required>

        <div class="form-row">
            <div>
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Choose</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ($old['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= e($old['location'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="work_mode">Work mode</label>
                <select id="work_mode" name="work_mode">
                    <?php foreach (['On-site', 'Hybrid', 'Remote'] as $m): ?><option <?= ($old['work_mode'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="job_type">Job type</label>
                <select id="job_type" name="job_type">
                    <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship'] as $m): ?><option <?= ($old['job_type'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="level">Experience level</label>
                <select id="level" name="level">
                    <?php foreach (['Entry', 'Mid', 'Senior'] as $m): ?><option value="<?= $m ?>" <?= ($old['level'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?> level</option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="skills">Skills <span class="opt">(comma separated)</span></label>
                <input type="text" id="skills" name="skills" value="<?= e($old['skills'] ?? '') ?>" placeholder="PHP, MySQL, JavaScript">
            </div>
        </div>

        <div class="form-row">
            <div><label for="salary_min">Yearly salary from ($)</label><input type="number" id="salary_min" name="salary_min" min="0" step="1000" value="<?= e($old['salary_min'] ?? '') ?>"></div>
            <div><label for="salary_max">Yearly salary to ($)</label><input type="number" id="salary_max" name="salary_max" min="0" step="1000" value="<?= e($old['salary_max'] ?? '') ?>"></div>
        </div>

        <label for="summary">About the role</label>
        <textarea id="summary" name="summary" rows="4" required><?= e($old['summary'] ?? '') ?></textarea>
        <label for="responsibilities">Responsibilities <span class="opt">(one per line)</span></label>
        <textarea id="responsibilities" name="responsibilities" rows="5" required><?= e($old['responsibilities'] ?? '') ?></textarea>
        <label for="requirements">Requirements <span class="opt">(one per line)</span></label>
        <textarea id="requirements" name="requirements" rows="5" required><?= e($old['requirements'] ?? '') ?></textarea>

        <button type="submit" class="btn btn-primary" style="margin-top:18px">Publish job</button>
    </form>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
