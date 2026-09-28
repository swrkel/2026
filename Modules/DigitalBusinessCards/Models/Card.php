<?php

namespace Modules\DigitalBusinessCards\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Card extends Model
{
    use SoftDeletes;

    /**
     * Available card designs. Keys are stored in the `theme` column; labels are
     * shown in the admin picker. Adding a design means adding a key here and a
     * matching [data-theme="key"] block in resources/views/show.blade.php.
     */
    public const THEMES = [
        'classic'  => 'Classic',
        'bold'     => 'Bold',
        'minimal'  => 'Minimal',
        'midnight' => 'Midnight',
    ];

    protected $guarded = ['id', 'uuid', 'views_count', 'saves_count'];

    protected $casts = [
        'links'        => 'array',
        'is_published' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('digital-business-cards.table_prefix', 'dbc_').'cards';
    }

    public function getConnectionName(): ?string
    {
        return config('digital-business-cards.connection') ?: parent::getConnectionName();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (self $card) {
            if (empty($card->uuid)) {
                $card->uuid = (string) Str::uuid();
            }

            if (empty($card->slug)) {
                $card->slug = static::uniqueSlug($card->fullName());
            }

            if (empty($card->theme)) {
                $card->theme = 'classic';
            }

            if (empty($card->accent_color)) {
                $card->accent_color = config('digital-business-cards.defaults.accent_color');
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(CardView::class, 'card_id');
    }

    /* --------------------------------------------------------------- Scopes */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOwnedBy(Builder $query, $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
    }

    /* ------------------------------------------------------------ Accessors */

    /** Falls back to the default design if the stored value is unknown. */
    public function themeKey(): string
    {
        return array_key_exists((string) $this->theme, self::THEMES) ? $this->theme : 'classic';
    }

    public function themeLabel(): string
    {
        return self::THEMES[$this->themeKey()];
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);

        return Str::upper(collect($parts)->map(fn ($p) => Str::substr($p, 0, 1))->implode(''));
    }

    /** "Head of Design at Northwind" — whichever halves exist. */
    public function role(): ?string
    {
        return match (true) {
            $this->job_title && $this->company => $this->job_title.' at '.$this->company,
            (bool) $this->job_title            => $this->job_title,
            (bool) $this->company              => $this->company,
            default                            => null,
        };
    }

    public function addressLines(): array
    {
        return array_values(array_filter([
            $this->address_line,
            trim(implode(' ', array_filter([$this->city, $this->region, $this->postal_code]))) ?: null,
            $this->country,
        ]));
    }

    public function normalisedLinks(): array
    {
        return collect($this->links ?? [])
            ->filter(fn ($l) => ! empty($l['url']))
            ->map(fn ($l) => [
                'label' => $l['label'] ?? parse_url($l['url'], PHP_URL_HOST) ?? 'Link',
                'url'   => $l['url'],
            ])
            ->values()
            ->all();
    }

    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return app('filesystem')
            ->disk(config('digital-business-cards.media.disk', 'public'))
            ->url($path);
    }

    public function photoUrl(): ?string
    {
        return $this->mediaUrl($this->photo_path);
    }

    public function logoUrl(): ?string
    {
        return $this->mediaUrl($this->logo_path);
    }

    public function publicUrl(): string
    {
        return route('dbc.public.show', $this->slug);
    }

    /* ------------------------------------------------------------- Helpers  */

    public static function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'card';
        $slug = $base;
        $i    = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
