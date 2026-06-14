import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import PsbLayout from '@/Layouts/PsbLayout';
import toast from 'react-hot-toast';

interface Props {
    recentRegistrations?: any[];
}

export default function PsbInput({ recentRegistrations = [] }: Props) {
    const form = useForm({
        registration_id: '',
        customer_name: '',
        customer_phone: '',
        customer_nik: '',
        customer_email: '',
        customer_address: '',
        rt: '',
        rw: '',
        village: '',
        district: '',
        package: '10M',
        router_name: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/psb/orders', {
            onSuccess: () => toast.success('Order submitted'),
            onError: (e) => toast.error('Gagal: ' + JSON.stringify(e)),
        });
    };

    return (
        <PsbLayout title="Input PSB Baru">
            <Head title="Input PSB" />
            <form onSubmit={handleSubmit} className="max-w-3xl space-y-6">
                {/* Sales Info */}
                <div className="card">
                    <div className="card-header"><h3 className="font-semibold">📋 Info Saleskit</h3></div>
                    <div className="card-body grid grid-cols-2 gap-4">
                        <div>
                            <label className="form-label">Registration ID (dari Saleskit)</label>
                            <input className="form-input" value={form.data.registration_id}
                                onChange={e => form.setData('registration_id', e.target.value)}
                                placeholder="REG-XXXXX" required />
                            {form.errors.registration_id && <div className="form-error">{form.errors.registration_id}</div>}
                        </div>
                        <div>
                            <label className="form-label">Paket Internet</label>
                            <select className="form-input" value={form.data.package}
                                onChange={e => form.setData('package', e.target.value)}>
                                <option>10M</option><option>15M</option><option>25M</option>
                                <option>30M</option><option>35M</option><option>50M</option>
                                <option>100M</option><option>200M</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Customer Info */}
                <div className="card">
                    <div className="card-header"><h3 className="font-semibold">👤 Data Pelanggan</h3></div>
                    <div className="card-body grid grid-cols-2 gap-4">
                        <div>
                            <label className="form-label">Nama lengkap *</label>
                            <input className="form-input" value={form.data.customer_name}
                                onChange={e => form.setData('customer_name', e.target.value)} required />
                            {form.errors.customer_name && <div className="form-error">{form.errors.customer_name}</div>}
                        </div>
                        <div>
                            <label className="form-label">No HP *</label>
                            <input className="form-input" value={form.data.customer_phone}
                                onChange={e => form.setData('customer_phone', e.target.value)} required />
                        </div>
                        <div>
                            <label className="form-label">NIK</label>
                            <input className="form-input" value={form.data.customer_nik}
                                onChange={e => form.setData('customer_nik', e.target.value)} />
                        </div>
                        <div>
                            <label className="form-label">Email</label>
                            <input className="form-input" type="email" value={form.data.customer_email}
                                onChange={e => form.setData('customer_email', e.target.value)} />
                        </div>
                        <div className="col-span-2">
                            <label className="form-label">Alamat lengkap *</label>
                            <textarea className="form-input" rows={2} value={form.data.customer_address}
                                onChange={e => form.setData('customer_address', e.target.value)} required />
                        </div>
                        <div>
                            <label className="form-label">RT *</label>
                            <input className="form-input" value={form.data.rt}
                                onChange={e => form.setData('rt', e.target.value)} required />
                        </div>
                        <div>
                            <label className="form-label">RW *</label>
                            <input className="form-input" value={form.data.rw}
                                onChange={e => form.setData('rw', e.target.value)} required />
                        </div>
                        <div>
                            <label className="form-label">Desa/Kelurahan *</label>
                            <input className="form-input" value={form.data.village}
                                onChange={e => form.setData('village', e.target.value)} required />
                        </div>
                        <div>
                            <label className="form-label">Kecamatan *</label>
                            <input className="form-input" value={form.data.district}
                                onChange={e => form.setData('district', e.target.value)} required />
                        </div>
                    </div>
                </div>

                {/* Router Info — PENTING (jawaban #10) */}
                <div className="card border-yellow-300 bg-yellow-50/50">
                    <div className="card-header"><h3 className="font-semibold">🔌 Router MikroTik (PENTING)</h3></div>
                    <div className="card-body">
                        <label className="form-label">Nama Router (sesuai Saleskit)</label>
                        <input className="form-input" value={form.data.router_name}
                            onChange={e => form.setData('router_name', e.target.value)} required
                            placeholder="contoh: mangliawan, krebet, sumberoto" />
                        <p className="text-xs text-gray-500 mt-1">
                            Password PPPoE akan di-generate dari nama router ini (lowercase).
                            Berlaku untuk C300 dan HiOS.
                        </p>
                        {form.errors.router_name && <div className="form-error">{form.errors.router_name}</div>}
                    </div>
                </div>

                <div className="flex justify-end gap-2">
                    <button type="button" onClick={() => router.visit('/psb')} className="btn btn-ghost">Batal</button>
                    <button type="submit" disabled={form.processing} className="btn btn-primary">
                        {form.processing ? 'Submitting...' : 'Submit PSB'}
                    </button>
                </div>
            </form>
        </PsbLayout>
    );
}
