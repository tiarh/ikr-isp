import { useState } from 'react';
import { router } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import StatusBadge from '@/Components/Psb/StatusBadge';
import toast from 'react-hot-toast';

interface Order { id: number; customer_name: string; village: string; package: string; status: string; }
interface Teknisi { id: number; name: string; email: string; open_tickets: number; }

export default function Assignment({ orders, teknisis }: { orders: Order[]; teknisis: Teknisi[] }) {
    const [selected, setSelected] = useState<Order | null>(orders[0] || null);
    const [pickedIds, setPickedIds] = useState<number[]>([]);
    const [primaryId, setPrimaryId] = useState<number | null>(null);

    const togglePick = (id: number) => {
        if (pickedIds.includes(id)) {
            setPickedIds(pickedIds.filter(x => x !== id));
            if (primaryId === id) setPrimaryId(null);
        } else {
            setPickedIds([...pickedIds, id]);
            if (!primaryId) setPrimaryId(id);
        }
    };

    const assign = () => {
        if (!selected || pickedIds.length === 0 || !primaryId) return toast.error('Pilih minimal 1 teknisi + 1 primary');
        router.post(`/psb/assignment/${selected.id}/assign`, {
            teknisi_ids: pickedIds,
            primary_id: primaryId,
        }, { onSuccess: () => { toast.success('Teknisi assigned'); setPickedIds([]); setPrimaryId(null); } });
    };

    return (
        <PsbLayout title="Assignment Teknisi">
            <div className="grid grid-cols-12 gap-4">
                <div className="col-span-5 card">
                    <div className="card-header"><h3 className="font-semibold">Order Coverage OK ({orders.length})</h3></div>
                    <div className="divide-y max-h-[calc(100vh-200px)] overflow-y-auto">
                        {orders.length === 0 && <div className="p-4 text-sm text-gray-500">Tidak ada order</div>}
                        {orders.map(o => (
                            <button key={o.id} onClick={() => { setSelected(o); setPickedIds([]); setPrimaryId(null); }}
                                className={`w-full text-left p-3 hover:bg-gray-50 ${selected?.id === o.id ? 'bg-blue-50' : ''}`}>
                                <div className="flex items-center justify-between">
                                    <span className="font-medium">#{o.id} {o.customer_name}</span>
                                    <StatusBadge status={o.status} />
                                </div>
                                <div className="text-xs text-gray-500 mt-1">📦 {o.package} · 📍 {o.village}</div>
                            </button>
                        ))}
                    </div>
                </div>

                <div className="col-span-7 card">
                    <div className="card-header">
                        <h3 className="font-semibold">Pilih Teknisi (sort by open ticket)</h3>
                        <p className="text-xs text-gray-500 mt-1">Centang = assign, klik lagi untuk hapus. Pilih 1 sbg primary (lead).</p>
                    </div>
                    <div className="card-body max-h-[calc(100vh-280px)] overflow-y-auto">
                        <table className="w-full text-sm">
                            <thead className="text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="text-left p-2">Pilih</th>
                                    <th className="text-left p-2">Primary</th>
                                    <th className="text-left p-2">Nama</th>
                                    <th className="text-left p-2">Open Ticket</th>
                                </tr>
                            </thead>
                            <tbody>
                                {teknisis.map(t => (
                                    <tr key={t.id} className="border-t">
                                        <td className="p-2">
                                            <input type="checkbox" checked={pickedIds.includes(t.id)}
                                                onChange={() => togglePick(t.id)} />
                                        </td>
                                        <td className="p-2">
                                            <input type="radio" name="primary" disabled={!pickedIds.includes(t.id)}
                                                checked={primaryId === t.id}
                                                onChange={() => setPrimaryId(t.id)} />
                                        </td>
                                        <td className="p-2 font-medium">{t.name}</td>
                                        <td className="p-2">
                                            <span className={`px-2 py-0.5 rounded text-xs ${
                                                t.open_tickets === 0 ? 'bg-emerald-100 text-emerald-700' :
                                                t.open_tickets < 3 ? 'bg-yellow-100 text-yellow-700' :
                                                'bg-red-100 text-red-700'
                                            }`}>
                                                {t.open_tickets}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {selected && (
                        <div className="card-body border-t">
                            <div className="text-sm text-gray-600 mb-2">
                                Assign #{selected.id} ke {pickedIds.length} teknisi (primary: #{primaryId || '?'})
                            </div>
                            <button onClick={assign} disabled={pickedIds.length === 0 || !primaryId}
                                className="btn btn-primary">Assign</button>
                        </div>
                    )}
                </div>
            </div>
        </PsbLayout>
    );
}
