import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

interface Stats {
    users_total: number;
    psb_total: number;
    psb_draft: number;
    psb_provisioning: number;
    psb_done: number;
}

export default function AdminDashboard({ stats }: { stats: Stats }) {
    return (
        <>
            <Head title="Admin Dashboard" />
            <div className="min-h-screen bg-gray-50 p-8">
                <div className="max-w-6xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-2xl font-bold">Admin Dashboard</h1>
                        <div className="flex gap-2">
                            <Link href="/admin/users" className="btn btn-secondary">Users</Link>
                            <Link href="/psb" className="btn btn-secondary">PSB App</Link>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="card p-6">
                            <div className="text-sm text-gray-500">Total Users</div>
                            <div className="text-3xl font-bold">{stats.users_total}</div>
                        </div>
                        <div className="card p-6">
                            <div className="text-sm text-gray-500">Total PSB Orders</div>
                            <div className="text-3xl font-bold">{stats.psb_total}</div>
                            <div className="text-xs text-gray-400 mt-2">
                                {stats.psb_draft} draft · {stats.psb_provisioning} provisioning · {stats.psb_done} done
                            </div>
                        </div>
                        <div className="card p-6">
                            <div className="text-sm text-gray-500">System</div>
                            <div className="text-sm mt-2">
                                <div>PHP 8.4 · Laravel 11</div>
                                <div>Livewire 3 · Inertia 2</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
