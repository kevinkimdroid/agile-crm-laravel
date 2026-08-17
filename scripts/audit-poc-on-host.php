<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$host = $argv[1] ?? '10.1.1.64';
$cfg = config('database.connections.vtiger');
config(['database.connections.vtiger_audit' => array_merge($cfg, ['host' => $host])]);
$db = Illuminate\Support\Facades\DB::connection('vtiger_audit');

$contacts = $db->table('vtiger_contactdetails as c')
    ->join('vtiger_crmentity as e', 'c.contactid', '=', 'e.crmid')
    ->where('c.email', 'like', '%@orient-demo.%')
    ->select('c.contactid', 'c.email', 'e.deleted')
    ->get();

echo "orient-demo contacts on {$host}: " . $contacts->count() . PHP_EOL;
foreach ($contacts as $c) {
    echo "  id={$c->contactid} deleted={$c->deleted} {$c->email}" . PHP_EOL;
}

$tickets = $db->table('vtiger_troubletickets as t')
    ->join('vtiger_crmentity as e', 't.ticketid', '=', 'e.crmid')
    ->where(function ($w) {
        $w->where('t.title', 'like', '%KOL-%')
            ->orWhere('e.description', 'like', '%POC demo%');
    })
    ->select('t.ticket_no', 't.title', 'e.deleted')
    ->get();

echo "KOL/POC tickets on {$host}: " . $tickets->count() . PHP_EOL;
foreach ($tickets as $t) {
    echo "  {$t->ticket_no} deleted={$t->deleted} {$t->title}" . PHP_EOL;
}
