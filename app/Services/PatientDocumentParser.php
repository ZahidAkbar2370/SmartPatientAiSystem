<?php

namespace App\Services;

/**
 * Extracts patient fields from Pakistani CNIC OCR text.
 *
 * Supported CNIC layouts in Pakistan:
 * 1. Old Urdu-only CNIC
 * 2. New bilingual Smart Card (chip) — English + Urdu
 * 3. New bilingual CNIC (photo left) — English + Urdu
 */
class PatientDocumentParser
{
    /**
     * @return array{
     *     full_name: string|null,
     *     father_husband_name: string|null,
     *     cnic: string|null,
     *     date_of_birth: string|null,
     *     gender: string|null,
     *     address: string|null,
     *     document_format: string|null,
     *     extracted_count: int
     * }
     */
    public function parse(string $ocrText, string $documentType = 'cnic'): array
    {
        $text = $this->normalizeText($ocrText);
        $format = $this->detectCnicFormat($text);

        if ($documentType === 'cnic') {
            $data = match ($format) {
                'urdu_only' => $this->parseUrduOnlyCnic($text),
                'bilingual_smart', 'bilingual' => $this->parseBilingualCnic($text),
                default => $this->parseGenericCnic($text),
            };
        } else {
            $data = $this->parseGenericCnic($text);
        }

        $data['document_format'] = $format;
        $data['extracted_count'] = count(array_filter([
            $data['full_name'] ?? null,
            $data['father_husband_name'] ?? null,
            $data['cnic'] ?? null,
            $data['date_of_birth'] ?? null,
            $data['gender'] ?? null,
            $data['address'] ?? null,
        ], fn ($value) => filled($value)));

        return $data;
    }

    /**
     * Detect which of the 3 Pakistani CNIC layouts the OCR text matches.
     */
    protected function detectCnicFormat(string $text): string
    {
        $hasEnglishLabels = (bool) preg_match(
            '/\b(Name|Father Name|Father\'?s Name|Identity Number|Date of Birth|Gender|Country of Stay)\b/i',
            $text
        );

        $hasUrduLabels = (bool) preg_match('/نام|جنس|والد|تاریخ پیدائش|شناختی/', $text);
        $hasEnglishBody = (bool) preg_match('/\b(PAKISTAN|National Identity Card|ISLAMIC REPUBLIC)\b/i', $text);
        $hasChipHints = (bool) preg_match('/\b(Country of Stay|Identity Number)\b/i', $text);

        if ($hasEnglishLabels || $hasEnglishBody) {
            return $hasChipHints ? 'bilingual_smart' : 'bilingual';
        }

        if ($hasUrduLabels || preg_match('/حکومت|قومی شناختی/', $text)) {
            return 'urdu_only';
        }

        // Fallback: CNIC number present with Latin name patterns → bilingual-like
        if ($this->extractCnic($text) && preg_match('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)+\b/', $text)) {
            return 'bilingual';
        }

        return 'unknown';
    }

    /**
     * Format 1 — Old Urdu-only CNIC.
     * Labels: نام، جنس، والد کا نام، تاریخ پیدائش
     * CNIC & DOB remain in Latin digits.
     */
    protected function parseUrduOnlyCnic(string $text): array
    {
        return [
            'full_name' => $this->extractUrduLabeledValue($text, ['نام']) 
                ?? $this->extractName($text),
            'father_husband_name' => $this->extractUrduLabeledValue($text, ['والد کا نام', 'والد', 'زوج'])
                ?? $this->extractFatherHusbandName($text),
            'cnic' => $this->extractCnic($text),
            'date_of_birth' => $this->extractUrduDateOfBirth($text) ?? $this->extractDateOfBirth($text),
            'gender' => $this->extractUrduGender($text) ?? $this->extractGender($text),
            'address' => null,
        ];
    }

    /**
     * Formats 2 & 3 — Bilingual English + Urdu CNIC / Smart Card.
     * Prefer English values for the patient form.
     */
    protected function parseBilingualCnic(string $text): array
    {
        return [
            'full_name' => $this->extractEnglishName($text) ?? $this->extractName($text),
            'father_husband_name' => $this->extractEnglishFatherName($text) ?? $this->extractFatherHusbandName($text),
            'cnic' => $this->extractIdentityNumber($text) ?? $this->extractCnic($text),
            'date_of_birth' => $this->extractLabeledDateOfBirth($text) ?? $this->extractDateOfBirth($text),
            'gender' => $this->extractGender($text),
            'address' => $this->extractAddress($text, 'cnic'),
        ];
    }

    /**
     * Generic fallback for driving license or unrecognized OCR.
     */
    protected function parseGenericCnic(string $text): array
    {
        return [
            'full_name' => $this->extractEnglishName($text) ?? $this->extractName($text),
            'father_husband_name' => $this->extractEnglishFatherName($text) ?? $this->extractFatherHusbandName($text),
            'cnic' => $this->extractIdentityNumber($text) ?? $this->extractCnic($text),
            'date_of_birth' => $this->extractLabeledDateOfBirth($text) ?? $this->extractDateOfBirth($text),
            'gender' => $this->extractGender($text) ?? $this->extractUrduGender($text),
            'address' => $this->extractAddress($text, 'cnic'),
        ];
    }

    protected function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Normalize common OCR confusions near CNIC digits
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function extractEnglishName(string $text): ?string
    {
        // Bilingual cards: "Name" → Urdu line → English line
        if (preg_match(
            '/(?:^|\n)\s*Name\s*[:\-]?\s*(.+?)(?=\n\s*Father|\n\s*Gender|\n\s*Country|\n\s*Identity|\n\s*Date\s*of|\n\s*نام|\z)/isu',
            $text,
            $block
        )) {
            $latin = $this->firstLatinPersonName($block[1]);
            if ($latin) {
                return $latin;
            }
        }

        if (preg_match('/(?:^|\n)\s*Name\s*[:\-]?\s*([A-Za-z][A-Za-z\.\'\- ]{2,60})/i', $text, $matches)) {
            return $this->cleanPersonName($matches[1], preferLatin: true);
        }

        return null;
    }

    protected function extractEnglishFatherName(string $text): ?string
    {
        if (preg_match(
            '/(?:^|\n)\s*Father(?:\'?s)?\s*Name\s*[:\-]?\s*(.+?)(?=\n\s*Gender|\n\s*Country|\n\s*Identity|\n\s*Date\s*of|\n\s*Husband|\n\s*والد|\z)/isu',
            $text,
            $block
        )) {
            $latin = $this->firstLatinPersonName($block[1]);
            if ($latin) {
                return $latin;
            }
        }

        if (preg_match('/(?:^|\n)\s*(?:S\/O|D\/O|W\/O)\s*[:\-]?\s*([A-Za-z][A-Za-z\.\'\- ]{2,60})/i', $text, $matches)) {
            return $this->cleanPersonName($matches[1], preferLatin: true);
        }

        return null;
    }

    /**
     * Pick the first English person-name line from a bilingual field block.
     */
    protected function firstLatinPersonName(string $block): ?string
    {
        if (! preg_match_all('/^[\t ]*([A-Za-z][A-Za-z\.\'\- ]{2,60})\s*$/m', $block, $matches)) {
            return null;
        }

        foreach ($matches[1] as $candidate) {
            $name = $this->cleanPersonName($candidate, preferLatin: true);
            if ($name && ! $this->isLabelWord($name)) {
                return $name;
            }
        }

        return null;
    }

    protected function extractName(string $text): ?string
    {
        $patterns = [
            '/(?:^|\n)\s*(?:Name|NAME|Holder(?:\'s)? Name)\s*[:\-]?\s*\n?\s*([^\n]{2,60})/iu',
            '/(?:^|\n)\s*نام\s*[:\-]?\s*\n?\s*([^\n]{2,60})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = $this->cleanPersonName($matches[1]);
                if ($name && ! $this->isLabelWord($name)) {
                    return $name;
                }
            }
        }

        return null;
    }

    protected function extractFatherHusbandName(string $text): ?string
    {
        $patterns = [
            '/(?:^|\n)\s*(?:Father(?:\/Husband)?(?:\s*Name)?|Husband(?:\s*Name)?|S\/O|D\/O|W\/O)\s*[:\-]?\s*\n?\s*([^\n]{2,60})/iu',
            '/(?:^|\n)\s*(?:والد کا نام|والد|زوجہ? کا نام)\s*[:\-]?\s*\n?\s*([^\n]{2,60})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = $this->cleanPersonName($matches[1]);
                if ($name && ! $this->isLabelWord($name)) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Read value on the same or next line after an Urdu label.
     *
     * @param  list<string>  $labels
     */
    protected function extractUrduLabeledValue(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '/');
            $pattern = '/(?:^|\n)\s*'.$quoted.'\s*[:\-]?\s*\n?\s*([^\n]{2,80})/u';

            if (preg_match($pattern, $text, $matches)) {
                $value = $this->cleanPersonName($matches[1]);
                if ($value && ! $this->isLabelWord($value) && ! preg_match('/^\d/', $value)) {
                    // Skip if we accidentally captured another Urdu label
                    if (! preg_match('/^(نام|جنس|والد|تاریخ|شناختی)/u', $value)) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    protected function extractIdentityNumber(string $text): ?string
    {
        if (preg_match('/Identity\s*Number\s*[:\-]?\s*\n?\s*(\d{5}[-\s]?\d{7}[-\s]?\d)/i', $text, $matches)) {
            return $this->formatCnicDigits($matches[1]);
        }

        return null;
    }

    protected function extractCnic(string $text): ?string
    {
        // Prefer well-formatted CNIC
        if (preg_match('/\b(\d{5}[-\s]\d{7}[-\s]\d)\b/', $text, $matches)) {
            $formatted = $this->formatCnicDigits($matches[1]);
            if ($formatted) {
                return $formatted;
            }
        }

        if (preg_match('/\b(\d{13})\b/', $text, $matches)) {
            return $this->formatCnicDigits($matches[1]);
        }

        // OCR sometimes drops hyphens: 32203 9445606 7
        if (preg_match('/\b(\d{5})\s+(\d{7})\s+(\d)\b/', $text, $matches)) {
            return $this->formatCnicDigits($matches[1].$matches[2].$matches[3]);
        }

        return null;
    }

    protected function formatCnicDigits(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === null || strlen($digits) !== 13) {
            return null;
        }

        return substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12, 1);
    }

    protected function extractLabeledDateOfBirth(string $text): ?string
    {
        $patterns = [
            '/Date\s*of\s*Birth\s*[:\-]?\s*\n?\s*(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})/i',
            '/DOB\s*[:\-]?\s*\n?\s*(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $normalized = $this->normalizeDate($matches[1]);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    protected function extractUrduDateOfBirth(string $text): ?string
    {
        if (preg_match('/تاریخ\s*پیدائش\s*[:\-]?\s*\n?\s*(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4})/u', $text, $matches)) {
            return $this->normalizeDate($matches[1]);
        }

        return null;
    }

    protected function extractDateOfBirth(string $text): ?string
    {
        // Prefer DOB near birth labels; avoid Date of Issue / Expiry
        $withoutIssueExpiry = preg_replace(
            '/Date\s*of\s*(?:Issue|Expiry)\s*[:\-]?\s*\n?\s*\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{2,4}/iu',
            '',
            $text
        ) ?? $text;

        if ($labeled = $this->extractLabeledDateOfBirth($withoutIssueExpiry)) {
            return $labeled;
        }

        if ($urdu = $this->extractUrduDateOfBirth($withoutIssueExpiry)) {
            return $urdu;
        }

        // Fallback: first valid historical date (not issue/expiry years typically recent)
        if (preg_match_all('/\b(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{4})\b/', $withoutIssueExpiry, $matches)) {
            foreach ($matches[1] as $candidate) {
                $normalized = $this->normalizeDate($candidate);
                if (! $normalized) {
                    continue;
                }
                $year = (int) substr($normalized, 0, 4);
                // Likely DOB if year is before current year - 5 (person age)
                if ($year >= 1920 && $year <= ((int) date('Y') - 1)) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    protected function extractGender(string $text): ?string
    {
        if (preg_match('/(?:Gender|Sex)\s*[:\-]?\s*\n?\s*(Male|Female|M|F)\b/i', $text, $matches)) {
            return $this->normalizeGender($matches[1]);
        }

        // Smart cards often show lone F / M under Gender
        if (preg_match('/Gender\s*(?:\n|.)*?\b([MF])\b/i', $text, $matches)) {
            return $this->normalizeGender($matches[1]);
        }

        if (preg_match('/\b(Male|Female)\b/i', $text, $matches)) {
            return $this->normalizeGender($matches[1]);
        }

        return $this->extractUrduGender($text);
    }

    protected function extractUrduGender(string $text): ?string
    {
        if (preg_match('/جنس\s*[:\-]?\s*\n?\s*(مرد|عورت|زن)/u', $text, $matches)) {
            return $this->normalizeGender($matches[1]);
        }

        if (preg_match('/\b(مرد|عورت)\b/u', $text, $matches)) {
            return $this->normalizeGender($matches[1]);
        }

        return null;
    }

    protected function extractAddress(string $text, string $documentType): ?string
    {
        if (preg_match('/(?:Address|Permanent Address|Present Address)\s*[:\-]?\s*\n?\s*(.+?)(?:\n\n|\n(?:Name|Father|CNIC|Gender|Date|Identity|License)|$)/is', $text, $matches)) {
            $address = trim(preg_replace('/\s+/', ' ', $matches[1]) ?? '');
            $address = preg_replace('/[^A-Za-z0-9,\.\-\/\s]/', '', $address) ?? $address;
            $address = trim($address);

            if (strlen($address) >= 5) {
                return $address;
            }
        }

        if ($documentType === 'cnic' && preg_match('/\b([A-Za-z ]{3,},\s*(?:Punjab|Sindh|KPK|Khyber Pakhtunkhwa|Balochistan|Islamabad)[A-Za-z ]*)\b/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function cleanPersonName(string $name, bool $preferLatin = false): ?string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = trim($name, " \t\n\r\0\x0B:-");

        // Drop trailing junk labels OCR sometimes appends
        $name = preg_replace('/\b(Gender|Father|Country|Identity|Date|Pakistan)\b.*$/i', '', $name) ?? $name;
        $name = trim($name);

        if (mb_strlen($name) < 2) {
            return null;
        }

        if ($preferLatin && ! $this->isMostlyLatin($name)) {
            return null;
        }

        // Keep Urdu/Arabic script as-is; normalize Latin casing
        if ($this->isMostlyLatin($name)) {
            return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
        }

        return $name;
    }

    protected function isMostlyLatin(string $value): bool
    {
        $letters = preg_replace('/[^A-Za-z\x{0600}-\x{06FF}]/u', '', $value) ?? '';
        if ($letters === '') {
            return false;
        }

        $latin = preg_replace('/[^A-Za-z]/', '', $letters) ?? '';

        return strlen($latin) >= (mb_strlen($letters) / 2);
    }

    protected function isLabelWord(string $value): bool
    {
        $blocked = [
            'NAME', 'FATHER', 'HUSBAND', 'GENDER', 'ADDRESS', 'PAKISTAN',
            'NATIONAL', 'IDENTITY', 'CARD', 'DRIVING', 'LICENSE', 'LICENCE',
            'DATE', 'BIRTH', 'CNIC', 'MALE', 'FEMALE', 'COUNTRY', 'STAY',
            'ISLAMIC', 'REPUBLIC', 'NUMBER', 'ISSUE', 'EXPIRY',
            'نام', 'جنس', 'والد', 'تاریخ', 'شناختی', 'حکومت', 'پاکستان',
        ];

        return in_array(mb_strtoupper(trim($value)), array_map('mb_strtoupper', $blocked), true);
    }

    protected function normalizeGender(string $value): string
    {
        $value = trim($value);
        $upper = mb_strtoupper($value);

        if (in_array($upper, ['F', 'FEMALE'], true) || in_array($value, ['عورت', 'زن'], true)) {
            return 'Female';
        }

        return 'Male';
    }

    protected function normalizeDate(string $date): ?string
    {
        $date = str_replace(['.', '/'], '-', trim($date));
        $parts = explode('-', $date);

        if (count($parts) !== 3) {
            return null;
        }

        [$day, $month, $year] = $parts;

        if (strlen($year) === 2) {
            $year = ((int) $year > 50 ? '19' : '20').$year;
        }

        $day = (int) $day;
        $month = (int) $month;
        $year = (int) $year;

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
