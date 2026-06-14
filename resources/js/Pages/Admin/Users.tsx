import { Head, Link, router } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    email_verified_at: string | null;
    roles: string[];
    created_at: string;
}

interface Props {
    users: User[];
    roles: string[];
}

export default function AdminUsers({ users, roles }: Props) {
    const handleDelete = (user: User) => {
        if (!confirm(`Delete user ${user.email}? This cannot be undone.`)) return;
        router.delete(`/admin/users/${user.id}`);
    };

    return (
        <>
            <Head title="Users" />
            <div className="min-h-screen bg-gray-50 p-8">
                <div className="max-w-6xl mx-auto">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold">Users</h1>
                            <p className="text-sm text-gray-600">
                                {users.length} user{users.length !== 1 ? 's' : ''} • {roles.length} role{roles.length !== 1 ? 's' : ''} defined
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Link href="/admin" className="btn btn-secondary">← Dashboard</Link>
                            <Link href="/admin/users/create" className="btn btn-primary">+ New User</Link>
                        </div>
                    </div>

                    {users.length === 0 ? (
                        <div className="card p-12 text-center text-gray-500">
                            <p className="mb-4">No users yet.</p>
                            <Link href="/admin/users/create" className="btn btn-primary">Create the first user</Link>
                        </div>
                    ) : (
                        <div className="card overflow-hidden">
                            <table className="w-full">
                                <thead className="bg-gray-100 text-left text-sm">
                                    <tr>
                                        <th className="p-3">ID</th>
                                        <th className="p-3">Name</th>
                                        <th className="p-3">Email</th>
                                        <th className="p-3">Phone</th>
                                        <th className="p-3">Roles</th>
                                        <th className="p-3">Verified</th>
                                        <th className="p-3">Created</th>
                                        <th className="p-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.map(u => (
                                        <tr key={u.id} className="border-t hover:bg-gray-50">
                                            <td className="p-3 font-mono text-sm">{u.id}</td>
                                            <td className="p-3">{u.name}</td>
                                            <td className="p-3 text-sm">{u.email}</td>
                                            <td className="p-3 text-sm text-gray-500">{u.phone || '—'}</td>
                                            <td className="p-3">
                                                {u.roles.length === 0 ? (
                                                    <span className="text-gray-400 text-xs">no roles</span>
                                                ) : (
                                                    u.roles.map(r => (
                                                        <span key={r} className="inline-block text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded mr-1">
                                                            {r}
                                                        </span>
                                                    ))
                                                )}
                                            </td>
                                            <td className="p-3 text-sm">
                                                {u.email_verified_at ? (
                                                    <span className="text-green-600">✓</span>
                                                ) : (
                                                    <span className="text-gray-400">—</span>
                                                )}
                                            </td>
                                            <td className="p-3 text-sm text-gray-500">{u.created_at}</td>
                                            <td className="p-3 text-right">
                                                <Link
                                                    href={`/admin/users/${u.id}/edit`}
                                                    className="text-blue-600 hover:underline text-sm mr-3"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(u)}
                                                    className="text-red-600 hover:underline text-sm"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
