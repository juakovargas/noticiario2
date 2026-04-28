<?php

namespace Database\Seeders;

use App\Models\HomePageSetting;
use Illuminate\Database\Seeder;

class HomePageSettingSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = ['youtube_shorts', 'tiktok', 'instagram_reels', 'facebook', 'x_twitter', 'youtube', 'website'];

        HomePageSetting::query()->updateOrCreate(
            ['locale' => 'en'],
            [
                'title' => 'Digital automated news bulletins',
                'subtitle' => 'Build scripts and production-ready content for multiple territories and social platforms.',
                'description' => 'Noticiario helps teams prepare short briefings and publication-ready workflows.',
                'hero_badge' => 'Editorial production platform',
                'primary_button_label' => 'Login',
                'primary_button_url' => '/login',
                'secondary_button_label' => 'Bulletin coverage',
                'secondary_button_url' => '/',
                'show_latest_noticiarios' => true,
                'latest_noticiarios_limit' => 6,
                'show_world_map_preview' => true,
                'show_platforms_section' => true,
                'platforms' => $platforms,
                'is_active' => true,
            ],
        );

        HomePageSetting::query()->updateOrCreate(
            ['locale' => 'es'],
            [
                'title' => 'Noticiarios digitales automatizados',
                'subtitle' => 'Crea guiones, audio y vídeo informativo para múltiples territorios y redes sociales.',
                'description' => 'Noticiario permite preparar informativos breves, locales o temáticos, generar prompts editoriales, revisar guiones y preparar contenidos para publicación en redes.',
                'hero_badge' => 'Plataforma de producción informativa',
                'primary_button_label' => 'Acceder',
                'primary_button_url' => '/login',
                'secondary_button_label' => 'Ver cobertura',
                'secondary_button_url' => '/',
                'show_latest_noticiarios' => true,
                'latest_noticiarios_limit' => 6,
                'show_world_map_preview' => true,
                'show_platforms_section' => true,
                'platforms' => $platforms,
                'is_active' => true,
            ],
        );

        HomePageSetting::query()->updateOrCreate(
            ['locale' => 'fr'],
            [
                'title' => 'Bulletins numériques automatisés',
                'subtitle' => 'Créez des scripts et contenus prêts à la publication pour plusieurs territoires.',
                'description' => 'Noticiario aide à préparer des journaux courts, réviser des scripts et organiser la publication sociale.',
                'hero_badge' => 'Plateforme éditoriale',
                'primary_button_label' => 'Connexion',
                'primary_button_url' => '/login',
                'secondary_button_label' => 'Voir la couverture',
                'secondary_button_url' => '/',
                'show_latest_noticiarios' => true,
                'latest_noticiarios_limit' => 6,
                'show_world_map_preview' => true,
                'show_platforms_section' => true,
                'platforms' => $platforms,
                'is_active' => true,
            ],
        );
    }
}
