<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'templates';

    protected $fillable = [
        'uuid',
        'key',
        'name',
        'channel',
        'subject',
        'body',
        'variables_schema',
        'version',
        'is_active',
    ];

    protected $casts = [
        'variables_schema' => 'array',
        'is_active'        => 'boolean',
        'version'          => 'integer',
    ];

    protected $hidden = ['id'];

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    protected static function booted(): void
    {
        static::creating(function (Template $template): void {
            if (empty($template->uuid)) {
                $template->uuid = (string) Str::uuid();
            }

            if (empty($template->version)) {
                $template->version = 1;
            }
        });
    }
}
