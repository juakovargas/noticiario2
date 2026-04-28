import ThemeSwitcher from '@/Components/ThemeSwitcher';
import { useTranslations } from '@/i18n/useTranslations';
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

interface HomeSetting {
    title: string;
    subtitle: string | null;
    description: string | null;
    hero_badge: string | null;
    primary_button_label: string | null;
    primary_button_url: string | null;
    secondary_button_label: string | null;
    secondary_button_url: string | null;
    show_platforms_section: boolean;
    show_world_map_preview: boolean;
    platforms: string[] | null;
}

interface Noticiario {
    id: number;
    title: string;
    description: string | null;
    hashtags: string[];
    target_platforms: string[];
}

export default function Home({ homeSetting, latestNoticiarios }: { homeSetting: HomeSetting; latestNoticiarios: Noticiario[] }): JSX.Element {
    const { t } = useTranslations();
    const page = usePage<PageProps>();
    const permissions = page.props.auth.user?.permissions ?? [];

    return (
        <div className="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <Head title={homeSetting.title || t('Public home')} />
            <div className="w-full px-4 py-6 md:px-8 lg:px-10">
                <div className="mb-6 flex justify-end">
                    <ThemeSwitcher />
                </div>

                <section className="rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/75 md:p-8">
                    <p className="text-sm text-slate-500 dark:text-slate-300">{homeSetting.hero_badge}</p>
                    <h1 className="mt-2 text-3xl font-bold tracking-tight md:text-4xl">{homeSetting.title}</h1>
                    <p className="mt-2 text-slate-700 dark:text-slate-200">{homeSetting.subtitle}</p>
                    <p className="mt-3 text-slate-600 dark:text-slate-300">{homeSetting.description}</p>
                    <div className="mt-6 flex flex-wrap gap-3">
                        <Link href={homeSetting.primary_button_url ?? '/login'} className="rounded-md bg-indigo-600 px-4 py-2 text-white transition hover:bg-indigo-500">
                            {homeSetting.primary_button_label ?? t('Login')}
                        </Link>
                        {homeSetting.secondary_button_label && (
                            <Link
                                href={homeSetting.secondary_button_url ?? '/'}
                                className="rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                {homeSetting.secondary_button_label}
                            </Link>
                        )}
                        {permissions.includes('admin.access') && (
                            <Link href={route('admin.dashboard')} className="rounded-md border border-slate-300 px-4 py-2 text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                {t('Go to admin panel')}
                            </Link>
                        )}
                        {permissions.includes('editor.access') && (
                            <Link href={route('editor.dashboard')} className="rounded-md border border-slate-300 px-4 py-2 text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                {t('Go to editor panel')}
                            </Link>
                        )}
                        {permissions.includes('viewer.access') && (
                            <Link href={route('viewer.dashboard')} className="rounded-md border border-slate-300 px-4 py-2 text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                {t('Go to viewer panel')}
                            </Link>
                        )}
                    </div>
                </section>

                {homeSetting.show_platforms_section && (
                    <section className="mt-6 rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                        <h2 className="mb-3 text-xl font-semibold">{t('Supported platforms')}</h2>
                        <div className="flex flex-wrap gap-2">
                            {(homeSetting.platforms ?? []).map((platform) => (
                                <span key={platform} className="rounded-full bg-slate-200 px-3 py-1 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {platform}
                                </span>
                            ))}
                        </div>
                    </section>
                )}

                <section className="mt-6 rounded-2xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                    <h2 className="mb-3 text-xl font-semibold">{t('Latest noticiarios')}</h2>
                    {latestNoticiarios.length === 0 && <p className="text-slate-600 dark:text-slate-300">{t('No published noticiarios yet')}</p>}
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {latestNoticiarios.map((item) => (
                            <article key={item.id} className="rounded-xl border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/80">
                                <h3 className="font-semibold">{item.title}</h3>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{item.description}</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {item.hashtags.map((tag) => (
                                        <span key={tag} className="text-xs text-slate-500 dark:text-slate-400">
                                            #{tag}
                                        </span>
                                    ))}
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </div>
    );
}
