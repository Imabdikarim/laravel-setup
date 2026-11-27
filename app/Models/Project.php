<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    /**
     * Icons the website knows how to draw. Keep this list in sync with
     * resources/js/views/admin/Projects.vue (admin) and src/data/systemIcons.js (website).
     */
    public const ICONS = [
        'truck',
        'bag',
        'plane',
        'medical',
        'money',
        'heartbeat',
        'globe',
        'register',
        'briefcase',
        'chart',
    ];

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'tags',
        'live_url',
        'repo_url',
        'image_url',
        'color',
        'icon',
        'status',
        'featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Only projects that are visible on the public website. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** "My Project" -> "my-project", adding -2, -3... if the slug is taken. */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'project';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}