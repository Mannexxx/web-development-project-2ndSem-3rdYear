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
if (is_post()) {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $auth = new AuthService(repo(UserRepository::class), repo(TownRepository::class));
    $result = $auth->attempt($email, (string) ($_POST['password'] ?? ''));
    if ($result['ok']) {
        AuthService::signIn((int) $result['user_id']);
        $user = current_user();
        if ($next !== '') {
            redirect($next);
        }
        redirect(is_admin($user) ? 'admin/index.php' : 'index.php');
    }
    $error = $result['error'];
}

render('login', ['error' => $error, 'email' => $email, 'next' => $next], [
    'title' => 'Sign in · ' . APP_NAME,
    'tab' => 'profile',
    'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Privacy & verification', 'privacy.php'], ['Community guidelines', 'guidelines.php'], ['Contact admin', 'support.php']]],
]);
