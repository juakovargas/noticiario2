import { useTranslations } from '@/i18n/useTranslations';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface WelcomeProps extends PageProps {
    canLogin: boolean;
    canRegister: boolean;
}

export default function Welcome({ auth, canLogin, canRegister }: WelcomeProps): JSX.Element {
    const permissions = auth.user?.permissions ?? [];
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Noticiario')} />

            <div className="min-h-screen bg-slate-950 text-slate-100">
                <div className="mx-auto flex max-w-6xl flex-col px-6 py-10 md:px-10">
                    <header className="flex flex-col gap-4 border-b border-slate-800 pb-8 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-widest text-cyan-300">{t('Noticiario')}</p>
                            <h1 className="mt-2 text-3xl font-bold md:text-4xl">{t('Create short digital news bulletins faster')}</h1>
                            <p className="mt-3 max-w-2xl text-slate-300">{t('Editorial automation platform')}</p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {!auth.user && canLogin && <Link href={route('login')} className="rounded-lg bg-cyan-500 px-4 py-2 font-semibold text-slate-950">{t('Login')}</Link>}
                            {!auth.user && canRegister && <Link href={route('register')} className="rounded-lg border border-slate-600 px-4 py-2 font-semibold hover:bg-slate-900">{t('Register')}</Link>}
                            {auth.user && permissions.includes('admin.access') && <Link href={route('admin.dashboard')} className="rounded-lg bg-cyan-500 px-4 py-2 font-semibold text-slate-950">{t('Admin Panel')}</Link>}
                            {auth.user && permissions.includes('editor.access') && <Link href={route('editor.dashboard')} className="rounded-lg border border-cyan-500 px-4 py-2 font-semibold text-cyan-300">{t('Editor Panel')}</Link>}
                            {auth.user && permissions.includes('viewer.access') && <Link href={route('viewer.dashboard')} className="rounded-lg border border-emerald-500 px-4 py-2 font-semibold text-emerald-300">{t('Viewer Panel')}</Link>}
                        </div>
                    </header>

                    <section className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {['News collection','Editorial editions','Script workflow','Future audio/video production','Multi-location and thematic coverage','Short-form publishing'].map((feature) => (
                            <article key={feature} className="rounded-xl border border-slate-800 bg-slate-900/70 p-5">
                                <h2 className="text-lg font-semibold text-white">{t(feature)}</h2>
                                <p className="mt-2 text-sm text-slate-300">{t('Editorial workspace')}</p>
                            </article>
                        ))}
                    </section>

                    <footer className="mt-10 border-t border-slate-800 pt-6 text-sm text-slate-400">
                        <p className="font-semibold text-slate-200">{t('Noticiario')}</p>
                        <p>{t('Editorial automation platform')}</p>
                    </footer>
                </div>
            </div>
        </>
    );
}
