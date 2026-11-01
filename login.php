<?php
require __DIR__ . '/config.php';

if (current_user()) { header('Location: ' . (is_employer() ? 'employer.php' : 'account.php')); exit; }

$pageTitle = 'Log in';
$currentPage = 'login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['back'])) {
    $_SESSION['login_back'] = safe_back($_GET['back']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password_hash'])) {
        $back = safe_back($_SESSION['login_back'] ?? '');
        unset($_SESSION['login_back']);
        login_user((int) $user['id']);
        if ($back !== '' && $user['role'] === 'seeker') {
            header('Location: ' . $back);
        } else {
            header('Location: ' . ($user['role'] === 'employer' ? 'employer.php' : 'account.php'));
        }
        exit;
    }
    $error = 'Wrong email or password.';
}

require __DIR__ . '/includes/header.php';
?>
<div class="wrap narrow" style="padding-top:48px;padding-bottom:72px;max-width:520px">
    <div class="panel form-card">
        <h1 style="font-size:1.9rem">Log in</h1>
        <p class="muted">New here? <a href="register.php">Create an account</a></p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form class="form" method="post">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button class="btn btn-primary btn-block" style="margin-top:18px">Log in</button>
        </form>
        <p class="muted" style="margin-top:18px;font-size:.9rem">
            Demo job seeker: demo@jobhunt.test / demo123<br>
            Demo employer: hr@northwind.test / hr12345
        </p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
