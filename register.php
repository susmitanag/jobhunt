<?php
require __DIR__ . '/config.php';

if (current_user()) { header('Location: ' . (is_employer() ? 'employer.php' : 'account.php')); exit; }

$pageTitle = 'Create an account';
$currentPage = 'register';
$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = ($_POST['role'] ?? '') === 'employer' ? 'employer' : 'seeker';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (mb_strlen($name) < 2) { $errors[] = 'Please enter your name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if (strlen($pass) < 8) { $errors[] = 'The password must be at least 8 characters.'; }
    if ($pass !== $pass2) { $errors[] = 'The two passwords do not match.'; }

    $cName = $cIndustry = $cLocation = '';
    if ($role === 'employer') {
        $cName = trim($_POST['company_name'] ?? '');
        $cIndustry = trim($_POST['company_industry'] ?? '');
        $cLocation = trim($_POST['company_location'] ?? '');
        if ($cName === '' || $cIndustry === '' || $cLocation === '') {
            $errors[] = 'Company name, industry and location are required for an employer account.';
        }
    }

    if (!$errors) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $dup->execute([$email]);
        if ($dup->fetchColumn()) { $errors[] = 'An account with that email already exists. Try logging in.'; }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $companyId = null;
            if ($role === 'employer') {
                $companyId = create_company($pdo, $cName, $cIndustry, $cLocation);
            }
            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, company_id) VALUES (?, ?, ?, ?, ?)');
            $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $companyId]);
            $userId = (int) $pdo->lastInsertId();
            $pdo->commit();
            login_user($userId);
            header('Location: ' . ($role === 'employer' ? 'employer.php' : 'account.php'));
            exit;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Could not create your account. Please try again.';
        }
    }
}

$roleOld = ($old['role'] ?? 'seeker') === 'employer' ? 'employer' : 'seeker';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap narrow" style="padding-top:48px;padding-bottom:72px">
    <div class="panel form-card">
        <h1 style="font-size:1.9rem">Create your account</h1>
        <p class="muted">Already have one? <a href="login.php">Log in</a></p>
        <?php if ($errors): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form class="form" method="post" id="register-form">
            <span class="group-label" style="font-weight:700;color:var(--forest)">I am a</span>
            <div class="radio-list" style="flex-direction:row;gap:24px;margin:6px 0 4px">
                <label><input type="radio" name="role" value="seeker" <?= $roleOld === 'seeker' ? 'checked' : '' ?>> Job seeker</label>
                <label><input type="radio" name="role" value="employer" <?= $roleOld === 'employer' ? 'checked' : '' ?>> Employer</label>
            </div>

            <label for="name">Your name</label>
            <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required autocomplete="name">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required autocomplete="email">
            <div class="form-row">
                <div><label for="password">Password</label><input type="password" id="password" name="password" minlength="8" required autocomplete="new-password"></div>
                <div><label for="password2">Repeat password</label><input type="password" id="password2" name="password2" minlength="8" required autocomplete="new-password"></div>
            </div>

            <div id="employer-fields" <?= $roleOld === 'employer' ? '' : 'hidden' ?>>
                <label for="company_name">Company name</label>
                <input type="text" id="company_name" name="company_name" value="<?= e($old['company_name'] ?? '') ?>">
                <div class="form-row">
                    <div><label for="company_industry">Industry</label><input type="text" id="company_industry" name="company_industry" value="<?= e($old['company_industry'] ?? '') ?>"></div>
                    <div><label for="company_location">Location</label><input type="text" id="company_location" name="company_location" value="<?= e($old['company_location'] ?? '') ?>"></div>
                </div>
            </div>

            <button class="btn btn-primary btn-block" style="margin-top:18px">Create account</button>
        </form>
    </div>
</div>
<script>
document.querySelectorAll('#register-form input[name="role"]').forEach(function (r) {
    r.addEventListener('change', function () {
        document.getElementById('employer-fields').hidden = (document.querySelector('#register-form input[name="role"]:checked').value !== 'employer');
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
