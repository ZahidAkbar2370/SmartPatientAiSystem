<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patient extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'father_husband_name',
        'cnic',
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'document_type',
        'document_image',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'cnic' => 'CNIC',
            'driving_license' => 'Driving License',
            default => 'N/A',
        };
    }
}
