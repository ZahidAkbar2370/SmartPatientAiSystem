# Smart Patient Record System

Laravel web application for patient registration with optional OCR extraction from CNIC / driving license images.

> This system uses OCR to extract **visible** information from identity documents and populate patient registration fields. It does **not** verify identity with NADRA or any government database.

## Features

- Secure login (no public registration)
- Patient records list with name/CNIC search
- Add patient manually
- Scan CNIC / driving license via camera or image upload
- OCR extraction → editable patient form → user confirmation → save
- Duplicate CNIC prevention
- Private storage for document images

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (or SQLite for local quick start)
- [Tesseract OCR](https://github.com/tesseract-ocr/tesseract) installed on the server

### Install Tesseract (Windows)

1. Download the installer from: https://github.com/UB-Mannheim/tesseract/wiki
2. During install, select **English** and **Urdu** language data (needed for all 3 Pakistani CNIC formats)
3. Install (default path is usually `C:\Program Files\Tesseract-OCR\`)
4. Set in `.env`:

```env
TESSERACT_PATH="C:\Program Files\Tesseract-OCR\tesseract.exe"
OCR_LANGUAGES=eng+urd
```

If `tesseract` is already on your system PATH, you can leave `TESSERACT_PATH` empty.

Supported CNIC layouts:
1. Old **Urdu-only** CNIC
2. New **bilingual Smart Card** (chip) — English + Urdu
3. New **bilingual CNIC** — English + Urdu

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Create the MySQL database `smart_patient_system`, then:

```bash
php artisan migrate --seed
php artisan serve
```

Open http://localhost:8000

### Default login

| Field    | Value             |
|----------|-------------------|
| Email    | admin@sps.local   |
| Password | password          |

## SQLite quick start

If MySQL is not available yet:

```env
DB_CONNECTION=sqlite
```

Ensure `database/database.sqlite` exists, then run migrate/seed as above.

## Project structure (key files)

```text
app/Http/Controllers/AuthController.php
app/Http/Controllers/PatientController.php
app/Models/Patient.php
app/Services/OcrService.php
app/Services/PatientDocumentParser.php
resources/views/patients/
public/js/scan.js
```

## User flow

1. Login
2. Patient Records
3. Add Patient → Manual **or** Scan Document
4. For scan: capture/upload → OCR → review form → save

OCR results are never saved automatically. The user must review and confirm.

## Notes

- OCR runs in the **browser** (Tesseract.js) so scanning works even if Tesseract is not installed on the PC
- Optional: install desktop Tesseract as a server-side backup (see above)
- Document images are stored under `storage/app/private/patient-documents/`
- Temporary scans use `storage/app/private/temp-scans/`
- Image viewing routes require authentication
- Keep image quality high (clear, well-lit, document filling the frame) for better OCR results
- First OCR run may take longer while language data downloads
