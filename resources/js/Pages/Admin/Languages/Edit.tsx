import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Language { id:number; name:string; native_name:string|null; code:string; flag_emoji:string|null; is_active:boolean; is_default:boolean; sort_order:number; }

export default function Edit({ language }: { language: Language }): JSX.Element {
  const { t } = useTranslations();
  const form = useForm({ name:language.name, native_name:language.native_name || '', code:language.code, flag_emoji:language.flag_emoji || '', is_active:language.is_active, is_default:language.is_default, sort_order:language.sort_order });
  const submit = (e: FormEvent<HTMLFormElement>): void => { e.preventDefault(); form.put(route('admin.languages.update', language.id)); };
  return <AdminLayout><Head title={t('Edit')} />
    <Card><CardContent className="pt-6"><form className="space-y-4" onSubmit={submit}>
      <div><Label>{t('Name')}</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div>
      <div><Label>{t('Native Name')}</Label><Input value={form.data.native_name} onChange={(e)=>form.setData('native_name', e.target.value)} /></div>
      <div><Label>Code</Label><Input value={form.data.code} onChange={(e)=>form.setData('code', e.target.value)} /></div>
      <div><Label>{t('Flag')}</Label><Input value={form.data.flag_emoji} onChange={(e)=>form.setData('flag_emoji', e.target.value)} /></div>
      <div><Label>Sort</Label><Input type="number" value={form.data.sort_order} onChange={(e)=>form.setData('sort_order', Number(e.target.value))} /></div>
      <label className="flex gap-2"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />{t('Active')}</label>
      <label className="flex gap-2"><input type="checkbox" checked={form.data.is_default} onChange={(e)=>form.setData('is_default', e.target.checked)} />{t('Default')}</label>
      <div className="flex gap-2"><Button type="submit">{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('admin.languages.index')}>{t('Cancel')}</Link></Button></div>
    </form></CardContent></Card></AdminLayout>;
}
