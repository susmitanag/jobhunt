<?php
// Database settings. These match XAMPP defaults (user root, no password).
$DB_HOST = 'localhost';
$DB_NAME = 'jobhunt';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Could not connect to the database. Check that MySQL is running and that you imported db/schema.sql. Details: ' . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Small helpers ---------- */

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function time_ago(string $datetime): string {
    $seconds = time() - strtotime($datetime);
    if ($seconds < 3600) {
        $m = max(1, (int) floor($seconds / 60));
        return $m . ' min ago';
    }
    if ($seconds < 86400) {
        $h = (int) floor($seconds / 3600);
        return $h . ($h === 1 ? ' hour ago' : ' hours ago');
    }
    $d = (int) floor($seconds / 86400);
    if ($d < 30) {
        return $d . ($d === 1 ? ' day ago' : ' days ago');
    }
    return date('j M Y', strtotime($datetime));
}

function salary_text($min, $max): string {
    $min = (int) $min;
    $max = (int) $max;
    if ($min <= 0 && $max <= 0) {
        return 'Salary not shown';
    }
    return '$' . number_format($min) . ' to $' . number_format($max);
}

function skill_list(string $csv): array {
    $items = array_map('trim', explode(',', $csv));
    return array_values(array_filter($items, fn($s) => $s !== ''));
}

function text_lines(string $text): array {
    $lines = preg_split('/\r\n|\r|\n/', $text);
    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function build_query(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}

/* ---------- Accounts ----------
   Job seekers and employers log in with an email and password.
   The session only stores the user id. Everything else is read from the database. */

function current_user(): ?array {
    global $pdo;
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = $pdo->prepare('SELECT id, name, email, role, company_id FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function is_seeker(): bool {
    $u = current_user();
    return $u !== null && $u['role'] === 'seeker';
}

function is_employer(): bool {
    $u = current_user();
    return $u !== null && $u['role'] === 'employer';
}

function login_user(int $userId): void {
    global $pdo;
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;

    // Keep anything the person saved before logging in.
    $guestSaved = array_keys($_SESSION['saved'] ?? []);
    $user = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $user->execute([$userId]);
    if ($user->fetchColumn() === 'seeker' && $guestSaved) {
        $ins = $pdo->prepare('INSERT IGNORE INTO saved_jobs (user_id, job_id) SELECT ?, id FROM jobs WHERE id = ?');
        foreach ($guestSaved as $jobId) {
            $ins->execute([$userId, (int) $jobId]);
        }
    }
    unset($_SESSION['saved']);
}

function require_login(string $role, string $back = ''): void {
    $u = current_user();
    if ($u === null || $u['role'] !== $role) {
        $_SESSION['login_back'] = $back;
        header('Location: login.php');
        exit;
    }
}

function safe_back(string $path): string {
    // Only allow simple local page names such as job.php?id=3
    return preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%-]*)?$/', $path) ? $path : '';
}

/* ---------- Saved jobs ----------
   Logged in job seekers: saved in the database, so they stay after logout.
   Visitors: saved in the session only. */

function saved_ids(): array {
    global $pdo;
    if (is_seeker()) {
        $stmt = $pdo->prepare('SELECT job_id FROM saved_jobs WHERE user_id = ?');
        $stmt->execute([current_user()['id']]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    return array_map('intval', array_keys($_SESSION['saved'] ?? []));
}

function is_saved(int $jobId): bool {
    static $cache = null;
    if ($cache === null) {
        $cache = array_flip(saved_ids());
    }
    return isset($cache[$jobId]);
}

/* ---------- Admin ---------- */

function is_admin(): bool {
    return !empty($_SESSION['admin']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}

/* ---------- Company logo ----------
   If a real logo file exists at assets/img/companies/<slug>.png (or jpg, webp, svg)
   it is used. Otherwise a clean generated logo is drawn from the company name. */

function logo_html(string $name, string $slug, string $color, int $shape = 0, int $size = 48, string $base = ''): string {
    $files = glob(__DIR__ . '/assets/img/companies/' . $slug . '.{png,jpg,jpeg,webp,svg}', GLOB_BRACE);
    if (!empty($files)) {
        $file = basename($files[0]);
        return '<img class="co-logo" src="' . e($base) . 'assets/img/companies/' . e($file) . '" width="' . $size . '" height="' . $size . '" alt="' . e($name) . ' logo">';
    }

    $words = preg_split('/\s+/', trim($name));
    $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    $decor = '';
    switch ($shape % 4) {
        case 0:
            $decor = '<circle cx="42" cy="6" r="16" fill="#fff" opacity="0.14"/>';
            break;
        case 1:
            $decor = '<polygon points="0,48 48,14 48,48" fill="#fff" opacity="0.13"/>';
            break;
        case 2:
            $decor = '<circle cx="8" cy="42" r="7" fill="#fff" opacity="0.16"/><circle cx="40" cy="8" r="4" fill="#fff" opacity="0.16"/>';
            break;
        default:
            $decor = '<rect y="35" width="48" height="13" fill="#fff" opacity="0.15"/>';
            break;
    }

    return '<svg class="co-logo" width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" role="img" aria-label="' . e($name) . ' logo">'
        . '<rect width="48" height="48" fill="' . e($color) . '"/>' . $decor
        . '<text x="24" y="30" text-anchor="middle" font-family="Space Grotesk, Arial, sans-serif" font-size="17" font-weight="700" fill="#fff">' . e($initials) . '</text>'
        . '</svg>';
}

/* ---------- Icons (simple line icons, drawn inline) ---------- */

function icon(string $name, int $size = 18): string {
    $paths = [
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'pin'       => '<path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'bookmark'  => '<path d="M6 3h12v18l-6-4-6 4z"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2M3 13h18"/>',
        'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'code'      => '<path d="M8 8l-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/>',
        'pen'       => '<path d="M4 20l1-4L16 5l3 3L8 19zM14 7l3 3"/>',
        'megaphone' => '<path d="M3 10v4h3l9 5V5L6 10zM18 9a4 4 0 010 6"/>',
        'chart'     => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'cross'     => '<path d="M10 3h4v7h7v4h-7v7h-4v-7H3v-4h7z"/>',
        'book'      => '<path d="M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2zM4 19a2 2 0 012-2h13"/>',
        'headset'   => '<path d="M4 14v-2a8 8 0 0116 0v2M4 14h3v5H5a1 1 0 01-1-1zM20 14h-3v5h2a1 1 0 001-1zM17 19c0 1.5-2 2-5 2"/>',
        'box'       => '<path d="M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10"/>',
        'bag'       => '<path d="M5 8h14l-1 12H6zM9 8V6a3 3 0 016 0v2"/>',
        'check'     => '<path d="M5 12l5 5 9-10"/>',
        'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x'         => '<path d="M6 6l12 12M18 6L6 18"/>',
        'building'  => '<path d="M4 21V5a1 1 0 011-1h9a1 1 0 011 1v16M15 9h4a1 1 0 011 1v11M8 8h3M8 12h3M8 16h3M3 21h18"/>',
        'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'coins'     => '<circle cx="9" cy="9" r="6"/><path d="M15 9.5A6 6 0 1111 20M9 7v4"/>',
    ];
    $inner = $paths[$name] ?? '';
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

/* ---------- Shared query for job cards ---------- */

const JOB_SELECT = 'SELECT j.*, c.name AS company_name, c.slug AS company_slug, c.color AS company_color,
                           c.shape AS company_shape, cat.name AS category_name, cat.slug AS category_slug
                    FROM jobs j
                    JOIN companies c ON c.id = j.company_id
                    JOIN categories cat ON cat.id = j.category_id';

/* ---------- Create a company with a unique slug, colour and logo shape ---------- */

function create_company(PDO $pdo, string $name, string $industry, string $location, string $about = ''): int {
    $base = slugify($name) ?: 'company';
    $slug = $base;
    $n = 2;
    $exists = $pdo->prepare('SELECT COUNT(*) FROM companies WHERE slug = ?');
    while (true) {
        $exists->execute([$slug]);
        if (!$exists->fetchColumn()) { break; }
        $slug = $base . '-' . $n++;
    }
    $palette = ['#1F5340', '#2B4C7E', '#8A3B5C', '#B5541C', '#3F5E5A', '#5B3F8C', '#9C6B00', '#2F6F8F'];
    $color = $palette[crc32($slug) % count($palette)];
    $shape = crc32($slug) % 4;
    $ins = $pdo->prepare('INSERT INTO companies (name, slug, industry, location, about, color, shape) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$name, $slug, $industry, $location, $about, $color, $shape]);
    return (int) $pdo->lastInsertId();
}
