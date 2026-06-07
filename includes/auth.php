<?php
if (session_status() === PHP_SESSION_NONE) {
<<<<<<< HEAD
    session_start();
}

// Security headers (local only — InfinityFree proxy handles these on production)
if (IS_LOCAL) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
=======
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false, // set true in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/config.php';

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
>>>>>>> parent of 3307ee2 (fix session tokens with cookies)


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
