import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

interface FormData {
    name: string;
}

export default function PermissionsCreate(): JSX.Element {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
    });

    const submit = (event: React.FormEvent): void => {
        event.preventDefault();
        post(route('admin.permissions.store'));
    };

    return (
        <AdminLayout>
            <Head title="Create Permission" />

            <AdminPageHeader
                title="Create Permission"
                description="Add a new permission key used by roles and checks."
            />

            <Card>
                <CardContent className="pt-6">
                    <form className="space-y-6" onSubmit={submit}>
                        <div>
                            <Label htmlFor="name">Permission key</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="example.action"
                            />
                            {errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}
                        </div>

                        <div className="flex justify-end">
                            <Button disabled={processing} type="submit">
                                Save permission
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
