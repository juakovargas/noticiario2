import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

interface RolePermissionOption {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
    permissions: string[];
}

interface UsersEditProps {
    user: User;
    roles: RolePermissionOption[];
    permissions: RolePermissionOption[];
}

interface FormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    is_active: boolean;
    roles: string[];
    permissions: string[];
}

export default function UsersEdit({ user, roles, permissions }: UsersEditProps): JSX.Element {
    const { data, setData, put, processing, errors } = useForm<FormData>({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        is_active: user.is_active,
        roles: user.roles,
        permissions: user.permissions,
    });

    const toggleArrayValue = (field: 'roles' | 'permissions', value: string): void => {
        const selected = data[field];
        const exists = selected.includes(value);

        setData(
            field,
            exists ? selected.filter((item) => item !== value) : [...selected, value],
        );
    };

    const submit = (event: React.FormEvent): void => {
        event.preventDefault();
        put(route('admin.users.update', user.id));
    };

    return (
        <AdminLayout>
            <Head title="Edit User" />

            <AdminPageHeader title="Edit User" description="Update profile, access, and status." />

            <Card>
                <CardContent className="pt-6">
                    <form className="space-y-6" onSubmit={submit}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                />
                                {errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}
                            </div>

                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                />
                                {errors.email && <p className="mt-1 text-xs text-rose-600">{errors.email}</p>}
                            </div>

                            <div>
                                <Label htmlFor="password">New password (optional)</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                />
                                {errors.password && <p className="mt-1 text-xs text-rose-600">{errors.password}</p>}
                            </div>

                            <div>
                                <Label htmlFor="password_confirmation">Confirm password</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData('password_confirmation', event.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div>
                            <label className="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(event) => setData('is_active', event.target.checked)}
                                    className="rounded border-slate-300"
                                />
                                Active user
                            </label>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="mb-2 text-sm font-medium text-slate-700">Roles</p>
                                <div className="max-h-48 space-y-2 overflow-auto rounded-md border border-slate-200 p-3">
                                    {roles.map((role) => (
                                        <label key={role.id} className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={data.roles.includes(role.name)}
                                                onChange={() => toggleArrayValue('roles', role.name)}
                                                className="rounded border-slate-300"
                                            />
                                            {role.name}
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <p className="mb-2 text-sm font-medium text-slate-700">Direct permissions</p>
                                <div className="max-h-48 space-y-2 overflow-auto rounded-md border border-slate-200 p-3">
                                    {permissions.map((permission) => (
                                        <label key={permission.id} className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={data.permissions.includes(permission.name)}
                                                onChange={() =>
                                                    toggleArrayValue('permissions', permission.name)
                                                }
                                                className="rounded border-slate-300"
                                            />
                                            {permission.name}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button disabled={processing} type="submit">
                                Update user
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
