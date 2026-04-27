<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $seo = $seoSettings ?? null;
        $defaultTitle = $seo?->default_title ?: config('app.name', 'Laravel');
        $resolvedTitle = $defaultTitle;
        if ($seo?->title_suffix && $seo?->title_suffix !== $defaultTitle) {
            $resolvedTitle = $defaultTitle.' | '.$seo->title_suffix;
        }

        $robots = $seo ? ($seo->enable_indexing ? ($seo->default_robots ?: 'index,follow') : 'noindex,nofollow') : null;

        $canonical = null;
        if ($seo?->canonical_base_url) {
            $path = request()->path();
            $normalizedPath = $path === '/' ? '' : '/'.ltrim($path, '/');
            $canonical = rtrim($seo->canonical_base_url, '/').$normalizedPath;
        }
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $resolvedTitle }}</title>

        @if ($seo?->default_description)
            <meta name="description" content="{{ $seo->default_description }}">
        @endif
        @if ($seo?->default_keywords)
            <meta name="keywords" content="{{ $seo->default_keywords }}">
        @endif
        @if ($robots)
            <meta name="robots" content="{{ $robots }}">
        @endif
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @endif

        @if ($seo?->google_site_verification)
            <meta name="google-site-verification" content="{{ $seo->google_site_verification }}">
        @endif
        @if ($seo?->bing_site_verification)
            <meta name="msvalidate.01" content="{{ $seo->bing_site_verification }}">
        @endif

        @if ($seo)
            <meta property="og:type" content="website">
            @if ($seo->site_name)
                <meta property="og:site_name" content="{{ $seo->site_name }}">
            @endif
            @if ($defaultTitle)
                <meta property="og:title" content="{{ $defaultTitle }}">
            @endif
            @if ($seo->default_description)
                <meta property="og:description" content="{{ $seo->default_description }}">
            @endif
            @if ($seo->default_og_image)
                <meta property="og:image" content="{{ $seo->default_og_image }}">
            @endif

            @if ($seo->default_twitter_card)
                <meta name="twitter:card" content="{{ $seo->default_twitter_card }}">
            @endif
            @if ($defaultTitle)
                <meta name="twitter:title" content="{{ $defaultTitle }}">
            @endif
            @if ($seo->default_description)
                <meta name="twitter:description" content="{{ $seo->default_description }}">
            @endif
            @if ($seo->default_og_image)
                <meta name="twitter:image" content="{{ $seo->default_og_image }}">
            @endif
        @endif

        @if ($seo?->enable_tracking)
            @if ($seo->google_tag_manager_id)
                <script>
                    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                    })(window,document,'script','dataLayer','{{ $seo->google_tag_manager_id }}');
                </script>
            @endif

            @if ($seo->google_analytics_id)
                <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo->google_analytics_id }}"></script>
                <script>
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}
                    gtag('js', new Date());
                    gtag('config', '{{ $seo->google_analytics_id }}');
                </script>
            @endif

            @if ($seo->microsoft_clarity_id)
                <script>
                    (function(c,l,a,r,i,t,y){
                        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
                    })(window, document, "clarity", "script", "{{ $seo->microsoft_clarity_id }}");
                </script>
            @endif

            @if ($seo->meta_pixel_id)
                <script>
                    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,
                    'script', 'https://connect.facebook.net/en_US/fbevents.js');
                    fbq('init', '{{ $seo->meta_pixel_id }}');
                    fbq('track', 'PageView');
                </script>
            @endif

            @if ($seo->tiktok_pixel_id)
                <script>
                    !function (w, d, t) {
                        w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
                        ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];
                        ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
                        for(var i=0;i<ttq.methods.length;i++) ttq.setAndDefer(ttq,ttq.methods[i]);
                        ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";
                        ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=r;ttq._t=ttq._t||{};ttq._t[e]=+new Date;
                        ttq._o=ttq._o||{};ttq._o[e]=n||{};n=d.createElement("script");n.type="text/javascript";
                        n.async=!0;n.src=r+"?sdkid="+e+"&lib="+t;
                        e=d.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
                        ttq.load('{{ $seo->tiktok_pixel_id }}');
                        ttq.page();
                    }(window, document, 'ttq');
                </script>
            @endif
        @endif

        @if ($seo?->enable_custom_scripts && $seo->custom_head_scripts)
            {!! $seo->custom_head_scripts !!}
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @if ($seo?->enable_tracking && $seo->google_tag_manager_id)
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $seo->google_tag_manager_id }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endif

        @if ($seo?->enable_custom_scripts && $seo->custom_body_start_scripts)
            {!! $seo->custom_body_start_scripts !!}
        @endif

        @inertia

        @if ($seo?->enable_custom_scripts && $seo->custom_body_end_scripts)
            {!! $seo->custom_body_end_scripts !!}
        @endif
    </body>
</html>
