<?php

namespace App\Console\Commands;

use App\Models\BulletinType;
use App\Services\Scheduling\BulletinTypeScheduleSyncService;
use Illuminate\Console\Command;

class SyncBulletinSchedulesCommand extends Command
{
    protected $signature = 'noticiario:sync-bulletin-schedules {--dry-run} {--bulletin-type-id=} {--activate} {--force}';
    protected $description = 'Create/update primary editorial schedules from bulletin type defaults';

    public function handle(BulletinTypeScheduleSyncService $sync): int
    {
        $query = BulletinType::query();
        if ($id = $this->option('bulletin-type-id')) $query->whereKey((int) $id);
        $created=0;$updated=0;$inspected=0;$skipped=0;
        foreach ($query->get() as $bt) {
            $inspected++;
            if (! $sync->shouldHavePrimarySchedule($bt) && ! $this->option('force')) { $skipped++; continue; }
            $existing = $bt->primarySchedule()->exists();
            if (! $this->option('dry-run')) {
                $schedule = $sync->syncPrimarySchedule($bt);
                if ($this->option('activate')) { $schedule->update(['is_active'=>true]); }
            }
            $existing ? $updated++ : $created++;
        }
        $this->info("inspected={$inspected} created={$created} updated={$updated} skipped={$skipped}");
        return self::SUCCESS;
    }
}
