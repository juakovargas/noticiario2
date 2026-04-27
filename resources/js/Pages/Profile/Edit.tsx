import ProfilePanelLayout from '@/Layouts/ProfilePanelLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    locales: Array<{ code: string; name: string }>;
    dateFormatOptions: string[];
    timeFormatOptions: string[];
    panel: 'admin' | 'editor' | 'viewer';
}

export default function Edit({ mustVerifyEmail, status, locales, dateFormatOptions, timeFormatOptions, panel }: PageProps<Props>) {
    return (
        <ProfilePanelLayout panel={panel}>
            <Head title="Profile" />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="rounded-lg bg-white p-4 shadow sm:p-8">
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

                <div className="rounded-lg bg-white p-4 shadow sm:p-8">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                <div className="rounded-lg bg-white p-4 shadow sm:p-8">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </ProfilePanelLayout>
    );
}
