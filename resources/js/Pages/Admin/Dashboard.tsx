import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

interface DashboardProps {
    stats: {
        users: number;
        activeUsers: number;
        inactiveUsers: number;
    };
}

export default function Dashboard({ stats }: DashboardProps): JSX.Element {
    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />

            <AdminPageHeader
                title="Administration Dashboard"
                description="Overview of users and system access health."
            />

            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardDescription>Total users</CardDescription>
                        <CardTitle>{stats.users}</CardTitle>
                    </CardHeader>
                    <CardContent className="text-xs text-slate-500">
                        Registered accounts in the system.
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Active users</CardDescription>
                        <CardTitle>{stats.activeUsers}</CardTitle>
                    </CardHeader>
                    <CardContent className="text-xs text-slate-500">
                        Users that can log in.
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Inactive users</CardDescription>
                        <CardTitle>{stats.inactiveUsers}</CardTitle>
                    </CardHeader>
                    <CardContent className="text-xs text-slate-500">
                        Accounts currently disabled.
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
