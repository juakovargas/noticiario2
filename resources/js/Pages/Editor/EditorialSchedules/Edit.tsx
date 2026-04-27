import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';
import Form from './Form';

export default function Edit(props: any): JSX.Element {
  return <EditorLayout><Head title="Edit Schedule" /><AdminPageHeader title="Edit Schedule" description="Update editorial schedule settings." /><Card><CardContent className="pt-6"><Form {...props} /></CardContent></Card></EditorLayout>;
}
