import StatusBadge from '@/Components/Psb/StatusBadge';
import PsbLayout from '@/Layouts/PsbLayout';
import { Link } from '@inertiajs/react';

interface Order {
    id: number;
    customer_name: string;
    status: string;
    status_label: string;
    status_color: string;
    coverage_status?: string;
    teknisi?: string;
    created_at: string;
}

interface Props {
    statusCounts: Record<string, number>;
    recentOrders: Order[];
    todayStats: { new_orders: number; completed_today: number; pending_coverage: number; provisioning: number };
}

const PIPELINE = [
    { key: 'draft', label: 'Draft', color: 'bg-gray-400' },
    { key: 'submitted', label: 'Submitted', color: 'bg-blue-500' },
    { key: 'coverage_ok', label: 'Coverage OK', color: 'bg-cyan-500' },
    { key: 'assigned', label: 'Assigned', color: 'bg-yellow-500' },
    { key: 'provisioning', label: 'Provisioning', color: 'bg-orange-500' },
    { key: 'photos', label: 'Photos', color: 'bg-purple-500' },
    { key: 'done', label: 'Done', color: 'bg-emerald-500' },
];

export default function Dashboard({ statusCounts, recentOrders, todayStats }: Props) {
    const total = Object.values(statusCounts).reduce((a, b) => a + b, 0);
    const maxCount = Math.max(...Object.values(statusCounts), 1);

    return (
        <PsbLayout title="📊 Dashboard PSB">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <p className="text-sm text-gray-600">Ringkasan pipeline PSB hari ini</p>
                    <Link href="/psb/orders/create" className="btn btn-primary">+ PSB Baru</Link>
                </div>

                {/* Today's stats */}
                <div className="grid grid-cols-4 gap-4">
                    <StatCard label="Order baru (hari ini)" value={todayStats.new_orders} color="bg-blue-50 text-blue-700" />
                    <StatCard label="Selesai hari ini" value={todayStats.completed_today} color="bg-emerald-50 text-emerald-700" />
                    <StatCard label="Pending coverage" value={todayStats.pending_coverage} color="bg-yellow-50 text-yellow-700" />
                    <StatCard label="In provisioning" value={todayStats.provisioning} color="bg-orange-50 text-orange-700" />
                </div>

                {/* Pipeline bar */}
                <div className="card">
                    <div className="card-header">
                        <h2 className="font-semibold">Pipeline Status</h2>
                    </div>
                    <div className="card-body space-y-3">
                        {PIPELINE.map(stage => {
                            const count = statusCounts[stage.key] || 0;
                            const pct = Math.max((count / maxCount) * 100, 5);
                            return (
                                <div key={stage.key} className="flex items-center gap-3">
                                    <div className="w-32 text-sm text-gray-700">{stage.label}</div>
                                    <div className="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                                        <div className={`${stage.color} h-full flex items-center justify-end pr-2 text-white text-xs font-medium transition-all`} style={{ width: `${pct}%` }}>
                                            {count > 0 && count}
                                        </div>
                                    </div>
                                    <div className="w-12 text-right text-sm font-mono">{count}</div>
                                </div>
                            );
                        })}
                        <div className="text-xs text-gray-500 pt-2 border-t">Total: {total} order</div>
                    </div>
                </div>

                {/* Recent orders */}
                <div className="card">
                    <div className="card-header flex items-center justify-between">
                        <h2 className="font-semibold">Order Terbaru</h2>
                        <Link href="/psb/orders" className="text-sm text-blue-600 hover:underline">Lihat semua →</Link>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 text-xs uppercase text-gray-600">
                                <tr>
                                    <th className="px-4 py-2 text-left">#</th>
                                    <th className="px-4 py-2 text-left">Customer</th>
                                    <th className="px-4 py-2 text-left">Status</th>
                                    <th className="px-4 py-2 text-left">Teknisi</th>
                                    <th className="px-4 py-2 text-left">Tgl</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentOrders.map(o => (
                                    <tr key={o.id} className="border-t hover:bg-gray-50">
                                        <td className="px-4 py-2">
                                            <Link href={`/psb/orders/${o.id}`} className="text-blue-600 hover:underline">#{o.id}</Link>
                                        </td>
                                        <td className="px-4 py-2 font-medium">{o.customer_name}</td>
                                        <td className="px-4 py-2"><StatusBadge status={o.status} /></td>
                                        <td className="px-4 py-2 text-gray-600">{o.teknisi || '-'}</td>
                                        <td className="px-4 py-2 text-gray-500 text-xs">{o.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </PsbLayout>
    );
}

function StatCard({ label, value, color }: { label: string; value: number; color: string }) {
    return (
        <div className={`card ${color} p-4`}>
            <div className="text-xs uppercase opacity-80">{label}</div>
            <div className="text-2xl font-bold mt-1">{value}</div>
        </div>
    );
}
