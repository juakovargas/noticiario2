import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

interface Permission {
    id: number;
    name: string;
}

interface PermissionsEditProps {
    permission: Permission;
}

interface FormData {
    name: string;
}

export default function PermissionsEdit({ permission }: PermissionsEditProps): JSX.Element {
    const { data, setData, put, processing, errors } = useForm<FormData>({
        name: permission.name,
    });

    const submit = (event: React.FormEvent): void => {
        event.preventDefault();
        put(route('admin.permissions.update', permission.id));
    };

    return (
        <AdminLayout>
            <Head title="Edit Permission" />

            <AdminPageHeader
                title="Edit Permission"
                description="Update the permission key used across authorization checks."
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
                            />
                            {errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}
                        </div>

                        <div className="flex justify-end">
                            <Button disabled={processing} type="submit">
                                Update permission
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
