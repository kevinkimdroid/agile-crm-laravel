@php
$stats = $overviewStats ?? ['users' => 0, 'roles' => 0, 'departments' => 0, 'modules' => 0];
$sections = [
    'People' => [
        ['section' => 'users', 'icon' => 'bi-person', 'name' => 'Users', 'hint' => 'Accounts, status, and reporting lines', 'keywords' => 'users people staff accounts'],
        ['section' => 'departments', 'icon' => 'bi-building', 'name' => 'Departments', 'hint' => 'Organize teams and user departments', 'keywords' => 'departments teams org'],
        ['section' => 'roles', 'icon' => 'bi-shield-lock', 'name' => 'Roles', 'hint' => 'Assign profiles to CRM roles', 'keywords' => 'roles permissions access'],
        ['section' => 'profiles', 'icon' => 'bi-person-vcard', 'name' => 'Profiles', 'hint' => 'Module permissions and field access', 'keywords' => 'profiles permissions modules'],
        ['section' => 'client-access', 'icon' => 'bi-person-check', 'name' => 'Client Access', 'hint' => 'Restrict users to assigned policies', 'keywords' => 'client access policy assignment'],
        ['section' => 'sharing-rules', 'icon' => 'bi-share', 'name' => 'Sharing Rules', 'hint' => 'Record visibility across the CRM', 'keywords' => 'sharing rules visibility'],
        ['section' => 'groups', 'icon' => 'bi-people', 'name' => 'Groups', 'hint' => 'Vtiger groups for assignment', 'keywords' => 'groups teams'],
        ['section' => 'login-history', 'icon' => 'bi-clock-history', 'name' => 'Login History', 'hint' => 'Recent sign-in activity', 'keywords' => 'login history audit security'],
    ],
    'Tickets' => [
        ['section' => 'ticket-dropdowns', 'icon' => 'bi-ui-checks', 'name' => 'Create Ticket Form', 'hint' => 'Categories and sources on new tickets', 'keywords' => 'ticket form dropdown categories sources'],
        ['section' => 'ticket-sla', 'icon' => 'bi-stopwatch', 'name' => 'SLA & TAT', 'hint' => 'Department turnaround and close rules', 'keywords' => 'sla tat turnaround close'],
        ['section' => 'ticket-automation', 'icon' => 'bi-arrow-left-right', 'name' => 'Assignment Rules', 'hint' => 'Auto-route tickets by keywords', 'keywords' => 'automation assignment rules routing'],
        ['section' => 'scheduler', 'icon' => 'bi-calendar-check', 'name' => 'Scheduler', 'hint' => 'Cron tasks and scheduled jobs', 'keywords' => 'scheduler cron jobs tasks'],
        ['section' => 'workflows', 'icon' => 'bi-diagram-3', 'name' => 'Workflows', 'hint' => 'Automated CRM workflows', 'keywords' => 'workflows automation'],
    ],
    'Modules' => [
        ['section' => 'modules', 'icon' => 'bi-grid', 'name' => 'Modules', 'hint' => 'Enable or disable app modules', 'keywords' => 'modules enable disable features'],
        ['route' => 'settings.layout-editor', 'icon' => 'bi-layout-text-sidebar', 'name' => 'Layouts & Fields', 'hint' => 'Edit module layouts and fields', 'keywords' => 'layouts fields editor'],
        ['section' => 'module-numbering', 'icon' => 'bi-hash', 'name' => 'Numbering', 'hint' => 'Ticket and record number sequences', 'keywords' => 'numbering sequences prefix'],
    ],
    'System' => [
        ['section' => 'configuration', 'icon' => 'bi-sliders', 'name' => 'General', 'hint' => 'Core CRM configuration options', 'keywords' => 'configuration general settings'],
        ['section' => 'pbx-extension-mapping', 'icon' => 'bi-telephone', 'name' => 'PBX Mapping', 'hint' => 'Map phone extensions to users', 'keywords' => 'pbx phone extension mapping calls'],
        ['section' => 'marketing', 'icon' => 'bi-bullseye', 'name' => 'Marketing', 'hint' => 'Campaign and marketing settings', 'keywords' => 'marketing campaigns'],
        ['section' => 'integration', 'icon' => 'bi-plug', 'name' => 'Integrations', 'hint' => 'External systems and APIs', 'keywords' => 'integrations api erp email'],
        ['section' => 'other', 'icon' => 'bi-three-dots', 'name' => 'Other', 'hint' => 'Additional configuration items', 'keywords' => 'other misc'],
    ],
];
@endphp

<div class="settings-overview">
    <div class="settings-overview-hero mb-4">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <div class="settings-overview-hero-icon mb-3"><i class="bi bi-gear-wide-connected"></i></div>
                <h2 class="settings-overview-hero-title mb-1">CRM Settings</h2>
                <p class="settings-overview-hero-desc mb-0">Configure users, tickets, modules, and system integrations from one place.</p>
            </div>
            <a href="{{ route('settings.crm') }}?section=users" class="btn btn-light btn-sm settings-overview-hero-btn">
                <i class="bi bi-person-plus me-1"></i>Manage users
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="settings-overview-stat">
                <span class="settings-overview-stat-label">Active users</span>
                <span class="settings-overview-stat-value">{{ number_format($stats['users'] ?? 0) }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="settings-overview-stat">
                <span class="settings-overview-stat-label">Roles</span>
                <span class="settings-overview-stat-value">{{ number_format($stats['roles'] ?? 0) }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="settings-overview-stat">
                <span class="settings-overview-stat-label">Departments</span>
                <span class="settings-overview-stat-value">{{ number_format($stats['departments'] ?? 0) }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="settings-overview-stat">
                <span class="settings-overview-stat-label">Modules enabled</span>
                <span class="settings-overview-stat-value">{{ number_format($stats['modules'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    <p class="settings-overview-search-hint text-muted small mb-4">
        <i class="bi bi-search me-1"></i>Use the search box above to filter settings tabs and cards below.
    </p>

    @foreach($sections as $groupName => $cards)
    <section class="settings-overview-group mb-4" data-settings-group>
        <h3 class="settings-overview-group-title">{{ $groupName }}</h3>
        <div class="settings-overview-grid">
            @foreach($cards as $card)
            @php
                $href = isset($card['route'])
                    ? route($card['route'])
                    : route('settings.crm') . '?section=' . $card['section'];
                $searchText = strtolower(($card['name'] ?? '') . ' ' . ($card['hint'] ?? '') . ' ' . ($card['keywords'] ?? '') . ' ' . $groupName);
            @endphp
            <a href="{{ $href }}"
               class="settings-overview-card"
               data-settings-card
               data-search="{{ $searchText }}">
                <span class="settings-overview-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                <span class="settings-overview-name">{{ $card['name'] }}</span>
                <span class="settings-overview-hint">{{ $card['hint'] }}</span>
                <span class="settings-overview-arrow"><i class="bi bi-arrow-right"></i></span>
            </a>
            @endforeach
        </div>
    </section>
    @endforeach

    <div class="settings-overview-empty d-none" id="settingsOverviewEmpty">
        <i class="bi bi-search"></i>
        <p class="mb-0">No settings match your search. Try a different term or clear the search box.</p>
    </div>
</div>

<style>
.settings-overview-hero {
    background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #0E4385) 55%, #2563eb 100%);
    border-radius: 16px;
    color: #fff;
    padding: 1.5rem 1.75rem;
    position: relative;
    overflow: hidden;
}
.settings-overview-hero::after {
    content: '';
    position: absolute;
    right: -2rem;
    top: -2rem;
    width: 10rem;
    height: 10rem;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    pointer-events: none;
}
.settings-overview-hero-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 12px;
    background: rgba(255,255,255,0.15);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}
.settings-overview-hero-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}
.settings-overview-hero-desc {
    font-size: 0.92rem;
    color: rgba(255,255,255,0.88);
    max-width: 36rem;
}
.settings-overview-hero-btn {
    border-radius: 10px;
    font-weight: 600;
    color: var(--agile-primary, #0E4385);
}
.settings-overview-stat {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    border: 1px solid rgba(14, 67, 133, 0.12);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    height: 100%;
    box-shadow: 0 2px 8px rgba(14, 67, 133, 0.04);
}
.settings-overview-stat-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.settings-overview-stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--agile-primary, #0E4385);
    line-height: 1.1;
}
.settings-overview-group-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
    margin: 0 0 0.85rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--agile-border, #e2e8f0);
}
.settings-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}
.settings-overview-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    position: relative;
    padding: 1.25rem 1.35rem 1.35rem;
    background: #fafbfc;
    border: 1px solid var(--agile-border, #e2e8f0);
    border-radius: 14px;
    text-decoration: none;
    color: var(--agile-text, #1e293b);
    transition: border-color 0.2s, background 0.2s, transform 0.2s, box-shadow 0.2s;
    min-height: 100%;
}
.settings-overview-card:hover {
    background: #fff;
    border-color: var(--agile-primary, #0E4385);
    color: var(--agile-primary, #0E4385);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(14, 67, 133, 0.1);
}
.settings-overview-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(14, 67, 133, 0.08);
    border-radius: 12px;
    color: var(--agile-primary, #0E4385);
    font-size: 1.2rem;
    margin-bottom: 0.85rem;
    transition: background 0.2s, color 0.2s;
}
.settings-overview-card:hover .settings-overview-icon {
    background: var(--agile-primary, #0E4385);
    color: #fff;
}
.settings-overview-name {
    font-size: 0.98rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    padding-right: 1.5rem;
}
.settings-overview-hint {
    font-size: 0.82rem;
    color: var(--agile-text-muted, #64748b);
    line-height: 1.45;
    flex: 1;
}
.settings-overview-card:hover .settings-overview-hint {
    color: inherit;
    opacity: 0.85;
}
.settings-overview-arrow {
    position: absolute;
    top: 1.15rem;
    right: 1.1rem;
    color: #cbd5e1;
    font-size: 0.95rem;
    transition: color 0.2s, transform 0.2s;
}
.settings-overview-card:hover .settings-overview-arrow {
    color: var(--agile-primary, #0E4385);
    transform: translateX(3px);
}
.settings-overview-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #64748b;
    background: #f8fafc;
    border-radius: 14px;
    border: 1px dashed #e2e8f0;
}
.settings-overview-empty i {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}
.settings-overview-group.is-hidden { display: none; }
@media (max-width: 575.98px) {
    .settings-overview-grid { grid-template-columns: 1fr; }
    .settings-overview-hero { padding: 1.25rem; }
}
</style>
