<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    /**
     * Preprocess an image for better OCR accuracy, then extract raw text.
     *
     * @return array{success: bool, text: string, message: string|null}
     */
    public function extractText(string $imagePath): array
    {
        try {
            $processedPath = $this->preprocessImage($imagePath);

            $text = $this->runOcr($processedPath);

            if ($processedPath !== $imagePath && file_exists($processedPath)) {
                @unlink($processedPath);
            }

            if ($text === '') {
                return [
                    'success' => false,
                    'text' => '',
                    'message' => 'We could not extract all information from the document. Please check the image quality or enter the missing information manually.',
                ];
            }

            return [
                'success' => true,
                'text' => $text,
                'message' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('OCR processing failed', [
                'error' => $e->getMessage(),
                'image' => $imagePath,
            ]);

            return [
                'success' => false,
                'text' => '',
                'message' => 'We could not extract all information from the document. Please check the image quality or enter the missing information manually.',
            ];
        }
    }

    /**
     * Run Tesseract with eng+urd when available (Pakistani CNIC support).
     */
    protected function runOcr(string $imagePath): string
    {
        $languages = config('ocr.languages', ['eng', 'urd']);
        if (! is_array($languages) || $languages === []) {
            $languages = [config('ocr.language', 'eng')];
        }

        $attempts = [
            implode('+', $languages),
            'eng',
        ];

        $lastError = null;

        foreach (array_unique($attempts) as $lang) {
            try {
                $ocr = new TesseractOCR($imagePath);
                $ocr->lang($lang);
                $ocr->psm(6); // Assume a uniform block of text (good for ID cards)

                $tesseractPath = config('ocr.tesseract_path');
                if (! empty($tesseractPath)) {
                    $ocr->executable($tesseractPath);
                }

                $text = trim((string) $ocr->run());
                if ($text !== '') {
                    return $text;
                }
            } catch (\Throwable $e) {
                $lastError = $e;
                Log::info('OCR language attempt failed', [
                    'lang' => $lang,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($lastError) {
            throw $lastError;
        }

        return '';
    }

    /**
     * Simple image preprocessing tuned for CNIC photos.
     */
    protected function preprocessImage(string $imagePath): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $imagePath;
        }

        $contents = @file_get_contents($imagePath);
        if ($contents === false) {
            return $imagePath;
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return $imagePath;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Upscale small phone photos; cap very large images
        $targetWidth = 1800;
        if ($width < 1000) {
            $scale = 1000 / max($width, 1);
            $newWidth = (int) ($width * $scale);
            $newHeight = (int) ($height * $scale);
        } elseif ($width > $targetWidth) {
            $newWidth = $targetWidth;
            $newHeight = (int) ($height * ($targetWidth / $width));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        if ($newWidth !== $width || $newHeight !== $height) {
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);
        imagefilter($image, IMG_FILTER_CONTRAST, -20);
        imagefilter($image, IMG_FILTER_BRIGHTNESS, 8);

        $processedPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ocr_'.uniqid('', true).'.png';
        imagepng($image, $processedPath);
        imagedestroy($image);

        return $processedPath;
    }
}
