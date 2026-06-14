import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import toast from 'react-hot-toast';
import SignatureCanvas from 'react-signature-canvas';

interface ChecklistItem {
    id: number; item_key: string; item_label: string; is_checked: boolean;
    checked_at?: string; notes?: string;
}

interface Order {
    id: number; customer_name: string; status: string;
    foto_rumah_path?: string; foto_modem_path?: string; foto_ktp_path?: string;
    foto_odp_path?: string; foto_odp_dalam_path?: string; foto_router_path?: string;
    redaman_odp?: number; redaman_router?: number;
    gps_lat?: number; gps_long?: number;
    bai_pdf_path?: string; bai_signed_at?: string;
    olt_type?: string;
    hioso_checklist?: ChecklistItem[];
}

const PHOTO_STEPS = [
    { key: 'rumah',     label: 'Foto Rumah',     field: 'foto_rumah_path' },
    { key: 'modem',     label: 'Foto Modem/ONT', field: 'foto_modem_path' },
    { key: 'ktp',       label: 'Foto KTP',       field: 'foto_ktp_path' },
    { key: 'odp',       label: 'Foto ODP (luar)',field: 'foto_odp_path' },
    { key: 'odp_dalam', label: 'Foto ODP (dalam)', field: 'foto_odp_dalam_path' },
    { key: 'router',    label: 'Foto Router',    field: 'foto_router_path' },
];

export default function PsbDocuments({ order }: { order: Order }) {
    const [step, setStep] = useState(0);
    const [gps, setGps] = useState<{ lat: number; lng: number } | null>(
        order.gps_lat && order.gps_long ? { lat: order.gps_lat, lng: order.gps_long } : null
    );
    const [redaman, setRedaman] = useState({ odp: order.redaman_odp?.toString() || '', router: order.redaman_router?.toString() || '' });
    const sigRef = useRef<SignatureCanvas>(null);

    const getGps = () => {
        if (!navigator.geolocation) return toast.error('Geolocation gak support');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                setGps({ lat: pos.coords.latitude, lng: pos.coords.longitude });
                toast.success('GPS captured');
            },
            () => toast.error('GPS failed'),
        );
    };

    const uploadPhoto = async (type: string) => {
        const input = document.createElement('input');
        input.type = 'file'; input.accept = 'image/*'; input.capture = 'environment';
        input.onchange = async (e: any) => {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('photo', file);

            // Get fresh CSRF token
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const xsrf = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '';

            try {
                const res = await fetch(`/psb/orders/${order.id}/photo-api/${type}`, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-XSRF-TOKEN': decodeURIComponent(xsrf),
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    const data = await res.json();
                    toast.success(`${type} uploaded`);
                    router.reload();
                } else {
                    const err = await res.text();
                    toast.error(`Upload gagal: ${res.status}`);
                }
            } catch (err: any) {
                toast.error(`Network error: ${err.message}`);
            }
        };
        input.click();
    };

    const saveMeasurements = () => {
        router.post(`/psb/orders/${order.id}/measurements`, {
            redaman_odp: parseFloat(redaman.odp) || 0,
            redaman_router: parseFloat(redaman.router) || 0,
            gps_lat: gps?.lat || 0,
            gps_long: gps?.lng || 0,
        }, { onSuccess: () => toast.success('Measurements saved') });
    };

    const toggleChecklist = (item: ChecklistItem) => {
        router.post(`/psb/checklist/${item.id}/toggle`, {
            is_checked: !item.is_checked,
        }, { onSuccess: () => router.reload() });
    };

    const saveBai = () => {
        if (!sigRef.current || sigRef.current.isEmpty()) return toast.error('Ttd dulu');
        const dataUrl = sigRef.current.toDataURL('image/png');
        router.post(`/psb/orders/${order.id}/bai`, { signature: dataUrl }, {
            onSuccess: () => { toast.success('BAI PDF generated'); router.reload(); },
        });
    };

    return (
        <PsbLayout title={`Dokumentasi #${order.id} - ${order.customer_name}`}>
            <div className="grid grid-cols-12 gap-4">
                <div className="col-span-3 card">
                    <div className="card-header"><h3 className="font-semibold">Steps</h3></div>
                    <div className="card-body space-y-1">
                        {PHOTO_STEPS.map((s, idx) => (
                            <button key={s.key} onClick={() => setStep(idx)}
                                className={`w-full text-left p-2 rounded text-sm ${
                                    step === idx ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50'
                                }`}>
                                {idx + 1}. {s.label}
                                {order[s.field as keyof Order] && <span className="ml-2 text-emerald-600">✓</span>}
                            </button>
                        ))}
                        <div className="border-t my-2"></div>
                        <button onClick={() => setStep(6)}
                            className={`w-full text-left p-2 rounded text-sm ${step === 6 ? 'bg-blue-50' : 'hover:bg-gray-50'}`}>
                            7. Redaman & GPS
                        </button>
                        {order.olt_type === 'hioso' && (
                            <button onClick={() => setStep(7)}
                                className={`w-full text-left p-2 rounded text-sm ${step === 7 ? 'bg-blue-50' : 'hover:bg-gray-50'}`}>
                                8. HiOS Checklist
                            </button>
                        )}
                        <button onClick={() => setStep(8)}
                            className={`w-full text-left p-2 rounded text-sm ${step === 8 ? 'bg-blue-50' : 'hover:bg-gray-50'}`}>
                            {order.olt_type === 'hioso' ? '9' : '8'}. BAI (ttd digital)
                        </button>
                    </div>
                </div>

                <div className="col-span-9 card">
                    <div className="card-header"><h3 className="font-semibold">Step {step + 1}</h3></div>
                    <div className="card-body">
                        {step < 6 && (
                            <div className="space-y-3">
                                <h4 className="font-medium text-lg">{PHOTO_STEPS[step].label}</h4>
                                {order[PHOTO_STEPS[step].field as keyof Order] ? (
                                    <div>
                                        <img src={`/storage/${order[PHOTO_STEPS[step].field as keyof Order]}`}
                                            className="max-w-md rounded-lg border" />
                                        <button onClick={() => uploadPhoto(PHOTO_STEPS[step].key)} className="btn btn-ghost mt-2">Re-upload</button>
                                    </div>
                                ) : (
                                    <button onClick={() => uploadPhoto(PHOTO_STEPS[step].key)} className="btn btn-primary">
                                        📷 Ambil/Upload Foto
                                    </button>
                                )}
                                <div className="flex gap-2 pt-4 border-t mt-4">
                                    <button onClick={() => setStep(Math.max(0, step - 1))} disabled={step === 0} className="btn btn-ghost">← Prev</button>
                                    <button onClick={() => setStep(Math.min(8, step + 1))} className="btn btn-primary">Next →</button>
                                </div>
                            </div>
                        )}

                        {step === 6 && (
                            <div className="space-y-4">
                                <h4 className="font-medium text-lg">Redaman & GPS</h4>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="form-label">Redaman ODP (dB)</label>
                                        <input className="form-input" type="number" step="0.01" value={redaman.odp}
                                            onChange={e => setRedaman({ ...redaman, odp: e.target.value })} />
                                    </div>
                                    <div>
                                        <label className="form-label">Redaman Router (dB)</label>
                                        <input className="form-input" type="number" step="0.01" value={redaman.router}
                                            onChange={e => setRedaman({ ...redaman, router: e.target.value })} />
                                    </div>
                                </div>
                                <div>
                                    <label className="form-label">GPS</label>
                                    <div className="flex gap-2">
                                        <input className="form-input" value={gps ? `${gps.lat.toFixed(7)}, ${gps.lng.toFixed(7)}` : ''} readOnly />
                                        <button onClick={getGps} className="btn btn-primary whitespace-nowrap">📍 Ambil GPS</button>
                                    </div>
                                </div>
                                <button onClick={saveMeasurements} className="btn btn-success">Simpan Pengukuran</button>
                            </div>
                        )}

                        {step === 7 && order.olt_type === 'hioso' && order.hioso_checklist && order.hioso_checklist.length > 0 && (
                            <div className="space-y-3">
                                <h4 className="font-medium text-lg">HiOS Manual Checklist</h4>
                                <p className="text-sm text-gray-600">Centang tiap step yang sudah dilakukan teknisi di router pelanggan.</p>
                                <div className="space-y-2">
                                    {order.hioso_checklist?.map((item: any) => (
                                        <label key={item.id} className="flex items-start gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" checked={!!item.is_checked}
                                                onChange={() => toggleChecklist(item)} className="mt-1" />
                                            <div className="flex-1">
                                                <div className="font-medium text-sm">{item.item_label}</div>
                                                {item.checked_at && <div className="text-xs text-gray-500">✓ {new Date(item.checked_at).toLocaleString('id-ID')}</div>}
                                                {item.notes && <div className="text-xs text-gray-600 mt-1">Note: {item.notes}</div>}
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        )}

                        {step === 7 && order.olt_type === 'hioso' && (!order.hioso_checklist || order.hioso_checklist.length === 0) && (
                            <div className="text-sm text-yellow-700 bg-yellow-50 p-3 rounded">
                                Checklist HiOS belum dibuat. Trigger dengan memilih OLT HiOS di step Provisioning dulu.
                            </div>
                        )}

                        {step === 8 && (
                            <div className="space-y-4">
                                <h4 className="font-medium text-lg">BAI: Tanda Tangan Pelanggan</h4>
                                {order.bai_signed_at ? (
                                    <div className="bg-emerald-50 border border-emerald-200 p-4 rounded">
                                        <div className="text-emerald-700 font-medium">✓ BAI signed at {new Date(order.bai_signed_at).toLocaleString('id-ID')}</div>
                                        <a href={`/storage/${order.bai_pdf_path}`} target="_blank" className="btn btn-primary mt-3 inline-block">📄 Download PDF</a>
                                    </div>
                                ) : (
                                    <>
                                        <p className="text-sm text-gray-600">Tanda tangani di bawah ini:</p>
                                        <div className="border-2 border-dashed border-gray-300 rounded-lg bg-white">
                                            <SignatureCanvas ref={sigRef}
                                                canvasProps={{ width: 600, height: 200, className: 'w-full' }} />
                                        </div>
                                        <div className="flex gap-2">
                                            <button onClick={() => sigRef.current?.clear()} className="btn btn-ghost">Clear</button>
                                            <button onClick={saveBai} className="btn btn-success">Simpan & Generate PDF</button>
                                        </div>
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </PsbLayout>
    );
}
