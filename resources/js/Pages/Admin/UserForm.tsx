import { Head, useForm, Link } from '@inertiajs/react';

interface Role {
    name: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    roles: string[];
}

interface Props {
    roles: string[];
    user: UserData | null;
}

export default function UserForm({ roles, user }: Props) {
    const isEdit = !!user;
    const form = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        password: '',
        password_confirmation: '',
        roles: user?.roles ?? [],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            form.put(`/admin/users/${user!.id}`);
        } else {
            form.post('/admin/users');
        }
    };

    return (
        <>
            <Head title={isEdit ? 'Edit User' : 'Create User'} />
            <div className="min-h-screen bg-gray-50 p-8">
                <div className="max-w-2xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-2xl font-bold">
                            {isEdit ? `Edit User: ${user!.email}` : 'Create New User'}
                        </h1>
                        <Link href="/admin/users" className="btn btn-secondary">← Back</Link>
                    </div>

                    <form onSubmit={submit} className="card p-6 space-y-4">
                        <div>
                            <label className="form-label">Name <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                className="form-input"
                                value={form.data.name}
                                onChange={e => form.setData('name', e.target.value)}
                                required
                            />
                            {form.errors.name && <p className="text-red-500 text-sm mt-1">{form.errors.name}</p>}
                        </div>

                        <div>
                            <label className="form-label">Email <span className="text-red-500">*</span></label>
                            <input
                                type="email"
                                className="form-input"
                                value={form.data.email}
                                onChange={e => form.setData('email', e.target.value)}
                                required
                            />
                            {form.errors.email && <p className="text-red-500 text-sm mt-1">{form.errors.email}</p>}
                        </div>

                        <div>
                            <label className="form-label">Phone</label>
                            <input
                                type="tel"
                                className="form-input"
                                value={form.data.phone}
                                onChange={e => form.setData('phone', e.target.value)}
                            />
                            {form.errors.phone && <p className="text-red-500 text-sm mt-1">{form.errors.phone}</p>}
                        </div>

                        <div>
                            <label className="form-label">
                                Password {isEdit && <span className="text-gray-400 text-xs">(leave blank to keep current)</span>}
                            </label>
                            <input
                                type="password"
                                className="form-input"
                                value={form.data.password}
                                onChange={e => form.setData('password', e.target.value)}
                                autoComplete="new-password"
                            />
                            {form.errors.password && <p className="text-red-500 text-sm mt-1">{form.errors.password}</p>}
                        </div>

                        <div>
                            <label className="form-label">Confirm Password</label>
                            <input
                                type="password"
                                className="form-input"
                                value={form.data.password_confirmation}
                                onChange={e => form.setData('password_confirmation', e.target.value)}
                                autoComplete="new-password"
                            />
                        </div>

                        <div>
                            <label className="form-label">Roles</label>
                            <div className="space-y-2 mt-1">
                                {roles.map(r => (
                                    <label key={r} className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            className="form-checkbox"
                                            checked={form.data.roles.includes(r)}
                                            onChange={e => {
                                                if (e.target.checked) {
                                                    form.setData('roles', [...form.data.roles, r]);
                                                } else {
                                                    form.setData('roles', form.data.roles.filter(x => x !== r));
                                                }
                                            }}
                                        />
                                        <span className="text-sm">{r}</span>
                                    </label>
                                ))}
                            </div>
                            {form.errors.roles && <p className="text-red-500 text-sm mt-1">{form.errors.roles}</p>}
                        </div>

                        <div className="flex items-center justify-end gap-2 pt-4 border-t">
                            <Link href="/admin/users" className="btn btn-secondary">Cancel</Link>
                            <button type="submit" className="btn btn-primary" disabled={form.processing}>
                                {form.processing ? 'Saving...' : (isEdit ? 'Update User' : 'Create User')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
