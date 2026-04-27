import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';
import { DateLike, formatDate as baseFormatDate, formatDateTime as baseFormatDateTime, formatTime as baseFormatTime } from '@/lib/dates';

export function useDateFormatter() {
    const page = usePage<PageProps>();
    const user = page.props.auth?.user;
    const locale = user?.preferred_locale || page.props.i18n?.locale;
    const preferences = {
        dateFormat: user?.date_format,
        timeFormat: user?.time_format,
    };

    const formatDate = (value: DateLike, fallback = '-'): string => baseFormatDate(value, locale, fallback, preferences);
    const formatDateTime = (value: DateLike, fallback = '-'): string => baseFormatDateTime(value, locale, fallback, preferences);
    const formatTime = (value: DateLike, fallback = '-'): string => baseFormatTime(value, locale, fallback, preferences);

    return {
        formatDate,
        formatDateTime,
        formatTime,
    };
}
