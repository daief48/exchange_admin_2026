<?php

namespace App\Console\Commands;

use App\Mail\MissingEntryNotificationMail;
use App\Models\Depot;
use App\Models\Expense;
use App\Models\MissingEntryLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckMissingDepotEntries extends Command
{
    protected $signature = 'app:check-missing-depot-entries {--date= : The date to check (YYYY-MM-DD), defaults to today}';
    protected $description = 'Identifies active depots with 0 entries on the given date and sends alert notifications';

    public function handle(): int
    {
        $targetDate = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : Carbon::today()->toDateString();
        $this->info("Scanning active depots for missing expense entries on: {$targetDate}");

        $activeDepots = Depot::where('status', true)->get();
        $missingCount = 0;

        foreach ($activeDepots as $depot) {
            $entriesCount = Expense::where('depot_id', $depot->id)
                ->whereDate('date', $targetDate)
                ->count();

            if ($entriesCount === 0) {
                $missingCount++;
                $this->warn("Depot non-compliant [0 entries]: {$depot->name} (Code: {$depot->code})");

                // Find recipient emails
                $recipients = [];
                if ($depot->contact_email) {
                    $recipients[] = $depot->contact_email;
                }

                $depotUsers = User::where('depot_id', $depot->id)->pluck('email')->toArray();
                $recipients = array_unique(array_merge($recipients, $depotUsers));

                // Head Office monitoring team
                $hoEmail = config('mail.monitoring_address', 'monitoring@aci-bd.com');
                $recipients[] = $hoEmail;
                $recipients = array_unique(array_filter($recipients));

                // Send email notification
                try {
                    Mail::to($recipients)->send(new MissingEntryNotificationMail($depot, $targetDate));
                    $this->info(" -> Alert email sent to: " . implode(', ', $recipients));
                } catch (\Throwable $e) {
                    $this->error(" -> Failed to send alert email: " . $e->getMessage());
                }

                // Record in missing_entry_logs table
                MissingEntryLog::updateOrCreate(
                    [
                        'depot_id' => $depot->id,
                        'date' => $targetDate,
                    ],
                    [
                        'sent_at' => now(),
                        'recipients' => implode(', ', $recipients),
                    ]
                );
            }
        }

        $this->info("Check completed. Total non-compliant depots flagged: {$missingCount} of {$activeDepots->count()}");
        return Command::SUCCESS;
    }
}
