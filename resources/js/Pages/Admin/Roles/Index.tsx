import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

interface RoleItem {
    id: number;
    name: string;
    permissions: string[];
}

interface RolesIndexProps {
    roles: {
        data: RoleItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}

export default function RolesIndex({ roles }: RolesIndexProps): JSX.Element {
    const destroyRole = (id: number): void => {
        if (!window.confirm('Delete this role?')) {
            return;
        }

        router.delete(route('admin.roles.destroy', id));
    };

    return (
        <AdminLayout>
            <Head title="Roles" />

            <AdminPageHeader
                title="Roles"
                description="Create roles and map permissions for access levels."
                actionLabel="Create role"
                actionHref={route('admin.roles.create')}
            />

            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500">
                                <th className="px-2 pb-3">Name</th>
                                <th className="px-2 pb-3">Permissions</th>
                                <th className="px-2 pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {roles.data.map((role) => (
                                <tr key={role.id} className="border-b border-slate-100">
                                    <td className="px-2 py-3 font-medium text-slate-900">{role.name}</td>
                                    <td className="px-2 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            {role.permissions.map((permission) => (
                                                <Badge key={permission} variant="outline">
                                                    {permission}
                                                </Badge>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="px-2 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button asChild size="sm" variant="secondary">
                                                <Link href={route('admin.roles.edit', role.id)}>Edit</Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => destroyRole(role.id)}
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <Pagination links={roles.links} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
