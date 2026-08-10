<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reclaim InnoDB disk after CRM hard-purge (TRUNCATE leaves allocated .ibd space
 * until the .ibd is recreated — MySQL 8 often no-ops OPTIMIZE/FORCE).
 */
class ReclaimKenyaOrientDatabaseSpaceCommand extends Command
{
    protected $signature = 'kenya-orient:reclaim-db-space
                            {--force : Skip confirmation}
                            {--purge-leftovers : Also truncate remaining non-POC junk tables}
                            {--min-mb=1 : Only rebuild tables at least this size (MB)}';

    protected $description = 'Recreate emptied CRM tables to reclaim disk on .72 vtiger DB';

    /** @var list<string> */
    protected array $leftoverTables = [
        'vtiger_mailscanner_ids',
        'vtiger_shorturls',
        'vtiger_seproductsrel',
        'vtiger_loginhistory',
        'mail_manager_emails',
        'mail_manager_attachments',
        'sms_logs',
        'its4you_error_log',
        'cache',
        'maturities_cache',
        'mass_broadcast_sends',
        'mass_broadcast_recipients',
        'pbx_calls',
        'investment_maturity_notifications',
        'com_vtiger_workflow_activatedonce',
        'work_tickets',
        'work_ticket_updates',
        'ticket_reassignments',
        'complaints',
        'complaint_attachments',
        'complaint_updates',
    ];

    public function handle(): int
    {
        $host = config('database.connections.vtiger.host');
        $db = config('database.connections.vtiger.database');
        $this->warn("Reclaim space on {$host} / {$db}");

        if (! $this->option('force') && ! $this->confirm('Recreate large empty tables to reclaim disk?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $conn = DB::connection('vtiger');

        if ($this->option('purge-leftovers')) {
            $this->purgeLeftovers($conn);
        }

        $before = $this->dbSizeMb($conn, $db);
        $this->line("DB size before: {$before} MB");

        $minMb = (float) $this->option('min-mb');
        $tables = $conn->select(
            "SELECT table_name AS t,
                    ROUND((data_length+index_length)/1024/1024,2) AS mb
             FROM information_schema.tables
             WHERE table_schema = ?
               AND table_type = 'BASE TABLE'
               AND engine = 'InnoDB'
               AND (data_length+index_length) >= ?
             ORDER BY (data_length+index_length) DESC",
            [$db, (int) ($minMb * 1024 * 1024)]
        );

        $optimized = 0;
        $conn->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $row) {
            $table = $row->t;
            if (! Schema::connection('vtiger')->hasTable($table)) {
                continue;
            }

            try {
                $count = $conn->table($table)->count();
            } catch (\Throwable $e) {
                $this->error("skip {$table}: " . $e->getMessage());
                continue;
            }

            // Only rebuild empty tables that still hold allocated space
            if ($count > 0) {
                $this->line(sprintf('skip %-45s %8.2f MB  rows=%d (has data)', $table, $row->mb, $count));
                continue;
            }

            // MySQL 8 often no-ops OPTIMIZE/ALTER…ENGINE/FORCE on already-InnoDB tables.
            // CREATE LIKE + DROP + RENAME recreates the .ibd and reclaims disk.
            $tmp = $table . '_reclaim_tmp';
            $this->line(sprintf('RECREATE %-42s %8.2f MB  rows=%d …', $table, $row->mb, $count));
            try {
                $conn->statement("DROP TABLE IF EXISTS `{$tmp}`");
                $conn->statement("CREATE TABLE `{$tmp}` LIKE `{$table}`");
                $conn->statement("DROP TABLE `{$table}`");
                $conn->statement("RENAME TABLE `{$tmp}` TO `{$table}`");
                $optimized++;
            } catch (\Throwable $e) {
                $this->error("FAIL {$table}: " . $e->getMessage());
                try {
                    $conn->statement("DROP TABLE IF EXISTS `{$tmp}`");
                } catch (\Throwable $ignored) {
                }
            }
        }

        $conn->statement('SET FOREIGN_KEY_CHECKS=1');

        $after = $this->dbSizeMb($conn, $db);
        $this->info("Rebuilt {$optimized} empty tables.");
        $this->info("DB size after: {$after} MB (was {$before} MB, freed ~" . round($before - $after, 1) . ' MB)');

        return self::SUCCESS;
    }

    protected function purgeLeftovers($conn): void
    {
        $this->info('Truncating leftover non-POC junk tables…');
        $conn->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->leftoverTables as $table) {
            if (! Schema::connection('vtiger')->hasTable($table)) {
                continue;
            }
            try {
                $n = $conn->table($table)->count();
                if ($n === 0) {
                    continue;
                }
                $conn->table($table)->truncate();
                $this->line("truncated {$table} (was {$n})");
            } catch (\Throwable $e) {
                try {
                    $deleted = $conn->table($table)->delete();
                    $this->line("deleted {$table} ({$deleted})");
                } catch (\Throwable $e2) {
                    $this->error("FAIL {$table}: " . $e2->getMessage());
                }
            }
        }
        $conn->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function dbSizeMb($conn, string $db): float
    {
        $row = $conn->selectOne(
            'SELECT ROUND(SUM(data_length+index_length)/1024/1024,1) AS mb
             FROM information_schema.tables WHERE table_schema = ?',
            [$db]
        );

        return (float) ($row->mb ?? 0);
    }
}
