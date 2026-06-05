<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Use DB-backed sessions on production (InfinityFree blocks filesystem sessions)
    if (!IS_LOCAL) {
        ini_set('session.save_handler', 'user');
        $pdo_sess = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo_sess->exec("
            CREATE TABLE IF NOT EXISTS php_sessions (
                session_id VARCHAR(128) PRIMARY KEY,
                data TEXT NOT NULL,
                updated_at INT NOT NULL
            )
        ");
        session_set_save_handler(
            fn() => true,
            fn() => true,
            function($id) use ($pdo_sess) {
                $s = $pdo_sess->prepare('SELECT data FROM php_sessions WHERE session_id=? AND updated_at > ?');
                $s->execute([$id, time() - 86400]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                return $r ? $r['data'] : '';
            },
            function($id, $data) use ($pdo_sess) {
                $s = $pdo_sess->prepare('REPLACE INTO php_sessions (session_id,data,updated_at) VALUES (?,?,?)');
                $s->execute([$id, $data, time()]);
                return true;
            },
            function($id) use ($pdo_sess) {
                $pdo_sess->prepare('DELETE FROM php_sessions WHERE session_id=?')->execute([$id]);
                return true;
            },
            function($max) use ($pdo_sess) {
                $pdo_sess->prepare('DELETE FROM php_sessions WHERE updated_at < ?')->execute([time() - $max]);
                return true;
            }
        );
        register_shutdown_function('session_write_close');
    }
    session_start();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');


function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * guardRoute — single entry point for all access control.
 *
 * Page types:
 *   'public'  — guests only (login, register). Logged-in users are redirected to their home.
 *   'user'    — logged-in non-admin users only.
 *   'admin'   — logged-in admins only.
 *   'open'    — anyone can view, but admins are redirected to admin portal.
 */
function guardRoute(string $pageType): void {
    $loggedIn = isLoggedIn();
    $role     = $_SESSION['role'] ?? '';

    switch ($pageType) {
        case 'public':
            // Already logged in — send to their home
            if ($loggedIn) {
                header('Location: ' . ($role === 'admin'
                    ? BASE_URL . '/admin/admin-dashboard.php'
                    : BASE_URL . '/dashboard.php'));
                exit;
            }
            break;

        case 'user':
            // Must be logged in
            if (!$loggedIn) {
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            }
            // Admins have no business here
            if ($role === 'admin') {
                header('Location: ' . BASE_URL . '/admin/admin-dashboard.php');
                exit;
            }
            break;

        case 'admin':
            // Must be logged in
            if (!$loggedIn) {
                header('Location: ' . BASE_URL . '/admin/admin-login.php');
                exit;
            }
            // Non-admins are sent to their dashboard
            if ($role !== 'admin') {
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            }
            break;

        case 'open':
            // Admins should not browse the public site and users should not browse admin site
            if ($loggedIn && $role === 'admin') {
                header('Location: ' . BASE_URL . '/admin/admin-dashboard.php');
                exit;
            }
            break;
    }
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function currentUser(): array {
    return [
        'id'         => $_SESSION['user_id']   ?? null,
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name'  => $_SESSION['last_name']  ?? '',
        'role'       => $_SESSION['role']       ?? '',
    ];
}
