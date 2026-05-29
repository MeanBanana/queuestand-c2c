<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /ITECA_SumativeAssessment/login.php');
        exit;
    }
}

function denyAdmin(): void {
    if (isLoggedIn() && $_SESSION['role'] === 'admin') {
        header('Location: /ITECA_SumativeAssessment/admin/admin-dashboard.php');
        exit;
    }
}

function requireRole(string $role): void {
    if (!isLoggedIn()) {
        $redirect = $role === 'admin'
            ? '/ITECA_SumativeAssessment/admin/admin-login.php'
            : '/ITECA_SumativeAssessment/login.php';
        header('Location: ' . $redirect);
        exit;
    }
    if ($_SESSION['role'] !== $role) {
        header('Location: /ITECA_SumativeAssessment/index.php');
        exit;
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
