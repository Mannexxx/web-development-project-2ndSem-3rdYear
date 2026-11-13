<?php

declare(strict_types=1);

namespace App\Service;

/**
 * BASIC face-verification check used by the registration flow.
 *
 * What it does: confirms three camera frames were received, that they are real,
 * reasonably lit images, and that the picture changed between frames (the person
 * followed the "turn your head" instructions instead of sending one still image).
 *
 * What it does NOT do: it does not recognise or match a face. For real biometric
 * matching, replace check() with a call to a face-recognition provider and keep
 * the same return shape.
 */
final class FaceVerifier
{
    private const MAX_FRAME_BYTES = 2_500_000;

    /**
     * @param string[] $frames raw JPEG bytes, one per liveness step
     * @return array{ok: bool, reason: ?string}
     */
    public function check(array $frames): array
    {
        if (count($frames) !== 3) {
            return $this->fail('We did not receive all of the camera frames. Please try again.');
        }

        $grids = [];
        foreach ($frames as $bytes) {
            if (!is_string($bytes) || $bytes === '' || strlen($bytes) > self::MAX_FRAME_BYTES) {
                return $this->fail('The camera image was missing or too large. Please try again.');
            }
            $info = @getimagesizefromstring($bytes);
            if ($info === false || $info[2] !== IMAGETYPE_JPEG || $info[0] < 160 || $info[1] < 160) {
                return $this->fail('The camera image was not valid. Please try again.');
            }
            if (function_exists('imagecreatefromstring')) {
                $grid = $this->luminanceGrid($bytes);
                if ($grid === null) {
                    return $this->fail('The camera image could not be read. Please try again.');
                }
                [$mean, $std] = $this->stats($grid);
                if ($mean < 20 || $mean > 238 || $std < 8) {
                    return $this->fail('We could not see you clearly. Move to a well-lit place, uncover the camera and try again.');
                }
                $grids[] = $grid;
            }
        }

        if (count($grids) === 3) {
            $d1 = $this->difference($grids[0], $grids[1]);
            $d2 = $this->difference($grids[0], $grids[2]);
            if ($d1 < 2.0 || $d2 < 2.0) {
                return $this->fail('We did not notice any head movement. Follow the on-screen instructions and try again.');
            }
        }

        return ['ok' => true, 'reason' => null];
    }

    private function fail(string $reason): array
    {
        return ['ok' => false, 'reason' => $reason];
    }

    /** @return float[]|null 24x24 grayscale values */
    private function luminanceGrid(string $bytes): ?array
    {
        $img = @imagecreatefromstring($bytes);
        if ($img === false) {
            return null;
        }
        $size = 24;
        $small = imagecreatetruecolor($size, $size);
        imagecopyresampled($small, $img, 0, 0, 0, 0, $size, $size, imagesx($img), imagesy($img));
        $grid = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $grid[] = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
            }
        }
        return $grid;
    }

    /** @return array{0: float, 1: float} mean and standard deviation */
    private function stats(array $grid): array
    {
        $n = count($grid);
        $mean = array_sum($grid) / $n;
        $var = 0.0;
        foreach ($grid as $v) {
            $var += ($v - $mean) ** 2;
        }
        return [$mean, sqrt($var / $n)];
    }

    private function difference(array $a, array $b): float
    {
        $sum = 0.0;
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $sum += abs($a[$i] - $b[$i]);
        }
        return $sum / $n;
    }
}
