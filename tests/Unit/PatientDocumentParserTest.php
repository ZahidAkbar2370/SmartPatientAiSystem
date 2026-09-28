<?php

namespace Tests\Unit;

use App\Services\PatientDocumentParser;
use PHPUnit\Framework\TestCase;

class PatientDocumentParserTest extends TestCase
{
    private PatientDocumentParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PatientDocumentParser;
    }

    public function test_urdu_only_cnic_format(): void
    {
        // Sample 1 — old Urdu-only CNIC
        $text = <<<TEXT
حکومت پاکستان
قومی شناختی کارڈ

32203-9445606-7

نام
غلام عباس

جنس
مرد

والد کا نام
اللہ بخش

شناختی علامت
دائیں رخسار پر تل

تاریخ پیدائش
07/02/1968
TEXT;

        $result = $this->parser->parse($text, 'cnic');

        $this->assertSame('urdu_only', $result['document_format']);
        $this->assertSame('غلام عباس', $result['full_name']);
        $this->assertSame('اللہ بخش', $result['father_husband_name']);
        $this->assertSame('32203-9445606-7', $result['cnic']);
        $this->assertSame('1968-02-07', $result['date_of_birth']);
        $this->assertSame('Male', $result['gender']);
    }

    public function test_bilingual_smart_card_cnic_format(): void
    {
        // Sample 2 — bilingual Smart Card (chip)
        $text = <<<TEXT
PAKISTAN
ISLAMIC REPUBLIC OF PAKISTAN
National Identity Card

Name
ماہ نور عباس
Mahnoor Abbas

Father Name
غلام عباس
Ghulam Abbas

Gender
F

Country of Stay
Pakistan

Identity Number
32203-7537293-6

Date of Birth
21.07.2005

Date of Issue
09.01.2024

Date of Expiry
09.01.2034
TEXT;

        $result = $this->parser->parse($text, 'cnic');

        $this->assertSame('bilingual_smart', $result['document_format']);
        $this->assertSame('Mahnoor Abbas', $result['full_name']);
        $this->assertSame('Ghulam Abbas', $result['father_husband_name']);
        $this->assertSame('32203-7537293-6', $result['cnic']);
        $this->assertSame('2005-07-21', $result['date_of_birth']);
        $this->assertSame('Female', $result['gender']);
    }

    public function test_bilingual_standard_cnic_format(): void
    {
        // Sample 3 — bilingual CNIC (photo left)
        $text = <<<TEXT
PAKISTAN National Identity Card
ISLAMIC REPUBLIC OF PAKISTAN

Name
موبایہ عباس
Mobaya Abbas

Father Name
غلام عباس
Ghulam Abbas

Gender
F

Country of Stay
Pakistan

Identity Number
32203-9307494-4

Date of Birth
15.01.2007

Date of Issue
28.11.2025

Date of Expiry
28.11.2035
TEXT;

        $result = $this->parser->parse($text, 'cnic');

        $this->assertContains($result['document_format'], ['bilingual', 'bilingual_smart']);
        $this->assertSame('Mobaya Abbas', $result['full_name']);
        $this->assertSame('Ghulam Abbas', $result['father_husband_name']);
        $this->assertSame('32203-9307494-4', $result['cnic']);
        $this->assertSame('2007-01-15', $result['date_of_birth']);
        $this->assertSame('Female', $result['gender']);
    }

    public function test_generic_english_cnic_still_works(): void
    {
        $text = <<<TEXT
PAKISTAN
NATIONAL IDENTITY CARD

Name
MUHAMMAD ALI

Father Name
MUHAMMAD AKRAM

Date of Birth
15-08-1998

Gender
Male

CNIC
35202-1234567-1

Address
Layyah, Punjab
TEXT;

        $result = $this->parser->parse($text, 'cnic');

        $this->assertSame('Muhammad Ali', $result['full_name']);
        $this->assertSame('Muhammad Akram', $result['father_husband_name']);
        $this->assertSame('35202-1234567-1', $result['cnic']);
        $this->assertSame('1998-08-15', $result['date_of_birth']);
        $this->assertSame('Male', $result['gender']);
        $this->assertSame('Layyah, Punjab', $result['address']);
    }
}
