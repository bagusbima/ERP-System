<?php

namespace App\Support;

use App\Models\Role;

/**
 * Central registry of the permission keys used by the role based access control.
 *
 * Keys are formatted as `module.action` and stored as an array on
 * {@see Role}. Keep this list in sync with the modules that
 * actually ship an API surface.
 */
final class Permissions
{
    /**
     * Modules that can be granted independently.
     */
    public const MODULES = [
        'master' => ['view', 'create', 'update', 'delete'],
        'inventory' => ['view', 'create', 'update', 'delete', 'approve'],
        'purchasing' => ['view', 'create', 'update', 'delete', 'approve'],
        'sales' => ['view', 'create', 'update', 'delete', 'approve'],
        'finance' => ['view', 'create', 'update', 'delete', 'approve'],
        'manufacturing' => ['view', 'create', 'update', 'delete', 'approve'],
        'report' => ['view', 'export'],
        'settings' => ['view', 'update'],
        'users' => ['view', 'create', 'update', 'delete'],
    ];

    /**
     * Every permission key available in the system.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $keys = [];

        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                $keys[] = $module.'.'.$action;
            }
        }

        return $keys;
    }

    /**
     * Whether the given key is a valid permission.
     */
    public static function isValid(string $key): bool
    {
        $segments = explode('.', $key, 2);

        if (count($segments) !== 2) {
            return false;
        }

        [$module, $action] = $segments;

        return isset(self::MODULES[$module]) && in_array($action, self::MODULES[$module], true);
    }

    /**
     * The permissions granted to a company owner or a superadmin.
     *
     * @return array<int, string>
     */
    public static function full(): array
    {
        return self::all();
    }

    /**
     * Indonesian labels of the modules, used by the role editor.
     *
     * @return array<string, string>
     */
    public static function moduleLabels(): array
    {
        return [
            'master' => 'Master Data',
            'inventory' => 'Persediaan',
            'purchasing' => 'Pembelian',
            'sales' => 'Penjualan',
            'finance' => 'Keuangan',
            'manufacturing' => 'Manufaktur',
            'report' => 'Laporan',
            'settings' => 'Pengaturan',
            'users' => 'Pengguna & Akses',
        ];
    }

    /**
     * Indonesian labels of the actions available inside a module.
     *
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            'view' => 'Melihat',
            'create' => 'Menambah',
            'update' => 'Mengubah',
            'delete' => 'Menghapus',
            'approve' => 'Menyetujui',
            'export' => 'Mengekspor',
        ];
    }

    /**
     * The registry grouped by module and labelled for the role editor.
     *
     * Served by the API so the SPA and the backend share a single definition
     * of what each permission key means.
     *
     * @return array<int, array{key: string, label: string, actions: array<int, array{key: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $moduleLabels = self::moduleLabels();
        $actionLabels = self::actionLabels();
        $modules = [];

        foreach (self::MODULES as $module => $actions) {
            $items = [];

            foreach ($actions as $action) {
                $items[] = [
                    'key' => $module.'.'.$action,
                    'label' => $actionLabels[$action] ?? $action,
                ];
            }

            $modules[] = [
                'key' => $module,
                'label' => $moduleLabels[$module] ?? $module,
                'actions' => $items,
            ];
        }

        return $modules;
    }
}
