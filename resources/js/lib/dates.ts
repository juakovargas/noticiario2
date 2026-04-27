export type DateLike = string | Date | null | undefined;

const DEFAULT_FALLBACK = '-';

const LOCALE_MAP: Record<string, string> = {
    en: 'en-GB',
    es: 'es-ES',
    fr: 'fr-FR',
};

interface FormatPreferences {
    dateFormat?: string | null;
    timeFormat?: string | null;
}

function toAppLocale(locale?: string): string {
    if (!locale) {
        return 'en-GB';
    }

    const normalized = locale.toLowerCase();

    if (LOCALE_MAP[normalized]) {
        return LOCALE_MAP[normalized];
    }

    if (normalized.startsWith('es')) {
        return 'es-ES';
    }

    if (normalized.startsWith('fr')) {
        return 'fr-FR';
    }

    if (normalized.startsWith('en')) {
        return 'en-GB';
    }

    return 'en-GB';
}

function toDate(value: DateLike): Date | null {
    if (!value) {
        return null;
    }

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    const normalized = value.trim().replace(' ', 'T');
    const parsed = new Date(normalized);

    if (!Number.isNaN(parsed.getTime())) {
        return parsed;
    }

    const reparsed = new Date(value);
    return Number.isNaN(reparsed.getTime()) ? null : reparsed;
}

function safeFormat(
    value: DateLike,
    locale: string | undefined,
    options: Intl.DateTimeFormatOptions,
    fallback = DEFAULT_FALLBACK,
): string {
    const date = toDate(value);

    if (!date) {
        return fallback;
    }

    try {
        return new Intl.DateTimeFormat(toAppLocale(locale), options).format(date);
    } catch {
        return fallback;
    }
}

function dateOptions(dateFormat?: string | null): Intl.DateTimeFormatOptions {
    if (dateFormat === 'yyyy-mm-dd') {
        return { year: 'numeric', month: '2-digit', day: '2-digit' };
    }

    if (dateFormat === 'mm/dd/yyyy') {
        return { month: '2-digit', day: '2-digit', year: 'numeric' };
    }

    return { day: '2-digit', month: '2-digit', year: 'numeric' };
}

function timeOptions(timeFormat?: string | null): Intl.DateTimeFormatOptions {
    return {
        hour: '2-digit',
        minute: '2-digit',
        hour12: timeFormat === '12h',
    };
}

export function formatDate(value: DateLike, locale?: string, fallback = DEFAULT_FALLBACK, preferences?: FormatPreferences): string {
    return safeFormat(value, locale, dateOptions(preferences?.dateFormat), fallback);
}

export function formatDateTime(value: DateLike, locale?: string, fallback = DEFAULT_FALLBACK, preferences?: FormatPreferences): string {
    return safeFormat(value, locale, {
        ...dateOptions(preferences?.dateFormat),
        ...timeOptions(preferences?.timeFormat),
    }, fallback);
}

export function formatTime(value: DateLike, locale?: string, fallback = DEFAULT_FALLBACK, preferences?: FormatPreferences): string {
    return safeFormat(value, locale, timeOptions(preferences?.timeFormat), fallback);
}

export function toDateTimeLocalInputValue(value: DateLike): string {
    const date = toDate(value);

    if (!date) {
        return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hour}:${minute}`;
}
