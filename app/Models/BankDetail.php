<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class BankDetail extends Model
{
    /** @use HasFactory<\Database\Factories\BankDetailFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'bank_id',
        'account_number',
        'account_name',
        'account_type',
        'is_primary',
        'is_active',
        'activated_at',
        'deactivated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'activated_at' => 'date',
            'deactivated_at' => 'date',
        ];
    }

    // Encrypt/Decrypt account_number attribute
    protected function accountNumber(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    // Relationships

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function monthlyPayments(): HasMany
    {
        return $this->hasMany(MonthlyPayment::class);
    }

    // Business Methods

    public function findDuplicates()
    {
        // Find other bank details with the same account number
        // Note: This requires decrypting all account numbers, which is expensive
        // In production, consider using a hash of the account number for duplicate detection
        return self::where('bank_id', $this->bank_id)
            ->where('id', '!=', $this->id)
            ->get()
            ->filter(fn ($detail) => $detail->account_number === $this->account_number);
    }

    public function validateAccount(): bool
    {
        // Basic validation of account number format
        // Ghana bank account numbers are typically 10-16 digits
        $accountNumber = $this->account_number;

        if (! $accountNumber) {
            return false;
        }

        return strlen($accountNumber) >= 10 && strlen($accountNumber) <= 16 && ctype_digit($accountNumber);
    }

    public function getPaymentCount(): int
    {
        return $this->monthlyPayments()->count();
    }

    public function isActive(): bool
    {
        return $this->is_active && ! $this->deactivated_at;
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'activated_at' => now(),
            'deactivated_at' => null,
        ]);
    }

    public function deactivate(?string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'notes' => $reason ? ($this->notes ? $this->notes."\n".$reason : $reason) : $this->notes,
        ]);
    }

    public function makePrimary(): void
    {
        // Remove primary status from other accounts
        self::where('staff_id', $this->staff_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $this->update(['is_primary' => true]);
    }
}
