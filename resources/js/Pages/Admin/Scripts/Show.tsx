import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

export default function Show({ script }: any) {
  return <AdminLayout><Head title="Script" />
    <div className="space-y-2 rounded border p-4">
      <h1 className="text-lg font-semibold">{script.final_title ?? script.title}</h1>
      <p>Status: {script.review_status}</p>
      <p>Creator: {script.bulletin_prompt_run?.created_by?.name ?? '-'}</p>
    </div>
  </AdminLayout>
}
