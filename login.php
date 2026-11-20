<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\TownRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;

$next = safe_next($_POST['next'] ?? $_GET['next'] ?? '', '');

if (current_user() !== null) {
    redirect($next !== '' ? $next : 'index.php');
}

$error = null;
$email = '';
$mode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';
$towns = repo(TownRepository::class)->all();
if (is_post()) {
    verify_csrf();
    $users = repo(UserRepository::class);
    $email = trim((string) ($_POST['email'] ?? ''));
    $auth = new AuthService($users, repo(TownRepository::class));
    $result = $auth->attempt($email, (string) ($_POST['password'] ?? ''));
    if ($result['ok']) {
        AuthService::signIn((int) $result['user_id']);
        $user = current_user();
        if (!is_admin($user) && empty($user['email_verified_at'])) {
            redirect('register.php?step=verify_contact' . ($next !== '' ? '&next=' . rawurlencode($next) : ''));
        }
        if ($next !== '') {
            redirect($next);
        }
        redirect(is_admin($user) ? 'admin/index.php' : 'index.php');
    }
    $error = $result['error'];
}

render('login', [
    'error' => $error,
    'email' => $email,
    'next' => $next,
    'mode' => $mode,
    'towns' => $towns,
    'errors' => [],
    'old' => [],
], [
    'title' => ($mode === 'register' ? 'Join the island community · ' : 'Sign in · ') . APP_NAME,
    'tab' => 'profile',
    'hide_header' => true,
    'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Privacy & verification', 'privacy.php'], ['Community guidelines', 'guidelines.php'], ['Contact admin', 'support.php']]],
]);
