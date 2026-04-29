<?php
namespace App\Jobs;
use App\Jobs\Concerns\TracksBackgroundTask;
use App\Models\BulletinPromptRun; use App\Models\NewsItem; use App\Models\Script;
use App\Services\EditorialReview\SourceReferenceExtractor;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue; use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue; use Illuminate\Queue\SerializesModels;
class ExtractSourceReferencesJob implements ShouldQueue { use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksBackgroundTask;
public function __construct(public string $contextType, public int $contextId, public ?int $userId = null, ?int $backgroundTaskId = null){$this->backgroundTaskId=$backgroundTaskId;}
public function handle(SourceReferenceExtractor $extractor): void { $this->markTaskRunning('Extracting sources'); $created=0; if($this->contextType==='bulletin_prompt_run'){$created=$extractor->extractFromBulletinPromptRun(BulletinPromptRun::findOrFail($this->contextId), $this->userId);} elseif($this->contextType==='script'){$created=$extractor->extractFromScript(Script::findOrFail($this->contextId), $this->userId);} else {$created=$extractor->extractFromNewsItem(NewsItem::findOrFail($this->contextId), $this->userId);} $this->markTaskCompleted('Source extraction queued',['created_count'=>$created]); }
public function failed(\Throwable $e): void { $this->markTaskFailed($e);} }
