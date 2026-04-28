import { useTranslations } from '@/i18n/useTranslations';
import { useEffect, useMemo, useRef } from 'react';

export type MapMarker = {
    location_id: number;
    location_name: string;
    location_type: string;
    latitude: number;
    longitude: number;
    marker_color: string;
    marker_label: string;
    bulletin_count: number;
    active_bulletin_count: number;
    inactive_bulletin_count?: number;
    pending_prompt_runs_count: number;
    completed_prompt_runs_count: number;
    archived_prompt_runs_count: number;
    latest_prompt_run_status: string | null;
    latest_prompt_run_at: string | null;
    bulletins: Array<{ id: number; name: string; status: string; edition_type: string | null; category: string | null; language: string | null; url: string | null }>;
};

interface Props {
    markers: MapMarker[];
    panel: 'admin' | 'editor' | 'viewer';
    initialCenter?: [number, number];
    initialZoom?: number;
}

declare global {
    interface Window { L?: any }
}

const colorMap: Record<string, string> = { green: '#16a34a', amber: '#d97706', yellow: '#d97706', red: '#dc2626', blue: '#2563eb', gray: '#64748b' };

export default function WorldBulletinMap({ markers, initialCenter = [20, 0], initialZoom = 2 }: Props): JSX.Element {
    const { t } = useTranslations();
    const mapRef = useRef<HTMLDivElement | null>(null);

    const popupHtml = useMemo(() => (marker: MapMarker) => {
        const bulletins = marker.bulletins.map((b) => `<li>${b.name}</li>`).join('');
        return `<div><strong>${marker.location_name}</strong><br/>${t('Bulletin count')}: ${marker.bulletin_count}<br/>${t('Pending prompt runs')}: ${marker.pending_prompt_runs_count}<br/>${t('Completed prompt runs')}: ${marker.completed_prompt_runs_count}<br/>${t('Latest status')}: ${marker.latest_prompt_run_status ?? '-'}<ul>${bulletins}</ul></div>`;
    }, [t]);

    useEffect(() => {
        if (!mapRef.current || !markers.length) {
            return;
        }

        const init = () => {
            if (!window.L || !mapRef.current) {
                return;
            }

            const map = window.L.map(mapRef.current).setView(initialCenter, initialZoom);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            markers.forEach((marker) => {
                window.L.circleMarker([marker.latitude, marker.longitude], {
                    radius: 8,
                    color: colorMap[marker.marker_color] ?? colorMap.blue,
                    fillOpacity: 0.9,
                }).addTo(map).bindPopup(popupHtml(marker));
            });

            return () => map.remove();
        };

        const leafletStyles = document.querySelector('link[data-leaflet="1"]');
        if (!leafletStyles) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.dataset.leaflet = '1';
            document.head.appendChild(link);
        }

        if (!window.L) {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.async = true;
            script.onload = () => init();
            document.body.appendChild(script);
            return;
        }

        return init();
    }, [initialCenter, initialZoom, markers, popupHtml]);

    if (!markers.length) {
        return <div className="rounded-lg border border-dashed p-8 text-center text-sm text-slate-500">{t('No bulletin locations configured yet')}</div>;
    }

    return <div ref={mapRef} className="h-[460px] w-full rounded-lg" />;
}
