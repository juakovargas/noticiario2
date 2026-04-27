import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';
import Form from './Form';

export default function Create(props: any): JSX.Element {
  return <EditorLayout><Head title="Create Schedule" /><AdminPageHeader title="Create Schedule" description="Create recurring or one-off editorial schedules." /><Card><CardContent className="pt-6"><Form {...props} /></CardContent></Card></EditorLayout>;
}
