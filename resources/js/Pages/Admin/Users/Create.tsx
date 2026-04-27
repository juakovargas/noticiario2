import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

interface RolePermissionOption { id: number; name: string; }
interface LocaleOption { code: string; name: string; }
interface UsersCreateProps { roles: RolePermissionOption[]; permissions: RolePermissionOption[]; localeOptions: LocaleOption[]; dateFormatOptions: string[]; timeFormatOptions: string[]; }

interface FormData {
    name: string; email: string; password: string; password_confirmation: string; is_active: boolean; roles: string[]; permissions: string[];
    preferred_locale: string; timezone: string; date_format: string; time_format: string; avatar: File | null;
}

export default function UsersCreate({ roles, permissions, localeOptions, dateFormatOptions, timeFormatOptions }: UsersCreateProps): JSX.Element {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '', email: '', password: '', password_confirmation: '', is_active: true, roles: [], permissions: [], preferred_locale: '', timezone: '', date_format: 'locale_default', time_format: '24h', avatar: null,
    });
    const toggleArrayValue = (field: 'roles' | 'permissions', value: string): void => setData(field, data[field].includes(value) ? data[field].filter((item) => item !== value) : [...data[field], value]);
    const submit = (event: React.FormEvent): void => { event.preventDefault(); post(route('admin.users.store')); };

    return <AdminLayout><Head title="Create User" /><AdminPageHeader title="Create User" description="Create a user account and assign initial access." />
        <Card><CardContent className="pt-6"><form className="space-y-6" onSubmit={submit} encType="multipart/form-data">
            <div className="grid gap-4 md:grid-cols-2">
                <div><Label htmlFor="name">Name</Label><Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} />{errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name}</p>}</div>
                <div><Label htmlFor="email">Email</Label><Input id="email" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} />{errors.email && <p className="mt-1 text-xs text-rose-600">{errors.email}</p>}</div>
                <div><Label htmlFor="password">Password</Label><Input id="password" type="password" value={data.password} onChange={(event) => setData('password', event.target.value)} />{errors.password && <p className="mt-1 text-xs text-rose-600">{errors.password}</p>}</div>
                <div><Label htmlFor="password_confirmation">Confirm password</Label><Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} /></div>
                <div><Label htmlFor="preferred_locale">Preferred locale</Label><select id="preferred_locale" className="w-full rounded-md border" value={data.preferred_locale} onChange={(e)=>setData('preferred_locale', e.target.value)}><option value="">Locale default</option>{localeOptions.map((locale)=><option key={locale.code} value={locale.code}>{locale.name}</option>)}</select></div>
                <div><Label htmlFor="timezone">Timezone</Label><Input id="timezone" value={data.timezone} onChange={(event) => setData('timezone', event.target.value)} /></div>
                <div><Label htmlFor="date_format">Date format</Label><select id="date_format" className="w-full rounded-md border" value={data.date_format} onChange={(e)=>setData('date_format', e.target.value)}>{dateFormatOptions.map((item)=><option key={item} value={item}>{item}</option>)}</select></div>
                <div><Label htmlFor="time_format">Time format</Label><select id="time_format" className="w-full rounded-md border" value={data.time_format} onChange={(e)=>setData('time_format', e.target.value)}>{timeFormatOptions.map((item)=><option key={item} value={item}>{item}</option>)}</select></div>
                <div className="md:col-span-2"><Label htmlFor="avatar">Upload avatar</Label><Input id="avatar" type="file" accept="image/jpeg,image/png,image/webp" onChange={(event)=>setData('avatar', event.target.files?.[0] ?? null)} />{errors.avatar && <p className="mt-1 text-xs text-rose-600">{errors.avatar}</p>}</div>
            </div>
            <div><label className="inline-flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" checked={data.is_active} onChange={(event) => setData('is_active', event.target.checked)} className="rounded border-slate-300" />Active user</label></div>
            <div className="grid gap-4 md:grid-cols-2"><div><p className="mb-2 text-sm font-medium text-slate-700">Roles</p><div className="max-h-48 space-y-2 overflow-auto rounded-md border border-slate-200 p-3">{roles.map((role) => <label key={role.id} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.roles.includes(role.name)} onChange={() => toggleArrayValue('roles', role.name)} className="rounded border-slate-300" />{role.name}</label>)}</div>{errors.roles && <p className="mt-1 text-xs text-rose-600">{errors.roles}</p>}</div>
            <div><p className="mb-2 text-sm font-medium text-slate-700">Direct permissions</p><div className="max-h-48 space-y-2 overflow-auto rounded-md border border-slate-200 p-3">{permissions.map((permission) => <label key={permission.id} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.permissions.includes(permission.name)} onChange={() => toggleArrayValue('permissions', permission.name)} className="rounded border-slate-300" />{permission.name}</label>)}</div>{errors.permissions && <p className="mt-1 text-xs text-rose-600">{errors.permissions}</p>}</div></div>
            <div className="flex justify-end"><Button disabled={processing} type="submit">Save user</Button></div>
        </form></CardContent></Card></AdminLayout>;
}
