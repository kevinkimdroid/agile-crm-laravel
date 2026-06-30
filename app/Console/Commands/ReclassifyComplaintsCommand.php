<?php

namespace App\Console\Commands;

use App\Models\Complaint;
use App\Services\ComplaintClassificationService;
use Illuminate\Console\Command;

class ReclassifyComplaintsCommand extends Command
{
    protected $signature = 'complaints:reclassify
                            {--source=Email : Only reclassify records from this source}
                            {--limit=500 : Max records to process}';

    protected $description = 'Re-score existing complaints so non-complaint email entries can be filtered out';

    public function handle(ComplaintClassificationService $classifier): int
    {
        $source = (string) $this->option('source');
        $limit = max(1, (int) $this->option('limit'));

        $query = Complaint::query()->orderByDesc('id');
        if ($source !== '') {
            $query->where('source', $source);
        }

        $rows = $query->limit($limit)->get();
        $counts = ['active' => 0, 'review' => 0, 'excluded' => 0];

        foreach ($rows as $complaint) {
            $result = $classifier->classifyComplaint($complaint);
            $counts[$result['register_status']] = ($counts[$result['register_status']] ?? 0) + 1;
        }

        $this->info("Reclassified {$rows->count()} record(s):");
        $this->line('  Complaints: ' . ($counts['active'] ?? 0));
        $this->line('  Needs review: ' . ($counts['review'] ?? 0));
        $this->line('  Not a complaint: ' . ($counts['excluded'] ?? 0));

        return self::SUCCESS;
    }
}
