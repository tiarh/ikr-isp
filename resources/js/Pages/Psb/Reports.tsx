import { Link } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

const COLORS = ['#3b82f6', '#06b6d4', '#eab308', '#f97316', '#a855f7', '#10b981', '#ef4444'];

export default function Reports({ stats, byPackage, byTeknisi }: any) {
    const pieData = Object.entries(byPackage || {}).map(([k, v]) => ({ name: k, value: v }));
    const teknisiData = Object.entries(byTeknisi || {}).map(([k, v]) => ({ name: k, value: v }));

    return (
        <PsbLayout title="Reports">
            <div className="flex justify-end mb-3">
                <Link href="/psb/reports/export" className="btn btn-primary">⬇ Export XLSX</Link>
            </div>
            <div className="grid grid-cols-4 gap-3 mb-4">
                {Object.entries(stats || {}).map(([k, v]: any) => (
                    <div key={k} className="card p-3">
                        <div className="text-xs uppercase text-gray-500">{k.replace(/_/g, ' ')}</div>
                        <div className="text-xl font-bold mt-1">{typeof v === 'number' ? v.toFixed(1) : v}</div>
                    </div>
                ))}
            </div>
            <div className="grid grid-cols-2 gap-4">
                <div className="card">
                    <div className="card-header"><h3 className="font-semibold">Distribusi Paket</h3></div>
                    <div className="card-body" style={{ height: 280 }}>
                        <ResponsiveContainer>
                            <PieChart>
                                <Pie data={pieData} dataKey="value" nameKey="name" outerRadius={80} label>
                                    {pieData.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>
                <div className="card">
                    <div className="card-header"><h3 className="font-semibold">Top Teknisi (Done)</h3></div>
                    <div className="card-body" style={{ height: 280 }}>
                        <ResponsiveContainer>
                            <BarChart data={teknisiData} layout="vertical">
                                <XAxis type="number" />
                                <YAxis dataKey="name" type="category" width={120} />
                                <Tooltip />
                                <Bar dataKey="value" fill="#3b82f6" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>
        </PsbLayout>
    );
}
