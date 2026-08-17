<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$host = $argv[1] ?? '10.1.1.64';
$ticketNos = array_slice($argv, 2);
if ($ticketNos === []) {
    $ticketNos = ['TT2025824905', 'TT2025824903'];
}

$cfg = config('database.connections.vtiger');
config(['database.connections.vtiger_purge' => array_merge($cfg, ['host' => $host])]);
$db = Illuminate\Support\Facades\DB::connection('vtiger_purge');

echo "Host: {$host} / " . ($cfg['database'] ?? 'vtiger') . PHP_EOL;

foreach ($ticketNos as $no) {
    $row = $db->table('vtiger_troubletickets as t')
        ->join('vtiger_crmentity as e', 't.ticketid', '=', 'e.crmid')
        ->where('t.ticket_no', $no)
        ->select('t.ticketid', 't.ticket_no', 't.title', 'e.deleted', 'e.description')
        ->first();

    if (! $row) {
        echo "{$no}: NOT FOUND" . PHP_EOL;
        continue;
    }

    echo "{$no}: id={$row->ticketid} deleted={$row->deleted} title=" . substr($row->title ?? '', 0, 80) . PHP_EOL;

    $db->table('vtiger_crmentity')
        ->where('crmid', $row->ticketid)
        ->update([
            'deleted' => 1,
            'modifiedtime' => now()->format('Y-m-d H:i:s'),
        ]);
    echo "  -> soft-deleted" . PHP_EOL;
}

echo "Done." . PHP_EOL;
