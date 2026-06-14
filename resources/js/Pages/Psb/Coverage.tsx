import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import toast from 'react-hot-toast';
import { MapContainer, TileLayer, Marker, Popup, Circle, Polyline } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';

interface Order {
    id: number;
    customer_name: string;
    customer_phone: string;
    village: string;
    package: string;
    gps_lat?: number;
    gps_long?: number;
    coverage_status?: string;
    odp_distance_m?: number;
    status: string;
    created_at: string;
}

interface Odp {
    id: number;
    code: string;
    name: string;
    lat: number;
    lng: number;
    distance_m: number;
}

export default function Coverage({ orders }: { orders: Order[] }) {
    const [selected, setSelected] = useState<Order | null>(orders[0] || null);
    const [odps, setOdps] = useState<Odp[]>([]);
    const [pickedOdp, setPickedOdp] = useState<Odp | null>(null);
    const [revisionNote, setRevisionNote] = useState('');
    const [showRejectModal, setShowRejectModal] = useState(false);

    useEffect(() => {
        if (selected?.gps_lat && selected?.gps_long) {
            fetch(`/api/v1/odp-assets?lat=${selected.gps_lat}&lng=${selected.gps_long}&radius=500`)
                .then(r => r.json())
                .then(d => setOdps(d.data || []))
                .catch(() => setOdps([]));
        }
    }, [selected?.id, selected?.gps_lat, selected?.gps_long]);

    const approve = () => {
        if (!pickedOdp || !selected) return toast.error('Pilih ODP dulu');
        router.post(`/psb/coverage/${selected.id}/approve`, {
            odp_asset_id: pickedOdp.id,
            odp_distance_m: pickedOdp.distance_m,
            odp_code: pickedOdp.code,
            odp_lat: pickedOdp.lat,
            odp_lng: pickedOdp.lng,
        }, { onSuccess: () => toast.success('Coverage OK') });
    };

    const reject = () => {
        if (!selected) return;
        router.post(`/psb/coverage/${selected.id}/reject`, {
            revision_note: revisionNote,
        }, {
            onSuccess: () => { toast.success('Rejected, order marked for revision'); setShowRejectModal(false); setRevisionNote(''); },
        });
    };

    return (
        <PsbLayout title="Coverage Check">
            <div className="grid grid-cols-12 gap-4 h-[calc(100vh-150px)]">
                {/* Order list */}
                <div className="col-span-3 card overflow-y-auto">
                    <div className="card-header"><h3 className="font-semibold">Order Submitted ({orders.length})</h3></div>
                    <div className="divide-y">
                        {orders.length === 0 && <div className="p-4 text-sm text-gray-500">Tidak ada order pending</div>}
                        {orders.map(o => (
                            <button key={o.id} onClick={() => setSelected(o)}
                                className={`w-full text-left p-3 hover:bg-gray-50 ${selected?.id === o.id ? 'bg-blue-50' : ''}`}>
                                <div className="flex items-center justify-between">
                                    <span className="font-medium text-sm">#{o.id} {o.customer_name}</span>
                                    <span className="text-xs text-gray-500">{o.package}</span>
                                </div>
                                <div className="text-xs text-gray-500 mt-0.5">📍 {o.village}</div>
                            </button>
                        ))}
                    </div>
                </div>

                {/* Map */}
                <div className="col-span-6 card overflow-hidden">
                    {selected?.gps_lat && selected?.gps_long ? (
                        <MapContainer center={[selected.gps_lat, selected.gps_long]} zoom={15} className="h-full w-full">
                            <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                            <Marker position={[selected.gps_lat, selected.gps_long]}>
                                <Popup>📍 {selected.customer_name}</Popup>
                            </Marker>
                            {odps.map(odp => (
                                <Marker key={odp.id} position={[odp.lat, odp.lng]}
                                    eventHandlers={{ click: () => setPickedOdp(odp) }}>
                                    <Popup>
                                        <strong>{odp.code}</strong> ({odp.name})<br/>
                                        Jarak: {Math.round(odp.distance_m)}m
                                    </Popup>
                                </Marker>
                            ))}
                            {pickedOdp && (
                                <>
                                    <Circle center={[selected.gps_lat, selected.gps_long]}
                                        radius={pickedOdp.distance_m}
                                        pathOptions={{ color: pickedOdp.distance_m <= 300 ? 'green' : 'red' }} />
                                    <Polyline
                                        positions={[[selected.gps_lat, selected.gps_long], [pickedOdp.lat, pickedOdp.lng]]}
                                        pathOptions={{ color: 'blue' }} />
                                </>
                            )}
                        </MapContainer>
                    ) : (
                        <div className="h-full flex items-center justify-center text-gray-400">Pilih order</div>
                    )}
                </div>

                {/* Detail + action */}
                <div className="col-span-3 space-y-3 overflow-y-auto">
                    {selected && (
                        <div className="card">
                            <div className="card-header"><h3 className="font-semibold">#{selected.id} {selected.customer_name}</h3></div>
                            <div className="card-body text-sm space-y-1">
                                <div>📞 {selected.customer_phone}</div>
                                <div>📍 {selected.village}</div>
                                <div>📦 {selected.package}</div>
                                <div>🌐 GPS: {selected.gps_lat?.toFixed(5)}, {selected.gps_long?.toFixed(5)}</div>
                            </div>
                        </div>
                    )}

                    {pickedOdp && (
                        <div className={`card ${pickedOdp.distance_m <= 300 ? 'bg-emerald-50 border-emerald-300' : 'bg-red-50 border-red-300'}`}>
                            <div className="card-header"><h3 className="font-semibold">ODP Dipilih</h3></div>
                            <div className="card-body text-sm space-y-2">
                                <div><strong>{pickedOdp.code}</strong> ({pickedOdp.name})</div>
                                <div>Jarak: <span className="font-bold">{Math.round(pickedOdp.distance_m)}m</span></div>
                                <div className={pickedOdp.distance_m <= 300 ? 'text-emerald-700' : 'text-red-700'}>
                                    {pickedOdp.distance_m <= 300 ? '✓ Dalam coverage (≤300m)' : '✗ Di luar coverage (>300m)'}
                                </div>
                                <div className="flex gap-2 pt-2">
                                    {pickedOdp.distance_m <= 300 && (
                                        <button onClick={approve} className="btn btn-success flex-1">Approve</button>
                                    )}
                                    <button onClick={() => setShowRejectModal(true)} className="btn btn-danger flex-1">Reject</button>
                                </div>
                            </div>
                        </div>
                    )}

                    {odps.length > 0 && !pickedOdp && (
                        <div className="card">
                            <div className="card-header"><h3 className="font-semibold text-sm">ODP Terdekat</h3></div>
                            <div className="card-body text-xs space-y-2">
                                {odps.slice(0, 5).map(odp => (
                                    <button key={odp.id} onClick={() => setPickedOdp(odp)}
                                        className="w-full text-left p-2 border rounded hover:bg-blue-50">
                                        <div className="font-mono">{odp.code}</div>
                                        <div className="text-gray-500">{Math.round(odp.distance_m)}m</div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {showRejectModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="card max-w-md w-full m-4">
                        <div className="card-header"><h3 className="font-semibold">Reject — Note Revisi</h3></div>
                        <div className="card-body space-y-3">
                            <textarea className="form-input" rows={3} value={revisionNote}
                                onChange={e => setRevisionNote(e.target.value)}
                                placeholder="mis. alamat pelanggan typo, ODP perlu survey ulang" />
                            <div className="text-xs text-gray-500">
                                Catatan: order akan masuk status <b>rejected</b> dan teknisi bisa revert ke provisioning (bukan ke sales).
                            </div>
                            <div className="flex gap-2">
                                <button onClick={() => setShowRejectModal(false)} className="btn btn-ghost flex-1">Batal</button>
                                <button onClick={reject} disabled={!revisionNote} className="btn btn-danger flex-1">Kirim Reject</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </PsbLayout>
    );
}
