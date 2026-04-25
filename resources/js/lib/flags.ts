export function countryCodeToFlagEmoji(countryCode?: string | null): string {
    if (!countryCode || countryCode.length !== 2) {
        return '🌐';
    }

    const upper = countryCode.toUpperCase();
    if (!/^[A-Z]{2}$/.test(upper)) {
        return '🌐';
    }

    const OFFSET = 127397;
    return String.fromCodePoint(...[...upper].map((char) => char.charCodeAt(0) + OFFSET));
}
