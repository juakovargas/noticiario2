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
        <div className="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <Head title={homeSetting.title || t('Public home')} />
            <div className="mx-auto max-w-6xl p-6">
                <div className="flex justify-end"><ThemeSwitcher /></div>
                <p className="text-sm">{homeSetting.hero_badge}</p>
                <h1 className="text-4xl font-bold">{homeSetting.title}</h1>
                <p>{homeSetting.subtitle}</p>
                <p className="mt-3">{homeSetting.description}</p>
                <div className="mt-4 flex gap-3">
                    <Link href={homeSetting.primary_button_url ?? '/login'} className="rounded bg-indigo-600 px-4 py-2 text-white">{homeSetting.primary_button_label ?? t('Login')}</Link>
                    {homeSetting.secondary_button_label && <Link href={homeSetting.secondary_button_url ?? '/'} className="rounded border px-4 py-2">{homeSetting.secondary_button_label}</Link>}
                    {permissions.includes('admin.access') && <Link href={route('admin.dashboard')} className="rounded border px-4 py-2">{t('Go to admin panel')}</Link>}
                    {permissions.includes('editor.access') && <Link href={route('editor.dashboard')} className="rounded border px-4 py-2">{t('Go to editor panel')}</Link>}
                    {permissions.includes('viewer.access') && <Link href={route('viewer.dashboard')} className="rounded border px-4 py-2">{t('Go to viewer panel')}</Link>}
                </div>

                {homeSetting.show_platforms_section && (
                    <section className="mt-8">
                        <h2 className="mb-2 text-xl font-semibold">{t('Supported platforms')}</h2>
                        <div className="flex flex-wrap gap-2">{(homeSetting.platforms ?? []).map((platform) => <span key={platform} className="rounded bg-slate-200 px-3 py-1 text-sm dark:bg-slate-800">{platform}</span>)}</div>
                    </section>
                )}

                <section className="mt-8">
                    <h2 className="mb-3 text-xl font-semibold">{t('Latest noticiarios')}</h2>
                    {latestNoticiarios.length === 0 && <p>{t('No published noticiarios yet')}</p>}
                    <div className="grid gap-4 md:grid-cols-2">
                        {latestNoticiarios.map((item) => (
                            <article key={item.id} className="rounded border border-slate-200 p-4 dark:border-slate-700">
                                <h3 className="font-semibold">{item.title}</h3>
                                <p className="text-sm">{item.description}</p>
                                <div className="mt-2 flex flex-wrap gap-2">{item.hashtags.map((tag) => <span key={tag} className="text-xs">#{tag}</span>)}</div>
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </div>
    );
}
