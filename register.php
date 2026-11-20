<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\TownRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
<<<<<<< HEAD
use App\Service\VerificationService;
=======
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073

$towns = repo(TownRepository::class)->all();
$next = safe_next($_POST['next'] ?? $_GET['next'] ?? '', '');
$me = current_user();
$errors = [];
$old = [];
<<<<<<< HEAD
$verificationError = null;
$verificationSent = false;
$verificationChannel = 'email';

if ($me !== null) {
    $step = (string) ($_GET['step'] ?? 'verify_contact');
    $verificationChannel = 'email';
    $destination = (string) ($me['email'] ?? '');
    $alreadyVerified = !empty($me['email_verified_at']);

    if (is_post()) {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $service = new VerificationService(repo(UserRepository::class));
        if ($action === 'send_code') {
            $delivery = $service->send((int) $me['id'], $verificationChannel, $destination, $me['phone_carrier'] ?? null);
            if ($delivery['ok']) {
                $verificationSent = true;
                flash('success', 'A new verification code was sent.');
            } else {
                $verificationError = $delivery['error'];
            }
        } elseif ($action === 'verify_code') {
            $result = $service->verify((int) $me['id'], $verificationChannel, trim((string) ($_POST['code'] ?? '')));
            if ($result['ok']) {
                $me = current_user(true);
                flash('success', 'Email verified. Your account is ready.');
                redirect($next !== '' ? $next : 'index.php');
            }
            $verificationError = $result['error'];
        }
    }

    if ($alreadyVerified && $step === 'verify_contact') {
        redirect($next !== '' ? $next : 'index.php');
    }
=======

if ($me !== null) {
    if (is_verified($me) || is_admin($me)) {
        flash('info', 'Your profile is already verified.');
        redirect($next !== '' ? $next : 'index.php');
    }
    $step = 'verify';
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
} else {
    $step = 'create';
    if (is_post()) {
        verify_csrf();
        $old = $_POST;
        $auth = new AuthService(repo(UserRepository::class), repo(TownRepository::class));
        $result = $auth->register($_POST);
        if ($result['errors'] === []) {
<<<<<<< HEAD
            $fresh = repo(UserRepository::class)->find((int) $result['user_id']);
            if ($fresh === null) {
                throw new \RuntimeException('The newly created account could not be loaded.');
            }
            $verificationChannel = 'email';
            $destination = (string) $fresh['email'];
            $delivery = (new VerificationService(repo(UserRepository::class)))->send(
                (int) $fresh['id'],
                $verificationChannel,
                $destination,
                $fresh['phone_carrier'] ?? null
            );
            if (!$delivery['ok']) {
                flash('error', $delivery['error']);
            } else {
                flash('success', 'Account created. Sign in to verify your email.');
            }
            redirect('login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''));
=======
            AuthService::signIn((int) $result['user_id']);
            flash('success', 'Account created. Complete face verification to message sellers and sell.');
            redirect('register.php?step=verify' . ($next !== '' ? '&next=' . rawurlencode($next) : ''));
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
        }
        $errors = $result['errors'];
    }
}

<<<<<<< HEAD
if ($me === null) {
    render('login', [
        'error' => null,
        'email' => '',
        'next' => $next,
        'mode' => 'register',
        'towns' => $towns,
        'errors' => $errors,
        'old' => $old,
    ], [
        'title' => 'Join the island community · ' . APP_NAME,
        'tab' => 'profile',
        'hide_header' => true,
        'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Privacy & verification', 'privacy.php#verification'], ['Community guidelines', 'guidelines.php'], ['Contact admin', 'support.php']]],
    ]);
    return;
}

render('register', [
    'step' => $step,
    'towns' => $towns,
    'errors' => $errors,
    'old' => $old,
    'next' => $next,
    'verificationChannel' => $verificationChannel,
    'verificationDestination' => $destination ?? '',
    'verificationError' => $verificationError,
    'verificationSent' => $verificationSent,
    'verificationCarrier' => $me['phone_carrier'] ?? ($old['phone_carrier'] ?? ''),
], [
    'title' => 'Verify your account · ' . APP_NAME,
    'tab' => 'profile',
    'hide_header' => true,
=======
render('register', [
    'step' => $step, 'towns' => $towns, 'errors' => $errors, 'old' => $old, 'next' => $next,
], [
    'title' => 'Join the island community · ' . APP_NAME,
    'tab' => 'profile',
    'scripts' => ['face.js'],
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
    'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Privacy & verification', 'privacy.php#verification'], ['Community guidelines', 'guidelines.php'], ['Contact admin', 'support.php']]],
]);
