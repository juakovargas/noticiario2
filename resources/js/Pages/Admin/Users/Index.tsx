import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import UserAvatar from '@/Components/UserAvatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';

interface UserItem {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    created_at: string;
    roles: string[];
    preferred_locale: string | null;
    timezone: string | null;
    avatar_url: string | null;
    initials: string;
    manager?: { id:number; name:string; email:string } | null;
}

interface UsersIndexProps {
    users: {
        data: UserItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}

export default function UsersIndex({ users }: UsersIndexProps): JSX.Element {
    const page = usePage<PageProps>();
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const currentUser = page.props.auth.user;
    const canImpersonate = currentUser?.permissions?.includes('admin.access') ?? false;

    const destroyUser = (id: number): void => {
        if (!window.confirm(t('Delete this user?'))) {
            return;
        }

        router.delete(route('admin.users.destroy', id));
    };

    const impersonateUser = (id: number): void => {
        router.post(route('admin.users.impersonate', id));
    };

    return (
        <AdminLayout>
            <Head title={t('Users')} />

            <AdminPageHeader
                title={t('Users')}
                description={t('System administration')}
                actionLabel={t('Create')}
                actionHref={route('admin.users.create')}
            />

            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500">
                                <th className="px-2 pb-3">{t('Name')}</th>
                                <th className="px-2 pb-3">{t('Email')}</th>
                                <th className="px-2 pb-3">{t('Preferred locale')}</th>
                                <th className="px-2 pb-3">{t('Timezone')}</th>
                                <th className="px-2 pb-3">{t('Status')}</th>
                                <th className="px-2 pb-3">{t('Roles')}</th>
                                <th className="px-2 pb-3">{t('Manager')}</th>
                                <th className="px-2 pb-3">{t('Created at')}</th>
                                <th className="px-2 pb-3 text-right">{t('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((user) => (
                                <tr key={user.id} className="border-b border-slate-100">
                                    <td className="px-2 py-3 font-medium text-slate-900"><div className="flex items-center gap-2"><UserAvatar user={user} size="sm" /><span>{user.name}</span></div></td>
                                    <td className="px-2 py-3 text-slate-600">{user.email}</td>
                                    <td className="px-2 py-3 text-slate-600">{user.preferred_locale ?? '-'}</td>
                                    <td className="px-2 py-3 text-slate-600">{user.timezone ?? '-'}</td>
                                    <td className="px-2 py-3">
                                        <Badge variant={user.is_active ? 'success' : 'danger'}>
                                            {user.is_active ? t('Active') : t('Inactive')}
                                        </Badge>
                                    </td>
                                    <td className="px-2 py-3 text-slate-600">
                                        {user.roles.length ? (
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.map((role) => (
                                                    <Badge key={`${user.id}-${role}`} variant="outline">
                                                        {role}
                                                    </Badge>
                                                ))}
                                            </div>
                                        ) : t('No roles')}
                                    </td>
                                    <td className="px-2 py-3 text-slate-600">{user.manager ? `${user.manager.name} (${user.manager.email})` : t('No manager')}</td>
                                    <td className="px-2 py-3 text-slate-600">{formatDateTime(user.created_at)}</td>
                                    <td className="px-2 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button asChild size="sm" variant="outline">
                                                <Link href={route('admin.users.show', user.id)}>{t('View')}</Link>
                                            </Button>
                                            <Button asChild size="sm" variant="secondary">
                                                <Link href={route('admin.users.edit', user.id)}>{t('Edit')}</Link>
                                            </Button>
                                            {canImpersonate && currentUser?.id !== user.id && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => impersonateUser(user.id)}
                                                >
                                                    {t('Impersonate')}
                                                </Button>
                                            )}
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => destroyUser(user.id)}
                                            >{t('Delete')}</Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <Pagination links={users.links} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
