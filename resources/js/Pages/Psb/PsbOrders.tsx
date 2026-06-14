import { Link } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import StatusBadge from '@/Components/Psb/StatusBadge';

export default function PsbOrders({ orders, filters }: { orders: any; filters: any }) {
    return (
        <PsbLayout title="Daftar PSB Order">
            <div className="card">
                <div className="card-header flex items-center justify-between">
                    <h3 className="font-semibold">Order List</h3>
                    <Link href="/psb/orders/create" className="btn btn-primary">+ PSB Baru</Link>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th className="px-4 py-2 text-left">#</th>
                                <th className="px-4 py-2 text-left">Customer</th>
                                <th className="px-4 py-2 text-left">HP</th>
                                <th className="px-4 py-2 text-left">Village</th>
                                <th className="px-4 py-2 text-left">Paket</th>
                                <th className="px-4 py-2 text-left">Status</th>
                                <th className="px-4 py-2 text-left">OLT</th>
                                <th className="px-4 py-2 text-left">Teknisi</th>
                                <th className="px-4 py-2 text-left">Tgl</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data?.map((o: any) => (
                                <tr key={o.id} className="border-t hover:bg-gray-50">
                                    <td className="px-4 py-2">
                                        <Link href={`/psb/orders/${o.id}`} className="text-blue-600 hover:underline">#{o.id}</Link>
                                    </td>
                                    <td className="px-4 py-2 font-medium">{o.customer_name}</td>
                                    <td className="px-4 py-2">{o.customer_phone}</td>
                                    <td className="px-4 py-2 text-gray-600">{o.village}</td>
                                    <td className="px-4 py-2">{o.package}</td>
                                    <td className="px-4 py-2"><StatusBadge status={o.status} /></td>
                                    <td className="px-4 py-2 uppercase text-xs">{o.olt_type || '-'}</td>
                                    <td className="px-4 py-2 text-gray-600">{o.teknisi || '-'}</td>
                                    <td className="px-4 py-2 text-xs text-gray-500">{o.created_at}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </PsbLayout>
    );
}
