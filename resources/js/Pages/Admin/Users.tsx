import { Head, Link } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    email_verified_at: string | null;
    roles: string[];
    created_at: string;
}

export default function AdminUsers({ users }: { users: User[] }) {
    return (
        <>
            <Head title="Users" />
            <div className="min-h-screen bg-gray-50 p-8">
                <div className="max-w-6xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-2xl font-bold">Users</h1>
                        <Link href="/admin" className="btn btn-secondary">← Dashboard</Link>
                    </div>
                    <div className="card overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-100 text-left text-sm">
                                <tr>
                                    <th className="p-3">ID</th>
                                    <th className="p-3">Name</th>
                                    <th className="p-3">Email</th>
                                    <th className="p-3">Roles</th>
                                    <th className="p-3">Verified</th>
                                    <th className="p-3">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map(u => (
                                    <tr key={u.id} className="border-t hover:bg-gray-50">
                                        <td className="p-3 font-mono text-sm">{u.id}</td>
                                        <td className="p-3">{u.name}</td>
                                        <td className="p-3 text-sm">{u.email}</td>
                                        <td className="p-3">
                                            {u.roles.map(r => (
                                                <span key={r} className="inline-block text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded mr-1">{r}</span>
                                            ))}
                                        </td>
                                        <td className="p-3 text-sm">
                                            {u.email_verified_at ? '✓' : '—'}
                                        </td>
                                        <td className="p-3 text-sm text-gray-500">{u.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}
