<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    /** @use HasFactory<\Database\Factories\ImportErrorFactory> */
    use HasFactory;

    protected $fillable = [
        'import_history_id',
        'row_number',
        'field_name',
        'error_message',
        'row_data',
    ];

    protected function casts(): array
    {
        return [
            'row_data' => 'array',
        ];
    }

    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(ImportHistory::class);
    }
}
