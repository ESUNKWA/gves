<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'logo_path',
        'primary_color',
        'currency',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'registration_number',
        'tax_id',
        'social_security_number',
        'collective_agreement',
    ];

    protected static function booted(): void
    {
        static::updating(function (CompanySetting $settings) {
            if ($settings->isDirty('logo_path') && $settings->getOriginal('logo_path')) {
                Storage::disk('public')->delete($settings->getOriginal('logo_path'));
            }
        });
    }

    /**
     * There is always exactly one company settings row (this app is deployed
     * one instance per client, so there is no multi-tenant settings list).
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['name' => config('app.name', 'SIRH')]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * The logo as a base64 data URI, for embedding in generated PDFs — dompdf
     * cannot reliably fetch the app's own HTTP URL for the logo, so the file
     * bytes are inlined directly instead.
     */
    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($this->logo_path) ?: 'image/png';
        $contents = Storage::disk('public')->get($this->logo_path);

        return "data:{$mimeType};base64,".base64_encode($contents);
    }

    public function addressLine(): string
    {
        return collect([$this->address, $this->city, $this->country])->filter()->join(', ');
    }

    /**
     * The brand color as space-separated RGB channels (e.g. "79 70 229"), for use in
     * Tailwind's `rgb(var(--color-primary) / <alpha-value>)` CSS-variable color pattern.
     */
    public function primaryColorRgbChannels(): string
    {
        $hex = ltrim($this->primary_color ?: '#4f46e5', '#');

        if (strlen($hex) === 3) {
            $hex = implode('', array_map(fn ($c) => str_repeat($c, 2), str_split($hex)));
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '4f46e5';
        }

        [$r, $g, $b] = sscanf($hex, '%02x%02x%02x');

        return "{$r} {$g} {$b}";
    }
}
