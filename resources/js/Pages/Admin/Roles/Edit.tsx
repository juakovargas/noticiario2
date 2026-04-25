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

interface Role {
    id: number;
    name: string;
    permissions: string[];
}

interface RolesEditProps {
    role: Role;
    permissions: PermissionOption[];
}

interface FormData {
    name: string;
    permissions: string[];
}

export default function RolesEdit({ role, permissions }: RolesEditProps): JSX.Element {
    const { data, setData, put, processing, errors } = useForm<FormData>({
        name: role.name,
        permissions: role.permissions,
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
        put(route('admin.roles.update', role.id));
    };

    return (
        <AdminLayout>
            <Head title="Edit Role" />

            <AdminPageHeader title="Edit Role" description="Update role and permission mapping." />

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
                                Update role
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
