export type DateLike = string | Date | null | undefined;

const DEFAULT_FALLBACK = '-';

const LOCALE_MAP: Record<string, string> = {
    en: 'en-GB',
    es: 'es-ES',
    fr: 'fr-FR',
};

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

export function formatDate(value: DateLike, locale?: string, fallback = DEFAULT_FALLBACK): string {
    return safeFormat(value, locale, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }, fallback);
}

export function formatDateTime(value: DateLike, locale?: string, fallback = DEFAULT_FALLBACK): string {
    return safeFormat(value, locale, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }, fallback);
}

export function formatTime(value: DateLike, locale?: string, fallback = DEFAULT_FALLBACK): string {
    return safeFormat(value, locale, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }, fallback);
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
