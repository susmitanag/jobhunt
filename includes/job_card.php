<?php
// Expects $job (a row from JOB_SELECT). Optional: $savedState to override.
$jobSaved = is_saved((int) $job['id']);
$skillsShown = array_slice(skill_list($job['skills']), 0, 3);
?>
<article class="job-card" data-job-id="<?= (int) $job['id'] ?>">
    <a class="job-card-logo" href="company.php?slug=<?= e($job['company_slug']) ?>" tabindex="-1" aria-hidden="true">
        <?= logo_html($job['company_name'], $job['company_slug'], $job['company_color'], (int) $job['company_shape'], 52) ?>
    </a>
    <div class="job-card-body">
        <div class="job-card-top">
            <h3 class="job-title"><a href="job.php?id=<?= (int) $job['id'] ?>"><?= e($job['title']) ?></a></h3>
            <?php if ($job['is_featured']): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
        </div>
        <p class="job-company">
            <a href="company.php?slug=<?= e($job['company_slug']) ?>"><?= e($job['company_name']) ?></a>
        </p>
        <ul class="job-meta">
            <li><?= icon('pin', 15) ?> <?= e($job['location']) ?><?= $job['location'] === $job['work_mode'] ? '' : ' (' . e($job['work_mode']) . ')' ?></li>
            <li><?= icon('briefcase', 15) ?> <?= e($job['job_type']) ?></li>
            <li><?= icon('coins', 15) ?> $<?= number_format((int) $job['salary_min']) ?> to $<?= number_format((int) $job['salary_max']) ?> a year</li>
        </ul>
        <div class="tag-row">
            <span class="tag tag-level"><?= e($job['level']) ?> level</span>
            <?php foreach ($skillsShown as $skill): ?>
                <span class="tag"><?= e($skill) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="job-card-side">
        <button type="button" class="save-btn <?= $jobSaved ? 'is-saved' : '' ?>" data-save="<?= (int) $job['id'] ?>"
                aria-pressed="<?= $jobSaved ? 'true' : 'false' ?>" aria-label="<?= $jobSaved ? 'Remove from saved jobs' : 'Save this job' ?>">
            <?= icon('bookmark', 20) ?>
        </button>
        <span class="job-posted"><?= e(time_ago($job['posted_at'])) ?></span>
    </div>
</article>
