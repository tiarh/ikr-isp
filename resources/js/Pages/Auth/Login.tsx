import { useState } from 'react';
import { router } from '@inertiajs/react';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/login', { email, password });
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-gray-100">
            <form onSubmit={submit} className="card p-8 w-full max-w-sm space-y-4">
                <div className="text-center">
                    <h1 className="text-2xl font-bold">IKR ISP</h1>
                    <p className="text-sm text-gray-600">PSB Management System</p>
                </div>
                <div>
                    <label className="form-label">Email</label>
                    <input type="email" className="form-input" value={email} onChange={e => setEmail(e.target.value)} required />
                </div>
                <div>
                    <label className="form-label">Password</label>
                    <input type="password" className="form-input" value={password} onChange={e => setPassword(e.target.value)} required />
                </div>
                <button type="submit" className="btn btn-primary w-full">Login</button>
            </form>
        </div>
    );
}
