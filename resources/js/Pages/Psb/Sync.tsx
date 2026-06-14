import { router } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import toast from 'react-hot-toast';

export default function Sync({ order, preview }: { order: any; preview: any }) {
    const doSync = () => {
        if (!confirm('Sync ke eBilling sekarang? Status akan jadi Done.')) return;
        router.post(`/psb/orders/${order.id}/sync`, {}, {
            onSuccess: () => toast.success('Synced! Order DONE.'),
            onError: () => toast.error('Sync gagal'),
        });
    };

    return (
        <PsbLayout title={`Sync eBilling #${order.id}`}>
            <div className="grid grid-cols-2 gap-4">
                <div className="card">
                    <div className="card-header"><h3 className="font-semibold">📋 Preview Data</h3></div>
                    <div className="card-body space-y-3 text-sm">
                        <Section title="Customer">
                            <KV k="Code" v={preview.customer.code} />
                            <KV k="Name" v={preview.customer.name} />
                            <KV k="Phone" v={preview.customer.phone} />
                            <KV k="Address" v={preview.customer.address} />
                            <KV k="Package" v={preview.customer.package} />
                            <KV k="Router" v={preview.customer.router} />
                        </Section>
                        <Section title="PPPoE">
                            <KV k="User" v={preview.pppoe.user} />
                            <KV k="Password" v={preview.pppoe.password} />
                        </Section>
                        <Section title="OLT">
                            <KV k="Type" v={preview.olt.type?.toUpperCase()} />
                            <KV k="Port" v={preview.olt.port} />
                            <KV k="SN" v={preview.olt.sn} />
                        </Section>
                    </div>
                </div>

                <div className="card">
                    <div className="card-header"><h3 className="font-semibold">📂 File Upload Status</h3></div>
                    <div className="card-body space-y-2 text-sm">
                        {Object.entries(preview.files_uploaded).map(([k, v]) => (
                            <div key={k} className="flex items-center justify-between">
                                <span>{k}</span>
                                <span className={v ? 'text-emerald-600' : 'text-red-600'}>{v ? '✓' : '✗'}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <div className="card mt-4">
                <div className="card-body flex items-center justify-between">
                    <div>
                        <h3 className="font-semibold">🚀 Sync ke eBilling</h3>
                        <p className="text-sm text-gray-600">
                            1. POST customer · 2. Generate invoice (join_date=provisioned_at) · 3. INSERT RADIUS · 4. Upload 6 foto + BAI
                        </p>
                    </div>
                    <button onClick={doSync} className="btn btn-success text-lg px-6 py-3">Sync Sekarang</button>
                </div>
            </div>
        </PsbLayout>
    );
}

function Section({ title, children }: any) {
    return (
        <div>
            <h4 className="font-medium text-xs uppercase text-gray-500 mb-1">{title}</h4>
            <div className="space-y-0.5">{children}</div>
        </div>
    );
}

function KV({ k, v }: { k: string; v: any }) {
    return (
        <div className="flex">
            <span className="w-24 text-gray-500">{k}</span>
            <span className="font-medium">{v || '-'}</span>
        </div>
    );
}
