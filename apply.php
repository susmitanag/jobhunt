<?php
require __DIR__ . '/config.php';

$wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function respond(bool $wantsJson, bool $ok, array $errors, int $jobId, string $company = ''): void {
    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'errors' => $errors, 'company' => $company]);
        exit;
    }
    $_SESSION['apply_flash'] = ['ok' => $ok, 'errors' => $errors, 'company' => $company];
    header('Location: job.php?id=' . $jobId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: jobs.php');
    exit;
}

$jobId = (int) ($_POST['job_id'] ?? 0);
if (!is_seeker()) {
    respond($wantsJson, false, ['Please log in with a job seeker account to apply.'], $jobId);
}
$user = current_user();
$stmt = $pdo->prepare('SELECT j.id, c.name AS company_name FROM jobs j JOIN companies c ON c.id = j.company_id WHERE j.id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();
if (!$job) {
    respond($wantsJson, false, ['That job no longer exists.'], $jobId);
}

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$url   = trim($_POST['portfolio_url'] ?? '');
$note  = trim($_POST['cover_note'] ?? '');

$errors = [];
if ($name === '' || mb_strlen($name) < 2) { $errors[] = 'Please enter your full name.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) { $errors[] = 'The phone number looks wrong.'; }
if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) { $errors[] = 'The portfolio link must start with http:// or https://.'; }
if (mb_strlen($note) < 20) { $errors[] = 'Please write a short note (at least 20 characters) about why you fit this role.'; }

if (!$errors) {
    $dup = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE job_id = ? AND (email = ? OR user_id = ?)');
    $dup->execute([$jobId, $email, $user['id']]);
    if ($dup->fetchColumn()) {
        $errors[] = 'You have already applied to this job.';
    }
}

if ($errors) {
    respond($wantsJson, false, $errors, $jobId);
}

$ins = $pdo->prepare('INSERT INTO applications (job_id, user_id, name, email, phone, portfolio_url, cover_note) VALUES (?, ?, ?, ?, ?, ?, ?)');
$ins->execute([$jobId, $user['id'], $name, $email, $phone, $url, $note]);

respond($wantsJson, true, [], $jobId, $job['company_name']);
