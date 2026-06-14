import { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    HomeIcon, ClipboardDocumentListIcon, MapIcon,
    UserGroupIcon, ChartBarIcon, ArrowRightOnRectangleIcon,
} from '@heroicons/react/24/outline';

export default function PsbLayout({ children, title }: PropsWithChildren<{ title?: string }>) {
    const { props, url } = usePage<any>();
    const user: { id?: number; name?: string; email?: string; roles?: string[] } | null = props.auth?.user ?? null;
    const roles: string[] = user?.roles ?? [];

    const navItems: { name: string; href: string; icon: any; roles?: string[] }[] = [
        { name: 'Dashboard',  href: '/psb',                icon: HomeIcon },
        { name: 'Orders',     href: '/psb/orders',         icon: ClipboardDocumentListIcon },
        { name: 'Pipeline',   href: '/psb/pipeline',       icon: ChartBarIcon },
        { name: 'Coverage',   href: '/psb/coverage',       icon: MapIcon, roles: ['sales_leader', 'admin'] },
        { name: 'Assignment', href: '/psb/assignment',     icon: UserGroupIcon, roles: ['leader_teknisi', 'admin'] },
        { name: 'Reports',    href: '/psb/reports',        icon: ChartBarIcon, roles: ['admin', 'sales_leader'] },
    ];

    return (
        <div className="min-h-screen flex">
            <Head title={title} />
            {/* Sidebar */}
            <aside className="w-60 bg-gray-900 text-white flex flex-col">
                <div className="p-4 border-b border-gray-800">
                    <Link href="/psb" className="text-lg font-bold flex items-center gap-2">
                        <span className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-sm">IKR</span>
                        IKR ISP
                    </Link>
                </div>
                <nav className="flex-1 p-2 space-y-1">
                    {navItems
                        .filter(item => !item.roles || item.roles.some(r => roles.includes(r)))
                        .map(item => {
                            const Icon = item.icon;
                            const active = url.startsWith(item.href);
                            return (
                                <Link key={item.href} href={item.href}
                                    className={`flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors ${
                                        active ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'
                                    }`}>
                                    <Icon className="w-5 h-5" /> {item.name}
                                </Link>
                            );
                        })}
                </nav>
                <div className="p-3 border-t border-gray-800">
                    <div className="text-xs text-gray-400 mb-2 px-2">Signed in as</div>
                    <div className="text-sm font-medium px-2 mb-3">{user?.name}</div>
                    <div className="flex gap-1">
                        {roles.map((r: string) => (
                            <span key={r} className="text-xs bg-gray-800 px-2 py-0.5 rounded">{r}</span>
                        ))}
                    </div>
                    <Link href="/logout" method="post" as="button"
                        className="mt-3 w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-red-300 hover:bg-red-900/30">
                        <ArrowRightOnRectangleIcon className="w-4 h-4" /> Logout
                    </Link>
                </div>
            </aside>

            {/* Main */}
            <main className="flex-1 overflow-y-auto">
                <header className="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
                    <h1 className="text-lg font-semibold">{title || 'Dashboard'}</h1>
                    <div className="text-sm text-gray-500">{new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</div>
                </header>
                <div className="p-6">{children}</div>
            </main>
        </div>
    );
}
