<?php

namespace Tests\Feature;

use App\Models\Script;
use App\Models\ScriptAudioRender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class ScriptAudioRenderTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_generate_an_audio_render_from_a_script_using_a_faked_http_response(): void
    {
        Storage::fake('public');
        config([
            'services.elevenlabs.api_key' => 'test-elevenlabs-key',
            'services.elevenlabs.default_voice_id' => 'voice-test',
            'services.elevenlabs.default_model_id' => 'model-test',
            'services.elevenlabs.default_output_format' => 'mp3_44100_128',
        ]);

        Http::fake(function ($request) {
            $this->assertSame('test-elevenlabs-key', $request->header('xi-api-key')[0] ?? null);
            $this->assertSame('audio/mpeg', $request->header('Accept')[0] ?? null);
            $this->assertStringContainsString('/v1/text-to-speech/voice-test', $request->url());
            $this->assertStringContainsString('output_format=mp3_44100_128', $request->url());
            $this->assertSame("Intro text\n\nBody text\n\nOutro text", $request->data()['text'] ?? null);
            $this->assertSame('model-test', $request->data()['model_id'] ?? null);

            return Http::response('fake-mp3-binary', 200, ['Content-Type' => 'audio/mpeg']);
        });

        $editor = $this->createUserWithPermissions(['editor.access']);
        $script = Script::factory()->create([
            'intro' => 'Intro text',
            'body' => 'Body text',
            'outro' => 'Outro text',
        ]);

        $this->actingAs($editor)
            ->post(route('editor.scripts.audio-renders.store', $script))
            ->assertRedirect()
            ->assertSessionHas('success', 'Audio generated successfully.');

        $render = ScriptAudioRender::query()->where('script_id', $script->id)->firstOrFail();

        $this->assertSame('completed', $render->status);
        $this->assertSame('elevenlabs', $render->provider);
        $this->assertSame('voice-test', $render->voice_id);
        $this->assertSame('model-test', $render->model_id);
        $this->assertSame('mp3_44100_128', $render->output_format);
        $this->assertSame(mb_strlen("Intro text\n\nBody text\n\nOutro text"), $render->character_count);
        $this->assertNotNull($render->generated_at);
        Storage::disk('public')->assertExists($render->audio_path);
        $this->assertSame('fake-mp3-binary', Storage::disk('public')->get($render->audio_path));

        $this->actingAs($editor)
            ->get(route('editor.scripts.show', $script))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('latestAudioRender.status', 'completed')
                ->where('latestAudioRender.provider', 'elevenlabs')
                ->has('latestAudioRender.audio_url')
                ->has('latestAudioRender.download_url')
            );
    }

    public function test_missing_api_key_returns_a_controlled_error_and_marks_the_render_as_failed(): void
    {
        config(['services.elevenlabs.api_key' => null]);
        Http::fake(['*' => Http::response('should not be called', 200)]);

        $editor = $this->createUserWithPermissions(['editor.access']);
        $script = Script::factory()->create(['body' => 'Text ready for audio.']);

        $this->actingAs($editor)
            ->post(route('editor.scripts.audio-renders.store', $script))
            ->assertRedirect()
            ->assertSessionHas('error', 'ElevenLabs API key is not configured.');

        Http::assertNothingSent();
        $this->assertDatabaseHas('script_audio_renders', [
            'script_id' => $script->id,
            'status' => 'failed',
            'error_message' => 'ElevenLabs API key is not configured.',
        ]);
    }

    public function test_empty_script_text_does_not_call_elevenlabs_and_shows_a_validation_error(): void
    {
        config(['services.elevenlabs.api_key' => 'test-elevenlabs-key']);
        Http::fake(['*' => Http::response('should not be called', 200)]);

        $editor = $this->createUserWithPermissions(['editor.access']);
        $script = Script::factory()->create([
            'intro' => null,
            'body' => null,
            'outro' => null,
        ]);

        $this->actingAs($editor)
            ->post(route('editor.scripts.audio-renders.store', $script))
            ->assertRedirect()
            ->assertSessionHas('error', 'The script has no text to convert into audio.');

        Http::assertNothingSent();
        $this->assertDatabaseHas('script_audio_renders', [
            'script_id' => $script->id,
            'status' => 'failed',
            'character_count' => 0,
            'error_message' => 'The script has no text to convert into audio.',
        ]);
    }

    public function test_completed_audio_render_can_be_downloaded(): void
    {
        Storage::fake('public');

        $editor = $this->createUserWithPermissions(['editor.access']);
        $script = Script::factory()->create();
        $path = sprintf('scripts/%d/audio/script_%d_audio_1.mp3', $script->id, $script->id);
        Storage::disk('public')->put($path, 'downloadable-mp3');

        $render = ScriptAudioRender::query()->create([
            'script_id' => $script->id,
            'status' => 'completed',
            'provider' => 'elevenlabs',
            'audio_path' => $path,
            'audio_disk' => 'public',
            'generated_at' => now(),
        ]);

        $this->actingAs($editor)
            ->get(route('editor.scripts.audio-renders.download', [$script, $render]))
            ->assertDownload(basename($path));
    }

    public function test_user_without_editor_access_cannot_generate_audio(): void
    {
        Http::fake(['*' => Http::response('should not be called', 200)]);

        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $script = Script::factory()->create(['body' => 'Text ready for audio.']);

        $this->actingAs($viewer)
            ->post(route('editor.scripts.audio-renders.store', $script))
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertDatabaseMissing('script_audio_renders', ['script_id' => $script->id]);
    }
}
