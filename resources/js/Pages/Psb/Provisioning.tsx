import { useState } from 'react';
import { router } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import StatusBadge from '@/Components/Psb/StatusBadge';
import toast from 'react-hot-toast';

interface Order {
    id: number; customer_name: string; village: string; package: string;
    olt_type?: string; olt_id?: number; olt_port_label?: string; onu_serial?: string;
    pppoe_user?: string; pppoe_password?: string;
    status: string;
    teknisi?: string;
    provisioning_status?: string;
}

export default function Provisioning({ orders }: { orders: Order[] }) {
    const [selected, setSelected] = useState<Order | null>(orders[0] || null);
    const [oltType, setOltType] = useState<'c300' | 'hioso'>('c300');
    const [oltId, setOltId] = useState('');
    const [onuSerial, setOnuSerial] = useState('');
    const [oltPort, setOltPort] = useState('');

    const selectOlt = () => {
        if (!selected) return;
        router.post(`/psb/provisioning/${selected.id}/select-olt`, {
            olt_type: oltType,
            olt_id: parseInt(oltId) || 0,
            onu_serial: onuSerial,
            olt_port: oltPort,
        }, { onSuccess: () => toast.success('OLT selected, PPPoE generated') });
    };

    const provision = () => {
        if (!selected) return;
        router.post(`/psb/provisioning/${selected.id}/provision`, {}, {
            onSuccess: () => toast.success('Provisioning complete'),
            onError: () => toast.error('Provisioning failed'),
        });
    };

    return (
        <PsbLayout title="Provisioning">
            <div className="grid grid-cols-12 gap-4">
                <div className="col-span-4 card">
                    <div className="card-header"><h3 className="font-semibold">Order Assigned ({orders.length})</h3></div>
                    <div className="divide-y max-h-[calc(100vh-200px)] overflow-y-auto">
                        {orders.map(o => (
                            <button key={o.id} onClick={() => setSelected(o)}
                                className={`w-full text-left p-3 hover:bg-gray-50 ${selected?.id === o.id ? 'bg-blue-50' : ''}`}>
                                <div className="flex justify-between">
                                    <span className="font-medium">#{o.id} {o.customer_name}</span>
                                    <StatusBadge status={o.status} />
                                </div>
                                <div className="text-xs text-gray-500 mt-1">📦 {o.package} · 🔧 {o.teknisi || '-'}</div>
                            </button>
                        ))}
                    </div>
                </div>

                <div className="col-span-8 space-y-4">
                    {selected && (
                        <>
                            <div className="card">
                                <div className="card-header"><h3 className="font-semibold">#{selected.id} {selected.customer_name}</h3></div>
                                <div className="card-body grid grid-cols-2 gap-4 text-sm">
                                    <div>📦 Paket: <strong>{selected.package}</strong></div>
                                    <div>🔧 Teknisi: <strong>{selected.teknisi || '-'}</strong></div>
                                    <div>🔌 OLT: <strong>{selected.olt_type?.toUpperCase() || 'belum dipilih'}</strong></div>
                                    <div>📡 SN: <strong>{selected.onu_serial || '-'}</strong></div>
                                </div>
                            </div>

                            {(!selected.olt_type || !selected.onu_serial) && (
                                <div className="card">
                                    <div className="card-header"><h3 className="font-semibold">Pilih OLT & Input SN</h3></div>
                                    <div className="card-body space-y-3">
                                        <div>
                                            <label className="form-label">Tipe OLT</label>
                                            <div className="flex gap-2">
                                                <label className="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" checked={oltType === 'c300'} onChange={() => setOltType('c300')} />
                                                    ZTE C300 (auto-provision via SSH)
                                                </label>
                                                <label className="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" checked={oltType === 'hioso'} onChange={() => setOltType('hioso')} />
                                                    HiOS (manual — checklist required)
                                                </label>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-3 gap-3">
                                            <div>
                                                <label className="form-label">OLT ID</label>
                                                <input className="form-input" value={oltId} onChange={e => setOltId(e.target.value)} type="number" />
                                            </div>
                                            <div>
                                                <label className="form-label">SN ONT</label>
                                                <input className="form-input" value={onuSerial} onChange={e => setOnuSerial(e.target.value)} />
                                            </div>
                                            <div>
                                                <label className="form-label">Port PON</label>
                                                <input className="form-input" value={oltPort} onChange={e => setOltPort(e.target.value)} placeholder="1/1/1" />
                                            </div>
                                        </div>
                                        <button onClick={selectOlt} className="btn btn-primary">Simpan & Generate PPPoE</button>
                                    </div>
                                </div>
                            )}

                            {selected.olt_type && selected.pppoe_user && (
                                <div className="card bg-emerald-50 border-emerald-200">
                                    <div className="card-header"><h3 className="font-semibold">🔑 PPPoE Generated</h3></div>
                                    <div className="card-body text-sm space-y-1 font-mono">
                                        <div>User: <strong>{selected.pppoe_user}</strong></div>
                                        <div>Pass: <strong>{selected.pppoe_password}</strong></div>
                                    </div>
                                </div>
                            )}

                            {selected.olt_type && selected.olt_type === 'hioso' && (
                                <div className="card bg-yellow-50 border-yellow-200">
                                    <div className="card-header"><h3 className="font-semibold">⚠ HiOS Mode</h3></div>
                                    <div className="card-body text-sm">
                                        <p>Provisioning HiOS = manual oleh teknisi di router pelanggan. PPPoE secret sudah di-add ke MikroTik. Checklist step manual akan dibuat otomatis.</p>
                                    </div>
                                </div>
                            )}

                            {selected.olt_type && selected.olt_type === 'c300' && (
                                <div className="card">
                                    <div className="card-header"><h3 className="font-semibold">⚙ C300 Auto-Provision</h3></div>
                                    <div className="card-body">
                                        <button onClick={provision} className="btn btn-success">Run SSH Provision (phpseclib)</button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </PsbLayout>
    );
}
