import AdminPageHeader from '@/Components/AdminPageHeader';
import UserAvatar from '@/Components/UserAvatar';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

interface RolePermissionOption { id: number; name: string; }
interface RoleWithPermissions { id: number; name: string; permissions: string[]; }
interface LocaleOption { code: string; name: string; }
interface User { id:number; name:string; email:string; is_active:boolean; roles:string[]; permissions:string[]; preferred_locale:string|null; timezone:string|null; date_format:string|null; time_format:string|null; avatar_url:string|null; initials:string; }
interface UsersEditProps { user: User; roles: RolePermissionOption[]; rolesWithPermissions: RoleWithPermissions[]; permissions: RolePermissionOption[]; localeOptions: LocaleOption[]; dateFormatOptions: string[]; timeFormatOptions: string[]; }

interface FormData { _method:'put'; name:string; email:string; password:string; password_confirmation:string; is_active:boolean; roles:string[]; permissions:string[]; preferred_locale:string; timezone:string; date_format:string; time_format:string; avatar:File|null; remove_avatar:boolean; }

export default function UsersEdit({ user, roles, rolesWithPermissions, permissions, localeOptions, dateFormatOptions, timeFormatOptions }: UsersEditProps): JSX.Element {
    const { t } = useTranslations();
    const { data, setData, post, processing, errors } = useForm<FormData>({
        _method: 'put', name: user.name, email: user.email, password: '', password_confirmation: '', is_active: user.is_active,
        roles: user.roles, permissions: user.permissions, preferred_locale: user.preferred_locale ?? '', timezone: user.timezone ?? '',
        date_format: user.date_format ?? 'locale_default', time_format: user.time_format ?? '24h', avatar: null, remove_avatar: false,
    });

    const toggleArrayValue = (field: 'roles' | 'permissions', value: string): void => {
        setData(field, data[field].includes(value) ? data[field].filter((item) => item !== value) : [...data[field], value]);
    };

    const effectivePermissions = Array.from(new Set(rolesWithPermissions.filter((r) => data.roles.includes(r.name)).flatMap((r) => r.permissions))).sort();

    const submit = (event: React.FormEvent): void => {
        event.preventDefault();
        post(route('admin.users.update', user.id), { forceFormData: true, preserveScroll: true });
    };

    return <AdminLayout>
        <Head title={t('Edit user')} />
        <AdminPageHeader title={t('Edit user')} description={t('User details')} />
        <Card><CardContent className="pt-6">
            <form className="space-y-6" onSubmit={submit} encType="multipart/form-data">
                <div className="grid gap-4 md:grid-cols-2">
                    <div><Label htmlFor="name">{t('Name')}</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />{errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}</div>
                    <div><Label htmlFor="email">{t('Email')}</Label><Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />{errors.email && <p className="mt-1 text-xs text-rose-600">{errors.email}</p>}</div>
                    <div><Label htmlFor="password">{t('Password')}</Label><Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />{errors.password && <p className="mt-1 text-xs text-rose-600">{errors.password}</p>}</div>
                    <div><Label htmlFor="password_confirmation">{t('Confirm password')}</Label><Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} /></div>
                    <div><Label htmlFor="preferred_locale">{t('Preferred locale')}</Label><select id="preferred_locale" className="w-full rounded-md border" value={data.preferred_locale} onChange={(e) => setData('preferred_locale', e.target.value)}><option value="">{t('Locale default')}</option>{localeOptions.map((l)=><option key={l.code} value={l.code}>{l.name}</option>)}</select></div>
                    <div><Label htmlFor="timezone">{t('Timezone')}</Label><Input id="timezone" value={data.timezone} onChange={(e) => setData('timezone', e.target.value)} /></div>
                    <div><Label htmlFor="date_format">{t('Date format')}</Label><select id="date_format" className="w-full rounded-md border" value={data.date_format} onChange={(e)=>setData('date_format', e.target.value)}>{dateFormatOptions.map((i)=><option key={i} value={i}>{i}</option>)}</select></div>
                    <div><Label htmlFor="time_format">{t('Time format')}</Label><select id="time_format" className="w-full rounded-md border" value={data.time_format} onChange={(e)=>setData('time_format', e.target.value)}>{timeFormatOptions.map((i)=><option key={i} value={i}>{i}</option>)}</select></div>
                    <div className="md:col-span-2"><Label>{t('Current avatar')}</Label><div className="mt-2 flex items-center gap-3"><UserAvatar user={user} size="md" /><span className="text-sm text-slate-500">{user.avatar_url ?? t('Avatar')}</span></div></div>
                    <div className="md:col-span-2"><Label htmlFor="avatar">{t('Upload avatar')}</Label><Input id="avatar" type="file" accept="image/jpeg,image/png,image/webp" onChange={(e)=>setData('avatar', e.target.files?.[0] ?? null)} />{errors.avatar && <p className="mt-1 text-xs text-rose-600">{errors.avatar}</p>}<label className="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_avatar} onChange={(e)=>setData('remove_avatar', e.target.checked)} />{t('Remove avatar')}</label></div>
                </div>

                <div><label className="inline-flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" checked={data.is_active} onChange={(e)=>setData('is_active', e.target.checked)} className="rounded border-slate-300" />{data.is_active ? t('Active user') : t('Inactive user')}</label></div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <p className="mb-2 text-sm font-medium text-slate-700">{t('User roles')}</p>
                        <ul className="mb-3 list-disc space-y-1 pl-4 text-xs text-slate-600"><li>{t('Superadmin')}: {t('Full system access')}</li><li>{t('Admin')}: {t('Technical administration')}</li><li>{t('Editor')}: {t('Editorial workspace access')}</li><li>{t('Viewer')}: {t('Read-only panel access')}</li></ul>
                        <div className="max-h-48 space-y-2 overflow-auto rounded-md border border-slate-200 p-3">{roles.map((role)=><label key={role.id} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.roles.includes(role.name)} onChange={() => toggleArrayValue('roles', role.name)} className="rounded border-slate-300" />{role.name}</label>)}</div>
                        {errors.roles && <p className="mt-1 text-xs text-rose-600">{errors.roles}</p>}
                    </div>
                    <div><p className="mb-2 text-sm font-medium text-slate-700">{t('Direct permissions')}</p><div className="max-h-48 space-y-2 overflow-auto rounded-md border border-slate-200 p-3">{permissions.map((permission)=><label key={permission.id} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.permissions.includes(permission.name)} onChange={() => toggleArrayValue('permissions', permission.name)} className="rounded border-slate-300" />{permission.name}</label>)}</div></div>
                </div>

                <div className="rounded-md border border-slate-200 p-3">
                    <p className="text-sm font-semibold">{t('Effective permissions')}</p>
                    <p className="mt-1 text-xs text-slate-500">{data.roles.length ? `${t('Selected roles')}: ${data.roles.join(', ')}` : t('No permissions selected')}</p>
                    <div className="mt-2 text-sm">{effectivePermissions.length ? effectivePermissions.join(', ') : t('No permissions selected')}</div>
                    <p className="mt-3 text-sm font-medium">{t('Panel access')}</p>
                    <ul className="text-sm text-slate-700">
                        <li>{effectivePermissions.includes('admin.access') ? t('Can access Admin panel') : t('Cannot access Admin panel')}</li>
                        <li>{effectivePermissions.includes('editor.access') ? t('Can access Editor panel') : t('Cannot access Editor panel')}</li>
                        <li>{effectivePermissions.includes('viewer.access') ? t('Can access Viewer panel') : t('Cannot access Viewer panel')}</li>
                    </ul>
                </div>

                <div className="flex items-center justify-end gap-2"><Button asChild variant="outline"><Link href={route('admin.users.index')}>{t('Cancel')}</Link></Button><Button type="submit" disabled={processing}>{t('Save user')}</Button></div>
            </form>
        </CardContent></Card>
    </AdminLayout>;
}
