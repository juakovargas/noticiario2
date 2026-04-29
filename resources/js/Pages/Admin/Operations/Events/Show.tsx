import AdminLayout from '@/Layouts/AdminLayout'; import { Head } from '@inertiajs/react';
export default function Show({event}:any){ return <AdminLayout><Head title={`Event #${event.id}`} /><pre className='rounded border p-4 text-xs'>{JSON.stringify(event,null,2)}</pre></AdminLayout>}
