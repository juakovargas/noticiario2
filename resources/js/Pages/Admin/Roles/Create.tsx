import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

interface PermissionOption {
    id: number;
    name: string;
}

interface RolesCreateProps {
    permissions: PermissionOption[];
}

interface FormData {
    name: string;
    permissions: string[];
}

export default function RolesCreate({ permissions }: RolesCreateProps): JSX.Element {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        permissions: [],
    });

    const togglePermission = (permission: string): void => {
        const exists = data.permissions.includes(permission);

        setData(
            'permissions',
            exists
                ? data.permissions.filter((item) => item !== permission)
                : [...data.permissions, permission],
        );
    };

    const submit = (event: React.FormEvent): void => {
        event.preventDefault();
        post(route('admin.roles.store'));
    };

    return (
        <AdminLayout>
            <Head title="Create Role" />

            <AdminPageHeader
                title="Create Role"
                description="Define a new role and assign permissions."
            />

            <Card>
                <CardContent className="pt-6">
                    <form className="space-y-6" onSubmit={submit}>
                        <div>
                            <Label htmlFor="name">Role name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                            {errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}
                        </div>

                        <div>
                            <p className="mb-2 text-sm font-medium text-slate-700">Permissions</p>
                            <div className="grid max-h-64 gap-2 overflow-auto rounded-md border border-slate-200 p-3 md:grid-cols-2">
                                {permissions.map((permission) => (
                                    <label key={permission.id} className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.permissions.includes(permission.name)}
                                            onChange={() => togglePermission(permission.name)}
                                            className="rounded border-slate-300"
                                        />
                                        {permission.name}
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button disabled={processing} type="submit">
                                Save role
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
