export interface LanguageOption {
    id?: number;
    code: string;
    name: string;
    native_name?: string | null;
    flag_emoji?: string | null;
}

export function formatLanguage(languageCode: string | null | undefined, availableLanguages: LanguageOption[]): string {
    if (!languageCode) {
        return '-';
    }

    const language = availableLanguages.find((item) => item.code === languageCode);

    if (!language) {
        return languageCode;
    }

    const label = language.native_name || language.name;
    return `${language.flag_emoji ?? ''} ${label} (${language.code})`.trim();
}
