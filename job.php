<?php
require __DIR__ . '/config.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(JOB_SELECT . ' WHERE j.id = ?');
$stmt->execute([$id]);
$job = $stmt->fetch();

if (!$job) {
    http_response_code(404);
    $pageTitle = 'Job not found';
    $currentPage = 'jobs';
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap narrow" style="padding:64px 0"><div class="empty-state"><h3>This job is not available</h3><p>It may have been removed.</p><a class="btn btn-primary" href="jobs.php">Browse jobs</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$co = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$co->execute([$job['company_id']]);
$company = $co->fetch();

$similarStmt = $pdo->prepare(JOB_SELECT . ' WHERE j.category_id = ? AND j.id <> ? ORDER BY j.posted_at DESC LIMIT 3');
$similarStmt->execute([$job['category_id'], $job['id']]);
$similar = $similarStmt->fetchAll();

$alreadyApplied = false;
if (is_seeker()) {
    $ap = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE job_id = ? AND user_id = ?');
    $ap->execute([$job['id'], current_user()['id']]);
    $alreadyApplied = (bool) $ap->fetchColumn();
}

$flash = $_SESSION['apply_flash'] ?? null;
unset($_SESSION['apply_flash']);

$pageTitle = $job['title'] . ' at ' . $job['company_name'];
$currentPage = 'jobs';
$jobSaved = is_saved((int) $job['id']);

require __DIR__ . '/includes/header.php';
?>

<section class="job-hero page-head">
    <div class="wrap">
        <p class="breadcrumb"><a href="jobs.php">Jobs</a> / <a href="jobs.php?category=<?= e($job['category_slug']) ?>"><?= e($job['category_name']) ?></a></p>
        <div class="job-hero-grid">
            <div class="job-hero-main">
                <?= logo_html($job['company_name'], $job['company_slug'], $job['company_color'], (int) $job['company_shape'], 72) ?>
                <div>
                    <h1><?= e($job['title']) ?></h1>
                    <p class="job-company"><a href="company.php?slug=<?= e($job['company_slug']) ?>"><?= e($job['company_name']) ?></a> in <?= e($job['location']) ?></p>
                    <div class="tag-row">
                        <span class="tag tag-level"><?= e($job['level']) ?> level</span>
                        <span class="tag"><?= e($job['job_type']) ?></span>
                        <span class="tag"><?= e($job['work_mode']) ?></span>
                        <?php if ($job['is_featured']): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="job-hero-actions">
                <button type="button" class="save-btn <?= $jobSaved ? 'is-saved' : '' ?>" data-save="<?= (int) $job['id'] ?>"
                        aria-pressed="<?= $jobSaved ? 'true' : 'false' ?>" aria-label="<?= $jobSaved ? 'Remove from saved jobs' : 'Save this job' ?>">
                    <?= icon('bookmark', 22) ?>
                </button>
                <a href="#apply" class="btn btn-primary">Apply now</a>
            </div>
        </div>
    </div>
</section>

<div class="wrap detail-layout">
    <div>
        <div class="panel">
            <h2>About the role</h2>
            <p><?= nl2br(e($job['summary'])) ?></p>
        </div>

        <div class="panel">
            <h2>What you will do</h2>
            <ul class="check-list">
                <?php foreach (text_lines($job['responsibilities']) as $line): ?>
                    <li><?= icon('check', 18) ?><span><?= e($line) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="panel">
            <h2>What we are looking for</h2>
            <ul class="check-list">
                <?php foreach (text_lines($job['requirements']) as $line): ?>
                    <li><?= icon('check', 18) ?><span><?= e($line) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <?php $skills = skill_list($job['skills']); if ($skills): ?>
                <h2 style="margin-top:24px">Skills</h2>
                <div class="tag-row">
                    <?php foreach ($skills as $s): ?><span class="tag"><?= e($s) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="company-box">
                <?= logo_html($company['name'], $company['slug'], $company['color'], (int) $company['shape'], 56) ?>
                <div>
                    <h3>About <?= e($company['name']) ?></h3>
                    <span class="muted"><?= e($company['industry']) ?> / <?= e($company['size_label']) ?></span>
                </div>
            </div>
            <p><?= e($company['about']) ?></p>
            <a class="link-arrow" href="company.php?slug=<?= e($company['slug']) ?>">See all roles at <?= e($company['name']) ?> <?= icon('arrow', 18) ?></a>
        </div>

        <?php if ($similar): ?>
            <h2 style="margin:32px 0 16px">Similar jobs</h2>
            <div class="job-list">
                <?php foreach ($similar as $job2): $jobBackup = $job; $job = $job2; require __DIR__ . '/includes/job_card.php'; $job = $jobBackup; endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="sidebar">
        <div class="panel">
            <h2>Job overview</h2>
            <ul class="overview">
                <li><?= icon('coins', 20) ?><div><small>Salary per year</small><strong><?= e(salary_text($job['salary_min'], $job['salary_max'])) ?></strong></div></li>
                <li><?= icon('pin', 20) ?><div><small>Location</small><strong><?= e($job['location']) ?><?= $job['location'] === $job['work_mode'] ? '' : ' (' . e($job['work_mode']) . ')' ?></strong></div></li>
                <li><?= icon('briefcase', 20) ?><div><small>Type</small><strong><?= e($job['job_type']) ?></strong></div></li>
                <li><?= icon('clock', 20) ?><div><small>Posted</small><strong><?= e(time_ago($job['posted_at'])) ?></strong></div></li>
                <?php if (!empty($job['deadline'])): ?>
                    <li><?= icon('calendar', 20) ?><div><small>Apply before</small><strong><?= e(date('j M Y', strtotime($job['deadline']))) ?></strong></div></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="panel apply-panel" id="apply">
            <?php if ($flash && $flash['ok']): ?>
                <div class="success-box">
                    <div class="success-icon"><?= icon('check', 30) ?></div>
                    <h2>Application sent</h2>
                    <p class="muted">Thank you. The team at <?= e($flash['company']) ?> will look at your application.</p>
                </div>
            <?php elseif (is_employer()): ?>
                <h2>Apply for this job</h2>
                <p class="muted">You are logged in as an employer. Log in with a job seeker account to apply.</p>
            <?php elseif (!is_seeker()): ?>
                <h2>Apply for this job</h2>
                <p class="muted">Log in or create a free job seeker account to apply. It only takes a minute.</p>
                <a class="btn btn-primary btn-block" href="login.php?back=<?= urlencode('job.php?id=' . (int) $job['id']) ?>">Log in to apply</a>
                <p style="text-align:center;margin:14px 0 0">No account? <a href="register.php">Sign up</a></p>
            <?php elseif ($alreadyApplied): ?>
                <div class="success-box">
                    <div class="success-icon"><?= icon('check', 30) ?></div>
                    <h2>You have applied</h2>
                    <p class="muted">Follow the status in <a href="account.php">My applications</a>.</p>
                </div>
            <?php else: ?>
                <div x-data="applyForm()">
                    <div x-show="sent" x-cloak class="success-box">
                        <div class="success-icon"><?= icon('check', 30) ?></div>
                        <h2>Application sent</h2>
                        <p class="muted">Thank you. The team at <span x-text="company"></span> will look at your application and email you if you move forward.</p>
                    </div>
                    <div x-show="!sent">
                        <h2>Apply for this job</h2>
                        <?php if ($flash && !$flash['ok']): ?>
                            <div class="alert alert-error"><ul><?php foreach ($flash['errors'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                        <?php endif; ?>
                        <div class="alert alert-error" x-show="errors.length" x-cloak>
                            <ul><template x-for="err in errors" :key="err"><li x-text="err"></li></template></ul>
                        </div>
                        <form class="form" id="apply-form" action="apply.php" method="post" novalidate @submit.prevent="submit($event)">
                            <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                            <label for="name">Full name</label>
                            <input type="text" id="name" name="name" required autocomplete="name" value="<?= e(current_user()['name']) ?>">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required autocomplete="email" value="<?= e(current_user()['email']) ?>">
                            <label for="phone">Phone <span class="opt">(optional)</span></label>
                            <input type="tel" id="phone" name="phone" autocomplete="tel">
                            <label for="portfolio_url">Portfolio or GitHub link <span class="opt">(optional)</span></label>
                            <input type="url" id="portfolio_url" name="portfolio_url" placeholder="https://">
                            <label for="cover_note">Why are you a good fit?</label>
                            <textarea id="cover_note" name="cover_note" rows="5" required></textarea>
                            <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px" :disabled="sending" x-text="sending ? 'Sending...' : 'Send application'">Send application</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
