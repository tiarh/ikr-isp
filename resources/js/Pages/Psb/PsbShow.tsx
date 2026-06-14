import { Link } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import StatusBadge from '@/Components/Psb/StatusBadge';

export default function PsbShow({ order }: { order: any }) {
    return (
        <PsbLayout title={`Order #${order.id} - ${order.customer_name}`}>
            <div className="grid grid-cols-3 gap-4">
                <div className="col-span-2 space-y-4">
                    <div className="card">
                        <div className="card-header flex items-center justify-between">
                            <h3 className="font-semibold">Detail Order</h3>
                            <StatusBadge status={order.status} />
                        </div>
                        <div className="card-body grid grid-cols-2 gap-3 text-sm">
                            <KV k="Customer" v={order.customer_name} />
                            <KV k="Phone" v={order.customer_phone} />
                            <KV k="Address" v={order.customer_address} />
                            <KV k="Village" v={order.village} />
                            <KV k="Package" v={order.package} />
                            <KV k="Router" v={order.router_name} />
                            <KV k="PPPoE User" v={order.pppoe_user} mono />
                            <KV k="PPPoE Pass" v={order.pppoe_password} mono />
                            <KV k="OLT" v={order.olt_type?.toUpperCase()} />
                            <KV k="SN" v={order.onu_serial} />
                            <KV k="Port" v={order.olt_port_label} />
                            <KV k="Redaman ODP" v={order.redaman_odp + ' dB'} />
                            <KV k="Redaman Router" v={order.redaman_router + ' dB'} />
                            <KV k="GPS" v={`${order.gps_lat}, ${order.gps_long}`} mono />
                        </div>
                    </div>

                    {order.status_logs && (
                        <div className="card">
                            <div className="card-header"><h3 className="font-semibold">Status History</h3></div>
                            <div className="card-body space-y-2 text-sm">
                                {order.status_logs.map((log: any) => (
                                    <div key={log.id} className="flex gap-3 border-l-2 border-gray-200 pl-3">
                                        <div className="text-xs text-gray-500 w-32">{new Date(log.created_at).toLocaleString('id-ID')}</div>
                                        <div>
                                            <div className="font-medium">{log.from_status} → <span className="text-blue-600">{log.to_status}</span></div>
                                            {log.note && <div className="text-gray-600 text-xs mt-0.5">{log.note}</div>}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                <div className="space-y-3">
                    <div className="card">
                        <div className="card-header"><h3 className="font-semibold">Quick Actions</h3></div>
                        <div className="card-body space-y-2">
                            <Link href={`/psb/orders/${order.id}/documents`} className="btn btn-primary w-full">📷 Dokumentasi</Link>
                            <Link href={`/psb/orders/${order.id}/sync`} className="btn btn-success w-full">🚀 Sync eBilling</Link>
                            {order.bai_pdf_path && (
                                <a href={`/storage/${order.bai_pdf_path}`} target="_blank" className="btn btn-ghost w-full">📄 Download BAI</a>
                            )}
                        </div>
                    </div>

                    {order.teknisi && order.teknisi.length > 0 && (
                        <div className="card">
                            <div className="card-header"><h3 className="font-semibold">Teknisi ({order.teknisi.length})</h3></div>
                            <div className="card-body text-sm space-y-1">
                                {order.teknisi.map((t: any) => (
                                    <div key={t.id} className="flex items-center justify-between">
                                        <span>🔧 {t.name}</span>
                                        <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">{t.pivot.role}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PsbLayout>
    );
}

function KV({ k, v, mono }: { k: string; v: any; mono?: boolean }) {
    return (
        <div>
            <div className="text-xs text-gray-500">{k}</div>
            <div className={mono ? 'font-mono' : 'font-medium'}>{v || '-'}</div>
        </div>
    );
}
