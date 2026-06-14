interface Props {
    status: string;
    label?: string;
}

const COLORS: Record<string, string> = {
    draft:        'bg-gray-500',
    submitted:    'bg-blue-500',
    coverage_ok:  'bg-cyan-500',
    assigned:     'bg-yellow-500',
    provisioning: 'bg-orange-500',
    photos:       'bg-purple-500',
    done:         'bg-emerald-500',
    rejected:     'bg-red-500',
};

const LABELS: Record<string, string> = {
    draft: 'Draft', submitted: 'Submitted', coverage_ok: 'Coverage OK',
    assigned: 'Assigned', provisioning: 'Provisioning', photos: 'Dokumentasi',
    done: 'Selesai', rejected: 'Revisi',
};

export default function StatusBadge({ status, label }: Props) {
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white ${COLORS[status] || 'bg-gray-400'}`}>
            {label || LABELS[status] || status}
        </span>
    );
}
