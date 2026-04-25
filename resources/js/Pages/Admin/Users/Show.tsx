import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    roles: string[];
    permissions: string[];
}

interface UsersShowProps {
    user: User;
}

export default function UsersShow({ user }: UsersShowProps): JSX.Element {
    return (
        <AdminLayout>
            <Head title="User Details" />

            <AdminPageHeader
                title={user.name}
                description="User profile and authorization details."
                actionLabel="Edit user"
                actionHref={route('admin.users.edit', user.id)}
            />

            <Card>
                <CardContent className="space-y-6 pt-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-500">Name</p>
                            <p className="font-medium text-slate-900">{user.name}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-500">Email</p>
                            <p className="font-medium text-slate-900">{user.email}</p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-500">Status</p>
                            <Badge variant={user.is_active ? 'success' : 'danger'}>
                                {user.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-500">Created at</p>
                            <p className="font-medium text-slate-900">{user.created_at}</p>
                        </div>
                    </div>

                    <div>
                        <p className="mb-2 text-xs uppercase tracking-wide text-slate-500">Roles</p>
                        <div className="flex flex-wrap gap-2">
                            {user.roles.length ? (
                                user.roles.map((role) => <Badge key={role}>{role}</Badge>)
                            ) : (
                                <p className="text-sm text-slate-500">No roles assigned.</p>
                            )}
                        </div>
                    </div>

                    <div>
                        <p className="mb-2 text-xs uppercase tracking-wide text-slate-500">Direct permissions</p>
                        <div className="flex flex-wrap gap-2">
                            {user.permissions.length ? (
                                user.permissions.map((permission) => (
                                    <Badge key={permission} variant="outline">
                                        {permission}
                                    </Badge>
                                ))
                            ) : (
                                <p className="text-sm text-slate-500">No direct permissions assigned.</p>
                            )}
                        </div>
                    </div>

                    <Button asChild variant="outline">
                        <Link href={route('admin.users.index')}>Back to users</Link>
                    </Button>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
