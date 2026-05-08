import PageHelp from '@/Components/Help/PageHelp';
import { useTranslations } from '@/i18n/useTranslations';
import ProfilePanelLayout from '@/Layouts/ProfilePanelLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

type Props = Record<string, unknown> & {
    mustVerifyEmail: boolean;
    status?: string;
    locales: Array<{ code: string; name: string }>;
    dateFormatOptions: string[];
    timeFormatOptions: string[];
    panel: 'admin' | 'editor' | 'viewer';
}

export default function Edit({ mustVerifyEmail, status, locales, dateFormatOptions, timeFormatOptions, panel }: PageProps<Props>) {
    const { t } = useTranslations();

    return (
        <ProfilePanelLayout panel={panel}>
            <Head title={t('Profile')} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">{t('Profile')}</h1>
                        <p className="text-sm text-slate-600 dark:text-slate-300">{t('Manage your account settings and security preferences.')}</p>
                    </div>
                    <PageHelp helpKey="profile.edit" />
                </div>

                <div className="rounded-lg bg-white p-4 shadow dark:bg-slate-900 sm:p-8">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        locales={locales}
                        dateFormatOptions={dateFormatOptions}
                        timeFormatOptions={timeFormatOptions}
                        panel={panel}
                        className="max-w-xl"
                    />
                </div>

                <div className="rounded-lg bg-white p-4 shadow dark:bg-slate-900 sm:p-8">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                <div className="rounded-lg bg-white p-4 shadow dark:bg-slate-900 sm:p-8">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </ProfilePanelLayout>
    );
}
