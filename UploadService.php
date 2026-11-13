<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/** Safe handling of uploaded photos and report evidence. */
final class UploadService
{
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    private const EVIDENCE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

    /**
     * Turns the awkward $_FILES['name'] structure of a multi-file input into a list of single files.
     * Empty slots (no file chosen) are skipped.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function files(string $field): array
    {
        if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'] ?? null)) {
            if (!empty($_FILES[$field]) && is_string($_FILES[$field]['name'] ?? null)
                && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                return [$_FILES[$field]];
            }
            return [];
        }
        $out = [];
        foreach ($_FILES[$field]['name'] as $i => $name) {
            $err = $_FILES[$field]['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'type' => $_FILES[$field]['type'][$i] ?? '',
                'tmp_name' => $_FILES[$field]['tmp_name'][$i] ?? '',
                'error' => $err,
                'size' => $_FILES[$field]['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /** Photo inside the public uploads/ folder. Returns the path relative to the project root. */
    public function saveListingPhoto(array $file): string
    {
        [$mime] = $this->validate($file, self::IMAGE_TYPES, true);
        return $this->store($file, 'uploads/listings', self::IMAGE_TYPES[$mime]);
    }

    /**
     * Evidence goes into storage/ which the web server never serves directly.
     *
     * @return array{path: string, original: string, mime: string, size: int}
     */
    public function saveEvidence(array $file): array
    {
        [$mime] = $this->validate($file, self::EVIDENCE_TYPES, false);
        $path = $this->store($file, 'storage/evidence', self::EVIDENCE_TYPES[$mime]);
        return [
            'path' => $path,
            'original' => mb_substr(preg_replace('/[^\p{L}\p{N} ._\-]/u', '', (string) $file['name']) ?: 'evidence', 0, 120),
            'mime' => $mime,
            'size' => (int) $file['size'],
        ];
    }

    public function saveFaceImage(string $bytes): string
    {
        $dir = APP_ROOT . '/storage/faces';
        $this->ensureDir($dir);
        $name = bin2hex(random_bytes(16)) . '.jpg';
        if (file_put_contents($dir . '/' . $name, $bytes) === false) {
            throw new RuntimeException('Could not save the verification image.');
        }
        return 'storage/faces/' . $name;
    }

    public function delete(?string $relative): void
    {
        if ($relative === null || $relative === '' || str_contains($relative, '..')) {
            return;
        }
        if (!str_starts_with($relative, 'uploads/') && !str_starts_with($relative, 'storage/')) {
            return;
        }
        $abs = APP_ROOT . '/' . $relative;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    /** @return array{0: string} detected mime type */
    private function validate(array $file, array $allowed, bool $mustBeImage): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('"' . $this->label($file) . '" is larger than 5 MB.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('"' . $this->label($file) . '" could not be uploaded. Please try again.');
        }
        if ((int) $file['size'] > MAX_UPLOAD_BYTES) {
            throw new RuntimeException('"' . $this->label($file) . '" is larger than 5 MB.');
        }
        if (!is_uploaded_file((string) $file['tmp_name'])) {
            throw new RuntimeException('"' . $this->label($file) . '" was not a valid upload.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('"' . $this->label($file) . '" must be a ' . ($mustBeImage ? 'JPG or PNG image.' : 'JPG, PNG or PDF file.'));
        }
        if ($mime !== 'application/pdf' && @getimagesize((string) $file['tmp_name']) === false) {
            throw new RuntimeException('"' . $this->label($file) . '" is not a valid image.');
        }
        return [$mime];
    }

    private function store(array $file, string $relativeDir, string $ext): string
    {
        $dir = APP_ROOT . '/' . $relativeDir;
        $this->ensureDir($dir);
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('Could not save "' . $this->label($file) . '". Check folder permissions.');
        }
        return $relativeDir . '/' . $name;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Upload folder is missing and could not be created.');
        }
    }

    private function label(array $file): string
    {
        return mb_substr((string) ($file['name'] ?? 'File'), 0, 60);
    }
}
