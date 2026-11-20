<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\TownRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;

$next = safe_next($_GET['next'] ?? $_GET['state_next'] ?? '', '');

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '' || GOOGLE_REDIRECT_URI === '') {
    flash('error', 'Google sign-in is not configured yet. Contact the administrator.');
    redirect('login.php');
}

$redirectUri = GOOGLE_REDIRECT_URI;

if (!isset($_GET['code'])) {
    $state = bin2hex(random_bytes(24));
    $_SESSION['google_oauth_state'] = $state;
    $_SESSION['google_oauth_next'] = $next;

    $params = http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'prompt' => 'select_account',
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

if (!isset($_GET['state'], $_SESSION['google_oauth_state'])
    || !hash_equals((string) $_SESSION['google_oauth_state'], (string) $_GET['state'])) {
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_next']);
    flash('error', 'Google sign-in could not be verified. Please try again.');
    redirect('login.php');
}

$callbackNext = (string) ($_SESSION['google_oauth_next'] ?? '');
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_next']);

$tokenPayload = http_build_query([
    'code' => (string) $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
]);

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $tokenPayload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
]);
$tokenRaw = curl_exec($ch);
$tokenStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$token = is_string($tokenRaw) ? json_decode($tokenRaw, true) : null;
if ($tokenStatus !== 200 || !is_array($token) || empty($token['access_token'])) {
    flash('error', 'Google sign-in failed. Please try again.');
    redirect('login.php');
}

$ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . (string) $token['access_token'], 'Accept: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
]);
$profileRaw = curl_exec($ch);
$profileStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$profile = is_string($profileRaw) ? json_decode($profileRaw, true) : null;
$email = is_array($profile) ? strtolower(trim((string) ($profile['email'] ?? ''))) : '';
$emailVerified = is_array($profile) && !empty($profile['email_verified']);
if ($profileStatus !== 200 || !is_array($profile) || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified) {
    flash('error', 'Google did not provide a verified email address.');
    redirect('login.php');
}

$users = repo(UserRepository::class);
$user = $users->findByEmail($email);
if ($user === null) {
    $first = mb_substr(trim((string) ($profile['given_name'] ?? '')), 0, 80);
    $last = mb_substr(trim((string) ($profile['family_name'] ?? '')), 0, 80);
    if ($first === '') {
        $first = 'Google';
    }
    if ($last === '') {
        $last = 'Member';
    }

    $base = strtolower(preg_replace('/[^a-z0-9._]/', '', strstr($email, '@', true) ?: '') ?? '');
    if (strlen($base) < 3) {
        $base = 'google';
    }
    $base = substr($base, 0, 32);
    $username = $base;
    $suffix = 1;
    while ($users->usernameExists($username)) {
        $username = substr($base, 0, 32 - strlen((string) $suffix)) . $suffix;
        $suffix++;
    }

    $defaultTown = repo(TownRepository::class)->all()[0]['id'] ?? 1;
    $userId = $users->create(
        $first,
        $last,
        $username,
        $email,
        null,
        null,
        password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
        (int) $defaultTown
    );
    $users->markEmailVerified($userId);
} else {
    $userId = (int) $user['id'];
    $users->markEmailVerified($userId);
}

AuthService::signIn($userId);
$signedIn = current_user();
redirect($callbackNext !== '' ? $callbackNext : (is_admin($signedIn) ? 'admin/index.php' : 'index.php'));
