<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\TownRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;

$towns = repo(TownRepository::class)->all();
$next = safe_next($_POST['next'] ?? $_GET['next'] ?? '', '');
$me = current_user();
$errors = [];
$old = [];

if ($me !== null) {
    if (is_verified($me) || is_admin($me)) {
        flash('info', 'Your profile is already verified.');
        redirect($next !== '' ? $next : 'index.php');
    }
    $step = 'verify';
} else {
    $step = 'create';
    if (is_post()) {
        verify_csrf();
        $old = $_POST;
        $auth = new AuthService(repo(UserRepository::class), repo(TownRepository::class));
        $result = $auth->register($_POST);
        if ($result['errors'] === []) {
            AuthService::signIn((int) $result['user_id']);
            flash('success', 'Account created. Complete face verification to message sellers and sell.');
            redirect('register.php?step=verify' . ($next !== '' ? '&next=' . rawurlencode($next) : ''));
        }
        $errors = $result['errors'];
    }
}

render('register', [
    'step' => $step, 'towns' => $towns, 'errors' => $errors, 'old' => $old, 'next' => $next,
], [
    'title' => 'Join the island community · ' . APP_NAME,
    'tab' => 'profile',
    'scripts' => ['face.js'],
    'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Privacy & verification', 'privacy.php#verification'], ['Community guidelines', 'guidelines.php'], ['Contact admin', 'support.php']]],
]);
