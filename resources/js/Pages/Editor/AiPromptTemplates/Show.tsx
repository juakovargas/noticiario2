import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';
interface T { name:string; type:string; description:string|null; system_prompt:string|null; user_prompt:string; expected_output_format:string; is_active:boolean; }
export default function Show({ template }: { template:T }): JSX.Element {
    return <EditorLayout><Head title={template.name} /><AdminPageHeader title={template.name} description="Prompt template details." /><Card><CardContent className="space-y-3 pt-6 text-sm"><p><strong>Type:</strong> {template.type}</p><p><strong>Output:</strong> {template.expected_output_format}</p><p><strong>Active:</strong> {template.is_active ? 'Yes' : 'No'}</p><p><strong>Description:</strong> {template.description || '-'}</p><div><strong>System prompt</strong><pre className="mt-1 whitespace-pre-wrap rounded bg-slate-100 p-3">{template.system_prompt || '-'}</pre></div><div><strong>User prompt</strong><pre className="mt-1 whitespace-pre-wrap rounded bg-slate-100 p-3">{template.user_prompt}</pre></div></CardContent></Card></EditorLayout>;
}
