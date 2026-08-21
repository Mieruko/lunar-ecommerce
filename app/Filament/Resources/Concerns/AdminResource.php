<?php

namespace App\Filament\Resources\Concerns;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class AdminResource extends Resource
{
    protected static string $viewPermission = 'admin.access';
    protected static ?string $managePermission = null;

    protected static function allowed(?string $permission): bool
    {
        $user = auth()->user();
        return $user && $user->status === 'active' && ($user->isAdministrator() || ($permission && $user->hasPermission($permission)));
    }

    public static function canViewAny(): bool { return static::allowed(static::$viewPermission); }
    public static function canView(Model $record): bool { return static::canViewAny(); }
    public static function canCreate(): bool { return static::allowed(static::$managePermission); }
    public static function canEdit(Model $record): bool { return static::allowed(static::$managePermission); }
    public static function canDelete(Model $record): bool { return static::allowed(static::$managePermission); }
    public static function canDeleteAny(): bool { return static::allowed(static::$managePermission); }
}
