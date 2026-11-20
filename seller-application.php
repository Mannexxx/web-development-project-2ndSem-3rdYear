<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\SellerApplicationRepository;
use App\Service\DiditService;
use App\Service\SellerDocumentRetentionService;
use App\Service\UploadService;

$me = require_verified('apply to sell');
$userId = (int) $me['id'];
$applications = repo(SellerApplicationRepository::class);
$uploader = new UploadService();
$didit = new DiditService();
$diditConfigured = $didit->configured();
(new SellerDocumentRetentionService($applications, $uploader))->purgeExpired();
$application = $applications->forUser($userId);

if ($application !== null && $application['status'] === 'approved' && $application['didit_status'] === 'Approved') {
    redirect('sell.php');
}

$idTypes = [
    'national_id' => 'Philippine National ID',
    'passport' => 'Passport',
    'drivers_license' => 'Driver’s license',
    'postal_id' => 'Postal ID',
    'voters_id' => 'Voter’s ID',
    'umid' => 'UMID',
    'prc_id' => 'PRC ID',
    'philhealth_id' => 'PhilHealth ID',
    'sss_id' => 'SSS ID',
    'other' => 'Other valid government ID',
];
$agreementVersion = 'seller-identity-v1';
$values = $application ?? [];
$errors = [];
$old = [];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'start_verification') {
        if ($application === null || $application['status'] !== 'pending') {
            abort(403, 'Submit a seller application before starting identity verification.');
        }
        try {
            $session = $didit->createSession($application, $me);
            $applications->setDiditSession((int) $application['id'], $session['session_id'], $session['status']);
            redirect($session['url']);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('seller-application.php');
        }
    }

    if ($action !== 'submit_application' || ($application !== null && !in_array($application['status'], ['rejected', 'changes_requested'], true))) {
        abort(403, 'This seller application cannot be changed right now.');
    }
    if (!$diditConfigured) {
        flash('error', 'Seller identity verification is not ready yet. Please contact the marketplace admin.');
        redirect('seller-application.php');
    }

    $old = $_POST;
    $first = trim((string) ($_POST['first_name'] ?? ''));
    $middle = trim((string) ($_POST['middle_name'] ?? ''));
    $last = trim((string) ($_POST['last_name'] ?? ''));
    $sex = (string) ($_POST['sex'] ?? '');
    $dobRaw = trim((string) ($_POST['date_of_birth'] ?? ''));
    $dob = \DateTimeImmutable::createFromFormat('!Y-m-d', $dobRaw);
    $dobErrors = \DateTimeImmutable::getLastErrors();
    $address = [
        'house_number' => trim((string) ($_POST['house_number'] ?? '')),
        'street_village' => trim((string) ($_POST['street_village'] ?? '')),
        'barangay' => trim((string) ($_POST['barangay'] ?? '')),
        'municipality' => trim((string) ($_POST['municipality'] ?? '')),
    ];
    $idType = (string) ($_POST['id_type'] ?? '');

    foreach (['First name' => [$first, 80], 'Middle name' => [$middle, 80], 'Last name' => [$last, 80]] as $label => [$value, $max]) {
        if ($label === 'Middle name' && $value === '') {
            continue;
        }
        if ($value === '' || mb_strlen($value) > $max) {
            $errors[] = $label . ' is required and must be 80 characters or fewer.';
        }
    }
    if (!in_array($sex, ['female', 'male', 'other'], true)) {
        $errors[] = 'Select your sex.';
    }
    if ($dob === false || ($dobErrors !== false && ($dobErrors['warning_count'] > 0 || $dobErrors['error_count'] > 0))
        || $dob->format('Y-m-d') !== $dobRaw || $dob >= (new \DateTimeImmutable('today'))->modify('-18 years')) {
        $errors[] = 'Enter a valid date of birth. Applicants must be older than 18.';
    }
    foreach ($address as $label => $value) {
        if ($value === '' || mb_strlen($value) > ($label === 'house_number' ? 80 : 120)) {
            $errors[] = 'Complete every address field and keep it within the allowed length.';
            break;
        }
    }
    if (!isset($idTypes[$idType])) {
        $errors[] = 'Select a valid ID type.';
    }
    foreach (['terms', 'privacy', 'guidelines', 'biometric_consent'] as $requiredAgreement) {
        if (empty($_POST[$requiredAgreement])) {
            $errors[] = 'Read and accept the Terms, Privacy Policy, Community Guidelines, and the separate biometric verification consent.';
            break;
        }
    }

    $files = [];
    foreach (['id_front' => 'Front of ID', 'id_back' => 'Back of ID'] as $key => $label) {
        $file = $_FILES[$key] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Upload the ' . $label . ' image.';
            continue;
        }
        $files[$key] = $file;
    }

    if ($errors === []) {
        $newFiles = [];
        try {
            $frontPath = $uploader->saveSellerIdImage($files['id_front']);
            $newFiles[] = $frontPath;
            $backPath = $uploader->saveSellerIdImage($files['id_back']);
            $newFiles[] = $backPath;

            $data = [
                'first_name' => $first,
                'middle_name' => $middle !== '' ? $middle : null,
                'last_name' => $last,
                'sex' => $sex,
                'date_of_birth' => $dobRaw,
                'house_number' => $address['house_number'],
                'street_village' => $address['street_village'],
                'barangay' => $address['barangay'],
                'municipality' => $address['municipality'],
                'id_type' => $idType,
                'id_front_path' => $frontPath,
                'id_back_path' => $backPath,
                'accepted_at' => now(),
                'agreement_version' => $agreementVersion,
            ];

            if ($application === null) {
                $applicationId = $applications->create($userId, $data);
            } else {
                $applicationId = (int) $application['id'];
                $applications->resubmit($applicationId, $data);
                $uploader->delete($application['id_front_path']);
                $uploader->delete($application['id_back_path']);
            }

            $freshApplication = $applications->find($applicationId);
            if ($freshApplication === null) {
                throw new RuntimeException('The submitted seller application could not be loaded.');
            }
            try {
                $session = $didit->createSession($freshApplication, $me);
                $applications->setDiditSession($applicationId, $session['session_id'], $session['status']);
                redirect($session['url']);
            } catch (RuntimeException $e) {
                flash('error', 'Your application was received, but identity verification could not start: ' . $e->getMessage());
                redirect('seller-application.php');
            }
        } catch (RuntimeException $e) {
            foreach ($newFiles as $path) {
                $uploader->delete($path);
            }
            $errors[] = $e->getMessage();
        }
    }
}

$form = [
    'first_name' => $old['first_name'] ?? ($values['first_name'] ?? $me['firstname']),
    'middle_name' => $old['middle_name'] ?? ($values['middle_name'] ?? ''),
    'last_name' => $old['last_name'] ?? ($values['last_name'] ?? $me['lastname']),
    'sex' => $old['sex'] ?? ($values['sex'] ?? ''),
    'date_of_birth' => $old['date_of_birth'] ?? ($values['date_of_birth'] ?? ''),
    'house_number' => $old['house_number'] ?? ($values['house_number'] ?? ''),
    'street_village' => $old['street_village'] ?? ($values['street_village'] ?? ''),
    'barangay' => $old['barangay'] ?? ($values['barangay'] ?? ''),
    'municipality' => $old['municipality'] ?? ($values['municipality'] ?? ''),
    'id_type' => $old['id_type'] ?? ($values['id_type'] ?? ''),
];
$mayResubmit = $application === null || in_array($application['status'], ['rejected', 'changes_requested'], true);

render('seller-application', [
    'application' => $application,
    'form' => $form,
    'errors' => $errors,
    'idTypes' => $idTypes,
    'mayResubmit' => $mayResubmit,
    'diditConfigured' => $diditConfigured,
], [
    'title' => 'Seller application · ' . APP_NAME,
    'tab' => 'sell',
    'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Terms & privacy', 'privacy.php'], ['Community guidelines', 'guidelines.php'], ['Contact admin', 'support.php']]],
]);
