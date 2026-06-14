import { useState } from 'react';
import { router } from '@inertiajs/react';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import PsbLayout from '@/Layouts/PsbLayout';
import StatusBadge from '@/Components/Psb/StatusBadge';
import toast from 'react-hot-toast';

const COLUMNS = [
    { id: 'draft', label: 'Draft', color: 'bg-gray-100' },
    { id: 'submitted', label: 'Submitted', color: 'bg-blue-50' },
    { id: 'coverage_ok', label: 'Coverage OK', color: 'bg-cyan-50' },
    { id: 'assigned', label: 'Assigned', color: 'bg-yellow-50' },
    { id: 'provisioning', label: 'Provisioning', color: 'bg-orange-50' },
    { id: 'photos', label: 'Dokumentasi', color: 'bg-purple-50' },
    { id: 'done', label: 'Selesai', color: 'bg-emerald-50' },
];

interface Order { id: number; customer_name: string; village: string; package: string; status: string; teknisi?: string; }

export default function Pipeline({ orders }: { orders: Order[] }) {
    const grouped = COLUMNS.reduce((acc, col) => {
        acc[col.id] = orders.filter(o => o.status === col.id);
        return acc;
    }, {} as Record<string, Order[]>);

    const onDragEnd = (result: any) => {
        if (!result.destination) return;
        const orderId = result.draggableId;
        const toStatus = result.destination.droppableId;
        if (result.source.droppableId === toStatus) return;

        // Optimistic update via PATCH
        router.patch(`/psb/orders/${orderId}/status`, { status: toStatus }, {
            onSuccess: () => toast.success(`Moved to ${toStatus}`),
            onError: () => toast.error('Cannot transition'),
            preserveScroll: true,
        });
    };

    return (
        <PsbLayout title="Pipeline PSB">
            <DragDropContext onDragEnd={onDragEnd}>
                <div className="flex gap-3 overflow-x-auto pb-4">
                    {COLUMNS.map(col => (
                        <Droppable key={col.id} droppableId={col.id}>
                            {(provided, snap) => (
                                <div ref={provided.innerRef} {...provided.droppableProps}
                                    className={`w-72 flex-shrink-0 rounded-xl ${col.color} p-3 ${
                                        snap.isDraggingOver ? 'ring-2 ring-blue-400' : ''
                                    }`}>
                                    <div className="flex items-center justify-between mb-3 px-1">
                                        <h3 className="font-semibold text-sm">{col.label}</h3>
                                        <span className="bg-white text-xs px-2 py-0.5 rounded-full">
                                            {grouped[col.id]?.length || 0}
                                        </span>
                                    </div>
                                    <div className="space-y-2 min-h-[100px]">
                                        {grouped[col.id]?.map((o, idx) => (
                                            <Draggable key={o.id} draggableId={String(o.id)} index={idx}>
                                                {(prov, snap2) => (
                                                    <div ref={prov.innerRef} {...prov.draggableProps} {...prov.dragHandleProps}
                                                        onClick={() => router.visit(`/psb/orders/${o.id}`)}
                                                        className={`bg-white p-3 rounded-lg shadow-sm border cursor-pointer ${
                                                            snap2.isDragging ? 'shadow-lg rotate-1' : ''
                                                        }`}>
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-medium text-sm">#{o.id}</span>
                                                            <span className="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{o.package}</span>
                                                        </div>
                                                        <div className="font-medium text-sm mt-1">{o.customer_name}</div>
                                                        <div className="text-xs text-gray-500 mt-1">📍 {o.village}</div>
                                                        {o.teknisi && <div className="text-xs text-gray-600 mt-1">🔧 {o.teknisi}</div>}
                                                    </div>
                                                )}
                                            </Draggable>
                                        ))}
                                        {provided.placeholder}
                                    </div>
                                </div>
                            )}
                        </Droppable>
                    ))}
                </div>
            </DragDropContext>
        </PsbLayout>
    );
}
