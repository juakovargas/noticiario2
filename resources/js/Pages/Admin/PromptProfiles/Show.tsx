import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

export default function Show({ profile }: any): JSX.Element {
 const { t } = useTranslations();
 return <AdminLayout><Head title={profile.name} /><AdminPageHeader title={profile.name} description={t('Prompt Profile')} />
 <Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>{t('Description')}:</strong> {profile.description || '-'}</p><p><strong>{t('Happiness level')}:</strong> {profile.happiness_level}</p><p><strong>{t('Optimism level')}:</strong> {profile.optimism_level}</p><p><strong>{t('Seriousness level')}:</strong> {profile.seriousness_level}</p><p><strong>{t('Humor level')}:</strong> {profile.humor_level}</p><p><strong>{t('Irony level')}:</strong> {profile.irony_level}</p><p><strong>{t('Formality level')}:</strong> {profile.formality_level}</p><p><strong>{t('Negativity tolerance')}:</strong> {profile.negativity_tolerance}</p><p><strong>{t('Controversy tolerance')}:</strong> {profile.controversy_tolerance}</p><p><strong>{t('Source strictness level')}:</strong> {profile.source_strictness_level}</p></CardContent></Card>
 </AdminLayout>;
}
