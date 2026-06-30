<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache TTLs (seconds)
    |--------------------------------------------------------------------------
    | Remote vtiger + ERP HTTP are slow; prefer longer caches for list/count data.
    */

    'cache_ttl' => [
        'counts' => (int) env('PERF_CACHE_COUNTS', 300),
        'dashboard' => (int) env('PERF_CACHE_DASHBOARD', 600),
        'erp_clients_count' => (int) env('PERF_CACHE_ERP_CLIENTS', 900),
        'serve_client_search' => (int) env('PERF_CACHE_SERVE_SEARCH', 300),
        'clients_list' => (int) env('PERF_CACHE_CLIENTS_LIST', 300),
        'erp_policy_details' => (int) env('PERF_CACHE_ERP_POLICY', 600),
        'client_tab_counts' => (int) env('PERF_CACHE_CLIENT_TAB_COUNTS', 600),
        'client_tab_counts_slow' => (int) env('PERF_CACHE_CLIENT_TAB_COUNTS_SLOW', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Local dev: skip heavy scheduler jobs (PBX CDR sync, mail fetch every minute)
    |--------------------------------------------------------------------------
    | Set SCHEDULER_RUN_IN_LOCAL=true if you need them while developing.
    */

    'scheduler_in_local' => filter_var(env('SCHEDULER_RUN_IN_LOCAL', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | ERP clients list: paint page first, load rows via AJAX
    |--------------------------------------------------------------------------
    */

    'erp_clients_lazy_load' => filter_var(
        env('ERP_CLIENTS_LAZY_LOAD', env('CLIENTS_VIEW_SOURCE', 'crm') === 'erp_http' ? 'true' : 'false'),
        FILTER_VALIDATE_BOOLEAN
    ),

];
