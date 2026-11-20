<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\DealRepository;
use App\Repository\ListingRepository;
use App\Repository\NotificationRepository;
use App\Repository\SupportRepository;
use App\Repository\UserRepository;
use App\Repository\TownRepository;
use App\Service\AuthService;
use App\Service\UploadService;
use App\Service\VerificationService;

$me = require_login('Please sign in to see your account.');
$uid = (int) $me['id'];
$notes = repo(NotificationRepository::class);
$users = repo(UserRepository::class);
$towns = repo(TownRepository::class)->all();
$profileErrors = [];
$section = (string) ($_GET['section'] ?? '');
if (!in_array($section, ['edit-profile', 'settings'], true) || (is_admin($me) && $section === 'settings')) {
    $section = '';
}

if (is_post()) {
    verify_csrf();
    $continueRendering = false;
    if (($_POST['action'] ?? '') === 'read_notifications') {
        $notes->markAllRead($uid);
        redirect('account.php#notifications');
    } elseif (($_POST['action'] ?? '') === 'deactivate_account') {
        if (is_admin($me)) {
            flash('error', 'Admin accounts cannot be deactivated here.');
            redirect('account.php?section=settings');
        }
        if (($_POST['confirm_deactivation'] ?? '') !== '1') {
            flash('error', 'Confirm the account deactivation before continuing.');
            redirect('account.php?section=settings');
        }
        try {
            $removedFiles = $users->deactivateAccount($uid);
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('account.php?section=settings');
        } catch (\PDOException $e) {
            error_log('Account deactivation failed for user ' . $uid . ': ' . $e->getMessage());
            flash('error', 'Your account could not be deactivated. Please try again or contact support.');
            redirect('account.php?section=settings');
        }
        $uploads = new UploadService();
        foreach ($removedFiles as $file) {
            $uploads->delete($file);
        }
        AuthService::signOut();
        flash('success', 'Your account has been deactivated and your personal details anonymized.');
        redirect('login.php');
    } elseif (($_POST['action'] ?? '') === 'update_profile') {
        $section = 'edit-profile';
        $first = mb_substr(trim((string) ($_POST['firstname'] ?? '')), 0, 80);
        $last = mb_substr(trim((string) ($_POST['lastname'] ?? '')), 0, 80);
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $townId = (int) ($_POST['town_id'] ?? 0);
        $namePattern = '/^[\p{L}][\p{L} .\'\-]*$/u';

        if (!preg_match($namePattern, $first)) {
            $profileErrors[] = 'Please enter a valid first name.';
        }
        if (!preg_match($namePattern, $last)) {
            $profileErrors[] = 'Please enter a valid last name.';
        }
        if (!preg_match('/^[a-z0-9._]{3,40}$/', $username)) {
            $profileErrors[] = 'Username must be 3-40 characters and use only letters, numbers, dots or underscores.';
        } elseif ($users->usernameExistsExcept($username, $uid)) {
            $profileErrors[] = 'That username is already used by another account.';
        }
        if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = 'Please enter a valid email address.';
        } elseif ($users->emailExistsExcept($email, $uid)) {
            $profileErrors[] = 'That email address is already used by another account.';
        }
        if (repo(TownRepository::class)->find($townId) === null) {
            $profileErrors[] = 'Please select a valid town.';
        }

        $profilePic = $me['profile_pic'];
        $sellerBackground = $me['seller_background'] ?? null;
        $uploads = new UploadService();
        $newProfilePic = null;
        $newSellerBackground = null;
        if (($_POST['remove_seller_background'] ?? '') === '1') {
            $sellerBackground = null;
        }
        if (!empty($_FILES['profile_picture']['name']) && $profileErrors === []) {
            try {
                $newProfilePic = $uploads->saveProfilePicture($_FILES['profile_picture']);
                $profilePic = $newProfilePic;
            } catch (\RuntimeException $e) {
                $profileErrors[] = $e->getMessage();
            }
        }
        if (!empty($_FILES['seller_background']['name']) && $profileErrors === []) {
            try {
                $newSellerBackground = $uploads->saveSellerBackground($_FILES['seller_background']);
                $sellerBackground = $newSellerBackground;
            } catch (\RuntimeException $e) {
                $profileErrors[] = $e->getMessage();
            }
        }

        if ($profileErrors === []) {
            $emailChanged = $email !== strtolower((string) ($me['email'] ?? ''));
            $oldProfilePic = $me['profile_pic'];
            $oldSellerBackground = $me['seller_background'] ?? null;
            try {
                $users->updateProfile($uid, $first, $last, $username, $email, $townId, $profilePic, $sellerBackground, $emailChanged);
            } catch (\Throwable $e) {
                $uploads->delete($newProfilePic);
                $uploads->delete($newSellerBackground);
                throw $e;
            }
            if ($profilePic !== $oldProfilePic) {
                $uploads->delete($oldProfilePic);
            }
            if ($sellerBackground !== $oldSellerBackground) {
                $uploads->delete($oldSellerBackground);
            }
            $me = current_user(true);
            if ($emailChanged) {
                $delivery = (new VerificationService($users))->send($uid, 'email', $email);
                flash($delivery['ok'] ? 'success' : 'error',
                    $delivery['ok'] ? 'Profile updated. Enter the verification code sent to your new email.' : 'Profile updated, but ' . $delivery['error']);
                redirect('register.php?step=verify_contact');
            }
            flash('success', 'Profile updated.');
            redirect('account.php');
        }
        $uploads->delete($newProfilePic);
        $uploads->delete($newSellerBackground);
        $continueRendering = true;
    }
    if (!$continueRendering) {
        redirect('account.php#notifications');
    }
}

$notifications = $notes->forUser($uid, 15);
$pageTitle = match ($section) {
    'edit-profile' => 'Edit profile · ' . APP_NAME,
    'settings' => 'Account settings · ' . APP_NAME,
    default => 'My account · ' . APP_NAME,
};

render('account', [
    'ratings' => $users->ratings($uid),
    'myListings' => repo(ListingRepository::class)->forSeller($uid),
    'deals' => repo(DealRepository::class)->forUser($uid),
    'tickets' => repo(SupportRepository::class)->forUser($uid),
    'notifications' => $notifications,
    'unreadNotes' => $notes->unreadCount($uid),
    'towns' => $towns,
    'profileErrors' => $profileErrors,
    'section' => $section,
], ['title' => $pageTitle, 'tab' => 'profile']);
