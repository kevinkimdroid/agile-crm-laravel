<?php

namespace App\Http\Controllers;

use App\Models\VtigerRole;
use App\Services\TicketSlaImportService;
use App\Services\TicketSlaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketSlaController extends Controller
{
    /** @var TicketSlaService */
    protected $sla;

    public function __construct(TicketSlaService $sla)
    {
        $this->sla = $sla;
    }

    public function index(): View
    {
        $roles = VtigerRole::on('vtiger')->orderBy('rolename')->get();
        $rolesCanClose = $this->sla->getRolesCanClose();
        $departmentTat = $this->sla->getAllDepartmentTat();
        $categoryTat = $this->sla->getAllCategoryTat();

        return view('settings.sections.ticket-sla', [
            'roles' => $roles,
            'rolesCanClose' => $rolesCanClose,
            'departmentTat' => $departmentTat,
            'categoryTat' => $categoryTat,
            'categoriesWithoutTat' => $this->sla->getCategoriesWithoutTat(),
            'departmentsWithoutTat' => $this->sla->getDepartmentsWithoutTat(),
        ]);
    }

    public function updateRoles(Request $request): RedirectResponse
    {
        $roles = $request->input('roles_can_close', []);
        $roles = is_array($roles) ? array_filter($roles) : [];
        $this->sla->setRolesCanClose($roles);
        return $this->backToSla('Close ticket permissions updated.');
    }

    public function updateDepartmentTat(Request $request): RedirectResponse
    {
        $department = $request->input('department');
        $tatHours = (int) $request->input('tat_hours', 24);
        if ($department && $tatHours > 0) {
            $this->sla->setDepartmentTat($department, $tatHours);
        }
        return $this->backToSla('Department TAT updated.', 'departments');
    }

    public function addDepartmentTat(Request $request): RedirectResponse
    {
        $request->validate([
            'department' => 'required|string|max:100',
            'tat_hours' => 'required|integer|min:1|max:720',
        ]);
        $this->sla->setDepartmentTat($request->department, $request->tat_hours);
        return $this->backToSla('Department added.', 'departments');
    }

    public function deleteDepartmentTat(string $department): RedirectResponse
    {
        $this->sla->deleteDepartmentTat($department);
        return $this->backToSla('Department TAT removed.', 'departments');
    }

    public function updateCategoryTat(Request $request): RedirectResponse
    {
        $category = $request->input('category');
        $tatHours = (int) $request->input('tat_hours', 24);
        if ($category && $tatHours > 0) {
            $this->sla->setCategoryTat($category, $tatHours);
        }
        return $this->backToSla('Category TAT updated.', 'categories');
    }

    public function addCategoryTat(Request $request): RedirectResponse
    {
        $request->validate([
            'category' => 'required|string|max:150',
            'tat_hours' => 'required|integer|min:1|max:720',
        ]);
        $this->sla->setCategoryTat($request->category, $request->tat_hours);
        return $this->backToSla('Category added.', 'categories');
    }

    public function deleteCategoryTat(string $category): RedirectResponse
    {
        $this->sla->deleteCategoryTat(urldecode($category));
        return $this->backToSla('Category TAT removed.', 'categories');
    }

    /**
     * Sync category TAT from ticket categories.
     */
    public function syncFromCategories(): RedirectResponse
    {
        $result = $this->sla->syncCategoriesFromTicketCategories();
        $added = $result['added'] ?? [];
        if (count($added) > 0) {
            $msg = 'Added ' . count($added) . ' categor' . (count($added) === 1 ? 'y' : 'ies') . ' with 24h default TAT: ' . implode(', ', $added) . '.';
            return $this->backToSla($msg, 'categories');
        }
        return redirect()->route('settings.crm', ['section' => 'ticket-sla', 'tab' => 'categories'])
            ->with('info', 'All ticket categories already have TAT configured.');
    }

    /**
     * Sync department TAT from org departments list.
     */
    public function syncFromDepartments(): RedirectResponse
    {
        $result = $this->sla->syncDepartmentsFromOrgList();
        $added = $result['added'] ?? [];
        if (count($added) > 0) {
            $msg = 'Added ' . count($added) . ' department(s) with 24h default TAT: ' . implode(', ', $added) . '.';
            return $this->backToSla($msg, 'departments');
        }
        return redirect()->route('settings.crm', ['section' => 'ticket-sla', 'tab' => 'departments'])
            ->with('info', 'All org departments already have TAT configured.');
    }

    /**
     * Import department TATs from Excel. Expects sheets per department with "Defined Time frame" column.
     */
    public function importFromExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $path = $request->file('file')->getRealPath();
        $import = app(TicketSlaImportService::class)->importFromFile($path);

        if (!empty($import['errors'])) {
            return redirect()->route('settings.crm', ['section' => 'ticket-sla', 'tab' => 'departments'])
                ->with('error', 'Import failed: ' . implode(' ', $import['errors']));
        }

        $imported = $import['imported'] ?? [];
        $skipped = $import['skipped'] ?? [];

        if (empty($imported)) {
            return redirect()->route('settings.crm', ['section' => 'ticket-sla', 'tab' => 'departments'])
                ->with('info', 'No departments could be imported. ' . (!empty($skipped) ? 'Skipped: ' . implode(', ', $skipped) : ''));
        }

        $summary = count($imported) . ' department(s) imported: ' . implode(', ', array_map(fn ($r) => $r['department'] . ' (' . $r['tat_hours'] . 'h)', $imported));
        if (!empty($skipped)) {
            $summary .= '. Skipped: ' . implode(', ', $skipped);
        }
        return $this->backToSla($summary, 'departments');
    }

    protected function backToSla(string $message, string $tab = 'categories'): RedirectResponse
    {
        return redirect()->route('settings.crm', ['section' => 'ticket-sla', 'tab' => $tab])->with('success', $message);
    }
}
