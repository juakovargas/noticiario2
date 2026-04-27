import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useTranslations } from '@/i18n/useTranslations';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface LocaleOption {
    code: string;
    name: string;
}

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    locales,
    dateFormatOptions,
    timeFormatOptions,
    className = '',
}: {
    mustVerifyEmail: boolean;
    status?: string;
    locales: LocaleOption[];
    dateFormatOptions: string[];
    timeFormatOptions: string[];
    className?: string;
}) {
    const user = usePage().props.auth.user!;
    const { t } = useTranslations();

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            preferred_locale: user.preferred_locale ?? '',
            timezone: user.timezone ?? '',
            date_format: user.date_format ?? 'locale_default',
            time_format: user.time_format ?? '24h',
            avatar: null as File | null,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">{t('Profile Information')}</h2>

                <p className="mt-1 text-sm text-gray-600">
                    {t("Update your account's profile information and email address.")}
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6" encType="multipart/form-data">
                <div>
                    <InputLabel htmlFor="name" value={t('Name')} />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value={t('Email')} />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="preferred_locale" value={t('Preferred locale')} />
                        <select id="preferred_locale" className="mt-1 block w-full rounded-md border-gray-300" value={data.preferred_locale} onChange={(e) => setData('preferred_locale', e.target.value)}>
                            <option value="">{t('Locale default')}</option>
                            {locales.map((locale) => <option key={locale.code} value={locale.code}>{locale.name}</option>)}
                        </select>
                        <InputError className="mt-2" message={errors.preferred_locale} />
                    </div>

                    <div>
                        <InputLabel htmlFor="timezone" value={t('Timezone')} />
                        <TextInput id="timezone" className="mt-1 block w-full" value={data.timezone} onChange={(e) => setData('timezone', e.target.value)} placeholder={t('Select timezone')} />
                        <InputError className="mt-2" message={errors.timezone} />
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="date_format" value={t('Date format')} />
                        <select id="date_format" className="mt-1 block w-full rounded-md border-gray-300" value={data.date_format} onChange={(e) => setData('date_format', e.target.value)}>
                            {dateFormatOptions.map((option) => <option key={option} value={option}>{t(option === 'locale_default' ? 'Locale default' : option)}</option>)}
                        </select>
                    </div>
                    <div>
                        <InputLabel htmlFor="time_format" value={t('Time format')} />
                        <select id="time_format" className="mt-1 block w-full rounded-md border-gray-300" value={data.time_format} onChange={(e) => setData('time_format', e.target.value)}>
                            {timeFormatOptions.map((option) => <option key={option} value={option}>{t(option === '24h' ? '24-hour' : '12-hour')}</option>)}
                        </select>
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="avatar" value={t('Upload avatar')} />
                    <input id="avatar" type="file" accept="image/jpeg,image/png,image/webp" className="mt-1 block w-full text-sm" onChange={(e) => setData('avatar', e.target.files?.[0] ?? null)} />
                    <InputError className="mt-2" message={errors.avatar} />
                    <p className="mt-1 text-xs text-gray-500">php artisan storage:link</p>
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>{t('Save')}</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">{t('Profile updated')}</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
