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
    const containerRef = useRef<HTMLDivElement | null>(null);
    const mapInstanceRef = useRef<any | null>(null);
    const markerLayerRef = useRef<any | null>(null);
    const initIdRef = useRef(0);
    const center = useMemo<[number, number]>(() => [initialCenter[0], initialCenter[1]], [initialCenter[0], initialCenter[1]]);

    const popupHtml = useMemo(() => (marker: MapMarker) => {
        const bulletins = marker.bulletins.map((b) => `<li>${b.name}</li>`).join('');
        return `<div><strong>${marker.location_name}</strong><br/>${t('Bulletin count')}: ${marker.bulletin_count}<br/>${t('Pending prompt runs')}: ${marker.pending_prompt_runs_count}<br/>${t('Completed prompt runs')}: ${marker.completed_prompt_runs_count}<br/>${t('Latest status')}: ${marker.latest_prompt_run_status ?? '-'}<ul>${bulletins}</ul></div>`;
    }, [t]);

    useEffect(() => {
        if (!containerRef.current || !markers.length) {
            return;
        }

        let cancelled = false;
        const initId = initIdRef.current + 1;
        initIdRef.current = initId;

        const renderMarkers = (leaflet: any, map: any): void => {
            if (!markerLayerRef.current) {
                markerLayerRef.current = leaflet.layerGroup().addTo(map);
            } else {
                markerLayerRef.current.clearLayers();
            }

            markers.forEach((marker) => {
                leaflet.circleMarker([marker.latitude, marker.longitude], {
                    radius: 8,
                    color: colorMap[marker.marker_color] ?? colorMap.blue,
                    fillOpacity: 0.9,
                }).addTo(markerLayerRef.current).bindPopup(popupHtml(marker));
            });

            window.setTimeout(() => {
                if (mapInstanceRef.current === map) {
                    map.invalidateSize();
                }
            }, 0);
        };

        const init = () => {
            if (cancelled || initId !== initIdRef.current || !window.L || !containerRef.current) {
                return;
            }

            const container = containerRef.current as HTMLDivElement & {
                _leaflet_id?: number;
                __noticiarioLeafletMap?: any;
            };
            const existingMap = mapInstanceRef.current ?? container.__noticiarioLeafletMap;

            if (existingMap) {
                mapInstanceRef.current = existingMap;
                existingMap.setView(center, initialZoom);
                renderMarkers(window.L, existingMap);

                return;
            }

            if (container._leaflet_id) {
                delete container._leaflet_id;
            }

            const map = window.L.map(container).setView(center, initialZoom);
            container.__noticiarioLeafletMap = map;
            mapInstanceRef.current = map;

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            renderMarkers(window.L, map);
        };

        let pendingScript: HTMLScriptElement | null = null;
        const onLeafletLoad = (): void => init();
        const leafletStyles = document.querySelector('link[data-leaflet="1"]');

        if (!leafletStyles) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.dataset.leaflet = '1';
            document.head.appendChild(link);
        }

        if (!window.L) {
            pendingScript = document.querySelector('script[data-leaflet="1"]') as HTMLScriptElement | null;

            if (!pendingScript) {
                pendingScript = document.createElement('script');
                pendingScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                pendingScript.async = true;
                pendingScript.dataset.leaflet = '1';
                document.body.appendChild(pendingScript);
            }

            pendingScript.addEventListener('load', onLeafletLoad, { once: true });
        } else {
            init();
        }

        return () => {
            cancelled = true;
            pendingScript?.removeEventListener('load', onLeafletLoad);

            if (mapInstanceRef.current) {
                mapInstanceRef.current.remove();
                mapInstanceRef.current = null;
                markerLayerRef.current = null;
            }

            if (containerRef.current) {
                const container = containerRef.current as HTMLDivElement & {
                    _leaflet_id?: number;
                    __noticiarioLeafletMap?: any;
                };

                delete container.__noticiarioLeafletMap;
                delete container._leaflet_id;
            }
        };
    }, [center, initialZoom, markers, popupHtml]);

    if (!markers.length) {
        return <div className="rounded-lg border border-dashed p-8 text-center text-sm text-slate-500">{t('No bulletin locations configured yet')}</div>;
    }

    return <div ref={containerRef} className="h-[460px] w-full rounded-lg" />;
}
