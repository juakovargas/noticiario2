import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

interface PermissionItem {
    id: number;
    name: string;
}

interface PermissionsIndexProps {
    permissions: {
        data: PermissionItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}

export default function PermissionsIndex({ permissions }: PermissionsIndexProps): JSX.Element {
    const destroyPermission = (id: number): void => {
        if (!window.confirm('Delete this permission?')) {
            return;
        }

        router.delete(route('admin.permissions.destroy', id));
    };

    return (
        <AdminLayout>
            <Head title="Permissions" />

            <AdminPageHeader
                title="Permissions"
                description="Manage atomic permission keys used by roles and policies."
                actionLabel="Create permission"
                actionHref={route('admin.permissions.create')}
            />

            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500">
                                <th className="px-2 pb-3">Name</th>
                                <th className="px-2 pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {permissions.data.map((permission) => (
                                <tr key={permission.id} className="border-b border-slate-100">
                                    <td className="px-2 py-3 font-medium text-slate-900">{permission.name}</td>
                                    <td className="px-2 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button asChild size="sm" variant="secondary">
                                                <Link href={route('admin.permissions.edit', permission.id)}>
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => destroyPermission(permission.id)}
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <Pagination links={permissions.links} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
