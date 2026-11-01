<?php
require __DIR__ . '/config.php';

$pageTitle = '';
$currentPage = 'home';

$jobCount = (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
$companyCount = (int) $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
$newThisWeek = (int) $pdo->query('SELECT COUNT(*) FROM jobs WHERE posted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();

$categories = $pdo->query(
    'SELECT cat.*, COUNT(j.id) AS job_count
     FROM categories cat LEFT JOIN jobs j ON j.category_id = cat.id
     GROUP BY cat.id ORDER BY job_count DESC, cat.name'
)->fetchAll();

$locations = $pdo->query('SELECT DISTINCT location FROM jobs ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);

$heroJobs = $pdo->query(JOB_SELECT . ' WHERE j.is_featured = 1 ORDER BY j.posted_at DESC LIMIT 3')->fetchAll();
$latestJobs = $pdo->query(JOB_SELECT . ' ORDER BY j.posted_at DESC LIMIT 6')->fetchAll();

$companies = $pdo->query(
    'SELECT c.*, COUNT(j.id) AS open_roles
     FROM companies c LEFT JOIN jobs j ON j.company_id = c.id
     GROUP BY c.id ORDER BY open_roles DESC, c.name LIMIT 6'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="wrap hero-grid">
        <div>
            <span class="eyebrow"><?= icon('check', 15) ?> <?= $newThisWeek ?> new jobs this week</span>
            <h1>Find work that <em>fits you</em>, not just a job title</h1>
            <p class="hero-lead">Search real roles in tech, design, healthcare, education and more. Every listing shows the salary range up front, so you can decide quickly.</p>

            <form class="search-card" action="jobs.php" method="get">
                <label class="search-field">
                    <?= icon('search', 20) ?>
                    <input type="text" name="q" placeholder="Job title or skill" aria-label="Job title or skill">
                </label>
                <label class="search-field">
                    <?= icon('pin', 20) ?>
                    <select name="location" aria-label="Location">
                        <option value="">Location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= e($loc) ?>"><?= e($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="search-field">
                    <?= icon('briefcase', 20) ?>
                    <select name="category" aria-label="Category">
                        <option value="">Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <div class="quick-links">
                <span>Popular:</span>
                <a href="jobs.php?mode=Remote">Remote</a>
                <a href="jobs.php?level=Entry">Entry level</a>
                <a href="jobs.php?type=Internship">Internships</a>
                <a href="jobs.php?category=engineering">Engineering</a>
            </div>
        </div>

        <div class="hero-stack" aria-hidden="true">
            <?php foreach ($heroJobs as $job): ?>
                <div class="stack-card">
                    <?= logo_html($job['company_name'], $job['company_slug'], $job['company_color'], (int) $job['company_shape'], 54) ?>
                    <div>
                        <h3><?= e($job['title']) ?></h3>
                        <p><?= e($job['company_name']) ?> in <?= e($job['location']) ?></p>
                        <p class="stack-salary">$<?= number_format((int) $job['salary_min']) ?> to $<?= number_format((int) $job['salary_max']) ?> a year</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="wrap">
    <div class="stats">
        <div class="stat"><span class="stat-num" data-count="<?= $jobCount ?>"><?= $jobCount ?></span><span class="stat-label">Open jobs</span></div>
        <div class="stat"><span class="stat-num" data-count="<?= $companyCount ?>"><?= $companyCount ?></span><span class="stat-label">Hiring companies</span></div>
        <div class="stat"><span class="stat-num" data-count="<?= $newThisWeek ?>"><?= $newThisWeek ?></span><span class="stat-label">Added in the last 7 days</span></div>
    </div>
</div>

<section class="section">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Browse by category</h2>
                <p>Pick a field and see everything that is open right now.</p>
            </div>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a class="category-card" href="jobs.php?category=<?= e($cat['slug']) ?>">
                    <span class="category-icon"><?= icon($cat['icon'], 24) ?></span>
                    <span>
                        <strong><?= e($cat['name']) ?></strong>
                        <span><?= (int) $cat['job_count'] ?> open <?= (int) $cat['job_count'] === 1 ? 'job' : 'jobs' ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Latest jobs</h2>
                <p>Added recently by employers.</p>
            </div>
            <a class="link-arrow" href="jobs.php">See all jobs <?= icon('arrow', 18) ?></a>
        </div>
        <div class="job-list">
            <?php foreach ($latestJobs as $job): ?>
                <?php require __DIR__ . '/includes/job_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Companies hiring now</h2>
                <p>Read about the team before you apply.</p>
            </div>
            <a class="link-arrow" href="companies.php">All companies <?= icon('arrow', 18) ?></a>
        </div>
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
</section>

<section class="section section-alt">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>How Jobhunt works</h2>
            </div>
        </div>
        <div class="steps">
            <div class="step">
                <h3>Search</h3>
                <p>Filter by category, location, work mode and experience level to see only the jobs that suit you.</p>
            </div>
            <div class="step">
                <h3>Save</h3>
                <p>Tap the bookmark on any job to keep it. Come back to your saved list when you are ready to apply.</p>
            </div>
            <div class="step">
                <h3>Apply</h3>
                <p>Send your details and a short note in one form. No account needed, and each employer sees your application in their dashboard.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="cta-band">
            <div>
                <h2>Hiring? Post a job in two minutes.</h2>
                <p>Add your company, describe the role and start receiving applications straight away.</p>
            </div>
            <a href="post_job.php" class="btn btn-accent">Post a job</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
