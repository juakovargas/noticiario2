import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';
import { DateLike, formatDate as baseFormatDate, formatDateTime as baseFormatDateTime, formatTime as baseFormatTime } from '@/lib/dates';

export function useDateFormatter() {
    const page = usePage<PageProps>();
    const locale = page.props.i18n?.locale;

    const formatDate = (value: DateLike, fallback = '-'): string => baseFormatDate(value, locale, fallback);
    const formatDateTime = (value: DateLike, fallback = '-'): string => baseFormatDateTime(value, locale, fallback);
    const formatTime = (value: DateLike, fallback = '-'): string => baseFormatTime(value, locale, fallback);

    return {
        formatDate,
        formatDateTime,
        formatTime,
    };
}
