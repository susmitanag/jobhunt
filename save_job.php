<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Use POST.']);
    exit;
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$check = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE id = ?');
$check->execute([$jobId]);
if (!$check->fetchColumn()) {
    echo json_encode(['success' => false, 'error' => 'That job no longer exists.']);
    exit;
}

if (is_employer()) {
    echo json_encode(['success' => false, 'error' => 'Employer accounts cannot save jobs.']);
    exit;
}

if (is_seeker()) {
    $uid = current_user()['id'];
    $has = $pdo->prepare('SELECT COUNT(*) FROM saved_jobs WHERE user_id = ? AND job_id = ?');
    $has->execute([$uid, $jobId]);
    if ($has->fetchColumn()) {
        $pdo->prepare('DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?')->execute([$uid, $jobId]);
        $saved = false;
    } else {
        $pdo->prepare('INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)')->execute([$uid, $jobId]);
        $saved = true;
    }
} elseif (isset($_SESSION['saved'][$jobId])) {
    unset($_SESSION['saved'][$jobId]);
    $saved = false;
} else {
    $_SESSION['saved'][$jobId] = time();
    $saved = true;
}

echo json_encode(['success' => true, 'saved' => $saved, 'count' => count(saved_ids())]);
