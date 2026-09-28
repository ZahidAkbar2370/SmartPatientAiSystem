<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tesseract OCR Executable
    |--------------------------------------------------------------------------
    |
    | Path to the tesseract binary. Leave null to use the system PATH.
    | Windows example:
    | C:\Program Files\Tesseract-OCR\tesseract.exe
    |
    */

    'tesseract_path' => env('TESSERACT_PATH'),

    /*
    |--------------------------------------------------------------------------
    | OCR Languages
    |--------------------------------------------------------------------------
    |
    | Pakistani CNICs need English + Urdu.
    | Install Urdu traineddata: urd.traineddata
    | Fallback to English-only if Urdu pack is missing.
    |
    */

    'language' => env('OCR_LANGUAGE', 'eng'),

    'languages' => array_values(array_filter(array_map(
        'trim',
        explode('+', env('OCR_LANGUAGES', 'eng+urd'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Image Upload Limits
    |--------------------------------------------------------------------------
    */

    'max_image_kb' => (int) env('OCR_MAX_IMAGE_KB', 5120),

    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'],

    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],

];
