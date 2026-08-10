<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hard-purge Vtiger module DATA tables (not soft-delete).
 * Keeps users ewanguba/kkmutai, roles/profiles/fields/picklists, and Laravel POC tables.
 */
class HardPurgeCrmDataCommand extends Command
{
    protected $signature = 'kenya-orient:hard-purge-crm
                            {--dry-run : List tables only}
                            {--force : Skip confirmation}
                            {--delete-other-users : Physically remove inactive users (keep ewanguba/kkmutai)}';

    protected $description = 'Hard truncate CRM data tables on .72 for a clean Kenya Orient DB';

    /** @var list<string> */
    protected array $keepUsernames = ['ewanguba', 'kkmutai'];

    /**
     * Module / transactional data tables to empty.
     * Do NOT include picklist/config tables (*status, *type, *priority, mapping, seq).
     *
     * @var list<string>
     */
    protected array $dataTables = [
        // Contacts
        'vtiger_contactdetails',
        'vtiger_contactaddress',
        'vtiger_contactscf',
        'vtiger_contactsubdetails',
        'vtiger_customerdetails',
        // Leads
        'vtiger_leaddetails',
        'vtiger_leadaddress',
        'vtiger_leadscf',
        'vtiger_leadsubdetails',
        // Accounts / Opps
        'vtiger_account',
        'vtiger_accountbillads',
        'vtiger_accountshipads',
        'vtiger_accountscf',
        'vtiger_potential',
        'vtiger_potentialscf',
        'vtiger_contpotentialrel',
        // Tickets
        'vtiger_troubletickets',
        'vtiger_ticketcf',
        'vtiger_ticketcomments',
        // Calendar / Activity
        'vtiger_activity',
        'vtiger_activitycf',
        'vtiger_activity_reminder',
        'vtiger_activity_reminder_popup',
        'vtiger_seactivityrel',
        'vtiger_cntactivityrel',
        'vtiger_salesmanactivityrel',
        'vtiger_invitees',
        'vtiger_recurringevents',
        // Emails
        'vtiger_emaildetails',
        'vtiger_email_track',
        'vtiger_emailslookup',
        'vtiger_email_access',
        'vtiger_emails_recipientprefs',
        // Documents / attachments
        'vtiger_notes',
        'vtiger_notescf',
        'vtiger_senotesrel',
        'vtiger_attachments',
        'vtiger_seattachmentsrel',
        'vtiger_salesmanattachmentsrel',
        'vtiger_mailmanager_mailrecord',
        'vtiger_mailmanager_mailattachments',
        'vtiger_mailmanager_mailrel',
        // Comments / tracker
        'vtiger_modcomments',
        'vtiger_modcommentscf',
        'vtiger_modtracker_basic',
        'vtiger_modtracker_detail',
        'vtiger_modtracker_relations',
        // Entity core
        'vtiger_crmentity',
        'vtiger_crmentityrel',
        'vtiger_crmentity_user_field',
        'vtiger_tracker',
        // PBX / SMS
        'vtiger_pbxmanager',
        'vtiger_pbxmanagercf',
        'vtiger_pbxmanager_phonelookup',
        'vtiger_smsnotifier',
        'vtiger_smsnotifiercf',
        'vtiger_smsnotifier_status',
        // Campaigns / FAQ records (not picklists)
        'vtiger_campaign',
        'vtiger_campaignscf',
        'vtiger_faq',
        'vtiger_faqcf',
        // Projects
        'vtiger_project',
        'vtiger_projectcf',
        'vtiger_projecttask',
        'vtiger_projecttaskcf',
        'vtiger_projectmilestone',
        'vtiger_projectmilestonecf',
        // Misc sales docs
        'vtiger_invoice',
        'vtiger_invoicecf',
        'vtiger_inventoryproductrel',
        'vtiger_quotes',
        'vtiger_quotescf',
        'vtiger_salesorder',
        'vtiger_salesordercf',
        'vtiger_purchaseorder',
        'vtiger_purchaseordercf',
        'vtiger_products',
        'vtiger_productcf',
        'vtiger_service',
        'vtiger_servicecf',
        'vtiger_vendor',
        'vtiger_vendorcf',
        'vtiger_pricebook',
        'vtiger_pricebookcf',
        // Calendar user prefs clutter
        'vtiger_calendar_user_activitytypes',
        // Mail scanner / short URLs / product links (often huge leftovers)
        'vtiger_mailscanner_ids',
        'vtiger_mailscanner_actions',
        'vtiger_mailscanner_folders',
        'vtiger_mailscanner_rules',
        'vtiger_mailscanner_ruleactions',
        'vtiger_shorturls',
        'vtiger_seproductsrel',
        'vtiger_loginhistory',
        // Laravel / app operational clutter (not POC seed data)
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
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->warn('HARD PURGE on ' . config('database.connections.vtiger.host') . ' / ' . config('database.connections.vtiger.database'));
        $this->line('Will TRUNCATE CRM data tables. Picklists/roles/profiles/fields kept.');
        $this->line('Users kept: ' . implode(', ', $this->keepUsernames));

        if (! $dry && ! $force && ! $this->confirm('Permanently wipe CRM data tables?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $conn = DB::connection('vtiger');
        $existing = [];
        foreach ($this->dataTables as $table) {
            if (Schema::connection('vtiger')->hasTable($table)) {
                try {
                    $existing[$table] = $conn->table($table)->count();
                } catch (\Throwable $e) {
                    $existing[$table] = -1;
                }
            }
        }

        arsort($existing);
        foreach ($existing as $table => $count) {
            $this->line(str_pad((string) $count, 10, ' ', STR_PAD_LEFT) . '  ' . $table);
        }
        $this->info('Tables to purge: ' . count($existing));

        if ($dry) {
            $this->warn('Dry run — no changes.');

            return self::SUCCESS;
        }

        $conn->statement('SET FOREIGN_KEY_CHECKS=0');
        $purged = 0;
        foreach (array_keys($existing) as $table) {
            try {
                $conn->table($table)->truncate();
                $this->line("truncated {$table}");
                $purged++;
            } catch (\Throwable $e) {
                // Fallback delete if truncate blocked
                try {
                    $conn->table($table)->delete();
                    $this->line("deleted {$table}");
                    $purged++;
                } catch (\Throwable $e2) {
                    $this->error("FAIL {$table}: " . $e2->getMessage());
                }
            }
        }

        // Reset entity sequence
        if (Schema::connection('vtiger')->hasTable('vtiger_crmentity_seq')) {
            $conn->table('vtiger_crmentity_seq')->delete();
            $conn->table('vtiger_crmentity_seq')->insert(['id' => 1]);
            $this->line('reset vtiger_crmentity_seq');
        }

        $conn->statement('SET FOREIGN_KEY_CHECKS=1');

        if ($this->option('delete-other-users')) {
            $this->deleteOtherUsers($conn);
        }

        // Verify keep users still active
        $conn->table('vtiger_users')
            ->whereIn('user_name', $this->keepUsernames)
            ->update(['status' => 'Active', 'is_admin' => 'on']);

        $contacts = Schema::connection('vtiger')->hasTable('vtiger_contactdetails')
            ? $conn->table('vtiger_contactdetails')->count() : -1;
        $tickets = Schema::connection('vtiger')->hasTable('vtiger_troubletickets')
            ? $conn->table('vtiger_troubletickets')->count() : -1;
        $crm = Schema::connection('vtiger')->hasTable('vtiger_crmentity')
            ? $conn->table('vtiger_crmentity')->count() : -1;
        $activeUsers = $conn->table('vtiger_users')->where('status', 'Active')->count();

        $this->info("Done. Purged {$purged} tables.");
        $this->line("contacts={$contacts} tickets={$tickets} crmentity={$crm} active_users={$activeUsers}");

        return self::SUCCESS;
    }

    protected function deleteOtherUsers($conn): void
    {
        $this->info('Deleting users other than ewanguba / kkmutai…');
        $keepIds = $conn->table('vtiger_users')
            ->whereIn('user_name', $this->keepUsernames)
            ->pluck('id')
            ->all();

        if (count($keepIds) < 2) {
            $this->error('Keep users missing — skip user delete.');

            return;
        }

        $otherIds = $conn->table('vtiger_users')->whereNotIn('id', $keepIds)->pluck('id')->all();
        $this->line('Users to delete: ' . count($otherIds));

        $userTables = [
            'vtiger_user2role',
            'vtiger_users2group',
            'vtiger_user_preferences',
            'vtiger_user_module_preferences',
            'vtiger_users_last_import',
            'vtiger_homestuff',
            'vtiger_homemodule',
            'vtiger_homedashbd',
            'vtiger_homereportchart',
            'vtiger_homerss',
            'vtiger_homewidget_share',
            'vtiger_asteriskextensions',
            'vtiger_sharedcalendar',
            'vtiger_shareduserinfo',
        ];

        $conn->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($userTables as $table) {
            if (! Schema::connection('vtiger')->hasTable($table)) {
                continue;
            }
            // column may be userid or id
            $cols = Schema::connection('vtiger')->getColumnListing($table);
            if (in_array('userid', $cols, true)) {
                $conn->table($table)->whereNotIn('userid', $keepIds)->delete();
            } elseif (in_array('id', $cols, true) && $table !== 'vtiger_users') {
                // homestuff uses userid typically
            }
        }
        $conn->table('vtiger_users')->whereNotIn('id', $keepIds)->delete();
        $conn->statement('SET FOREIGN_KEY_CHECKS=1');
        $this->line('Remaining users: ' . $conn->table('vtiger_users')->count());
    }
}
