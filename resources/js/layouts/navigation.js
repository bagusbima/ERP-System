import { Boxes, Building2, LayoutDashboard, Package, ShoppingCart, ShieldCheck, Users } from 'lucide-vue-next';

/**
 * Sidebar navigation.
 *
 * Modules are appended here as they ship; the `permission` key is checked
 * against the active company so users only see what they can act on.
 *
 * @type {Array<{label: string, items: Array<{name: string, path: string, icon: unknown, permission?: string}>}>}
 */
export const navigation = [
    {
        label: 'Umum',
        items: [
            { name: 'Dashboard', path: '/', icon: LayoutDashboard },
        ],
    },
    {
        label: 'Master Data',
        items: [
            { name: 'Katalog Produk', path: '/master-data', icon: Boxes, permission: 'master.view' },
        ],
    },
    {
        label: 'Operasional',
        items: [
            { name: 'Inventory', path: '/inventory', icon: Package, permission: 'inventory.view' },
            { name: 'Purchasing', path: '/purchasing', icon: ShoppingCart, permission: 'purchasing.view' },
        ],
    },
    {
        label: 'Pengaturan',
        items: [
            { name: 'Perusahaan', path: '/companies', icon: Building2 },
            { name: 'Pengguna', path: '/users', icon: Users, permission: 'users.view' },
            { name: 'Peran & Izin', path: '/roles', icon: ShieldCheck, permission: 'users.view' },
        ],
    },
];
