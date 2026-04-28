import { useTranslations } from '@/i18n/useTranslations';

export interface HomePageSettingFormData {
    locale: string;
    title: string;
    subtitle: string;
    description: string;
    hero_badge: string;
    primary_button_label: string;
    primary_button_url: string;
    secondary_button_label: string;
    secondary_button_url: string;
    show_latest_noticiarios: boolean;
    latest_noticiarios_limit: number;
    show_world_map_preview: boolean;
    show_platforms_section: boolean;
    platforms: string[];
    seo_title: string;
    seo_description: string;
    is_active: boolean;
}

const platformOptions = ['youtube', 'youtube_shorts', 'tiktok', 'instagram_reels', 'facebook', 'x_twitter', 'website', 'other'];

export default function Form({
    data,
    setData,
}: {
    data: HomePageSettingFormData;
    setData: <K extends keyof HomePageSettingFormData>(key: K, value: HomePageSettingFormData[K]) => void;
}): JSX.Element {
    const { t } = useTranslations();

    return (
        <div className="grid gap-4">
            <input className="rounded border p-2" placeholder={t('Locale')} value={data.locale} onChange={(e) => setData('locale', e.target.value)} />
            <input className="rounded border p-2" placeholder={t('Title')} value={data.title} onChange={(e) => setData('title', e.target.value)} />
            <input className="rounded border p-2" placeholder={t('Subtitle')} value={data.subtitle} onChange={(e) => setData('subtitle', e.target.value)} />
            <textarea className="rounded border p-2" placeholder={t('Description')} value={data.description} onChange={(e) => setData('description', e.target.value)} />
            <input className="rounded border p-2" placeholder={t('Hero badge')} value={data.hero_badge} onChange={(e) => setData('hero_badge', e.target.value)} />
            <input className="rounded border p-2" placeholder={t('Primary button label')} value={data.primary_button_label} onChange={(e) => setData('primary_button_label', e.target.value)} />
            <input className="rounded border p-2" placeholder={t('Primary button URL')} value={data.primary_button_url} onChange={(e) => setData('primary_button_url', e.target.value)} />
            <input className="rounded border p-2" placeholder={t('Secondary button label')} value={data.secondary_button_label} onChange={(e) => setData('secondary_button_label', e.target.value)} />
            <input className="rounded border p-2" placeholder={t('Secondary button URL')} value={data.secondary_button_url} onChange={(e) => setData('secondary_button_url', e.target.value)} />
            <label>{t('Latest noticiarios limit')}</label>
            <input type="number" min={1} max={24} className="rounded border p-2" value={data.latest_noticiarios_limit} onChange={(e) => setData('latest_noticiarios_limit', Number(e.target.value))} />
            <label className="flex gap-2"><input type="checkbox" checked={data.show_latest_noticiarios} onChange={(e) => setData('show_latest_noticiarios', e.target.checked)} />{t('Show latest noticiarios')}</label>
            <label className="flex gap-2"><input type="checkbox" checked={data.show_world_map_preview} onChange={(e) => setData('show_world_map_preview', e.target.checked)} />{t('Show world map preview')}</label>
            <label className="flex gap-2"><input type="checkbox" checked={data.show_platforms_section} onChange={(e) => setData('show_platforms_section', e.target.checked)} />{t('Show platforms section')}</label>
            <label className="flex gap-2"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />{t('Active')}</label>
            <p className="font-semibold">{t('Platforms')}</p>
            <div className="grid grid-cols-2 gap-2">
                {platformOptions.map((platform) => (
                    <label className="flex gap-2" key={platform}>
                        <input
                            type="checkbox"
                            checked={data.platforms.includes(platform)}
                            onChange={(event) =>
                                setData(
                                    'platforms',
                                    event.target.checked ? [...data.platforms, platform] : data.platforms.filter((item) => item !== platform),
                                )
                            }
                        />
                        {t(platform === 'x_twitter' ? 'X/Twitter' : platform.replace('_', ' '))}
                    </label>
                ))}
            </div>
        </div>
    );
}
