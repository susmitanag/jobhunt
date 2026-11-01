<?php
require __DIR__ . '/../config.php';

if (is_admin()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($pass, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $admin['id'];
        header('Location: index.php');
        exit;
    }
    $error = 'Wrong email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin login | Jobhunt</title>
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrap">
    <div class="login-box panel form-card">
        <div style="text-align:center;margin-bottom:14px"><img src="../assets/img/mark.svg" width="52" height="52" alt=""></div>
        <h1 style="font-size:1.6rem;text-align:center">Admin login</h1>
        <p class="muted" style="text-align:center">Demo login: admin@jobhunt.test / admin123</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form class="form" method="post">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button class="btn btn-primary btn-block" style="margin-top:16px">Log in</button>
        </form>
        <p style="text-align:center;margin-top:16px"><a href="../index.php">Back to the site</a></p>
    </div>
</div>
</body>
</html>
