<?php

namespace App\Http\Controllers;

use App\Exports\MasterTemplateExport;
use App\Imports\MasterDataImport;
use App\Models\Business;
use App\Models\Depot;
use App\Models\ExpenseHead;
use App\Models\MissingEntryLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class MasterDataController extends Controller
{
    public function index()
    {
        $depots = Depot::withCount('expenses')->orderBy('name')->get();
        $businesses = Business::withCount('expenses')->orderBy('name')->get();
        $expenseHeads = ExpenseHead::withCount('expenses')->orderBy('name')->get();
        $users = User::with(['depot', 'roles'])->latest()->get();
        $missingLogs = MissingEntryLog::with('depot')->latest('date')->take(20)->get();

        return view('master.index', compact('depots', 'businesses', 'expenseHeads', 'users', 'missingLogs'));
    }

    public function storeDepot(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:depots,name',
            'code' => 'required|string|max:20|unique:depots,code',
            'contact_email' => 'nullable|email|max:150',
        ]);

        Depot::create(array_merge($validated, ['status' => true]));

        return back()->with('success', "Depot '{$validated['name']}' created successfully.");
    }

    public function storeBusiness(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:businesses,name',
            'code' => 'required|string|max:20|unique:businesses,code',
        ]);

        Business::create(array_merge($validated, ['status' => true]));

        return back()->with('success', "Business Unit '{$validated['name']}' created successfully.");
    }

    public function storeExpenseHead(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:expense_heads,name',
            'code' => 'required|string|max:20|unique:expense_heads,code',
            'is_transport_related' => 'nullable|boolean',
        ]);

        ExpenseHead::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'is_transport_related' => (bool) ($request->is_transport_related ?? false),
            'status' => true,
        ]);

        return back()->with('success', "Expense Head '{$validated['name']}' created successfully.");
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'employee_id' => ['required', 'string', 'max:50', 'unique:users,employee_id'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            // Only depot operator accounts may be provisioned from this module;
            // higher-privilege roles (head_office / auditor) are managed separately.
            'role' => ['nullable', 'in:depot_operator'],
            'depot_id' => ['required', 'exists:depots,id'],
        ], [
            'employee_id.required' => 'Employee ID is required when assigning a depot/showroom operator.',
            'employee_id.unique' => 'This Employee ID is already assigned to another user.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'employee_id' => strtoupper(trim($validated['employee_id'])),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'depot_operator',
            'depot_id' => $validated['depot_id'],
        ]);

        $user->syncRoles(['Depot Operator']);

        return back()->with('success', "Depot Operator '{$user->name}' ({$user->employee_id}) created successfully.");
    }

    // =========================================================
    // Module: Bulk Upload (Excel / CSV) for all master entities
    // =========================================================

    /**
     * Column definitions per bulk-uploadable entity. Column order in the
     * spreadsheet is fixed; the first spreadsheet row is always the header.
     */
    private const BULK_TYPES = [
        'depots' => ['label' => 'Depots', 'columns' => ['name', 'code', 'contact_email', 'status']],
        'businesses' => ['label' => 'Business Units', 'columns' => ['name', 'code', 'status']],
        'expense-heads' => ['label' => 'Expense Heads', 'columns' => ['name', 'code', 'is_transport_related', 'status']],
        // Only depot operator accounts can be provisioned via bulk upload;
        // every imported user is created as a Depot Operator tied to depot_code.
        'users' => ['label' => 'Users', 'columns' => ['name', 'employee_id', 'email', 'password', 'depot_code']],
    ];

    private const TEMPLATE_SAMPLES = [
        'depots' => [
            ['Rangamati Hub', 'RANGAMATI', 'rangamati@aci-bd.com', 'active'],
            ['Cox Bazar WH', 'COXBWH', 'coxbazar@aci-bd.com', 'active'],
        ],
        'businesses' => [
            ['Heavy Excavator', 'BU-HEAVY01', 'active'],
            ['Outboard Motor', 'BU-OBM01', 'active'],
        ],
        'expense-heads' => [
            ['Forklift Maintenance', 'EH-040', 'no', 'active'],
            ['Container Transport', 'EH-041', 'yes', 'active'],
        ],
        'users' => [
            ['Md. Rahim Uddin', 'EMP-201', 'rahim@aci-bd.com', '123456', 'JASHORE'],
            ['Nusrat Jahan', 'EMP-202', 'nusrat@aci-bd.com', '123456', 'BENAPOLE'],
        ],
    ];

    public function downloadTemplate(string $type)
    {
        $config = self::BULK_TYPES[$type] ?? abort(404, 'Unknown bulk import template.');
        $rows = self::TEMPLATE_SAMPLES[$type];

        return Excel::download(
            new MasterTemplateExport($config['columns'], $rows),
            "aci_{$type}_bulk_template.xlsx"
        );
    }

    public function bulkImport(Request $request, string $type)
    {
        $config = self::BULK_TYPES[$type] ?? abort(404, 'Unknown bulk import type.');

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,xlsx,xls,txt'],
        ], [
            'file.mimes' => 'The upload must be an Excel (.xlsx/.xls) or CSV file.',
            'file.max' => 'The upload file must not exceed 10 MB.',
        ]);

        $sheets = Excel::toArray(new MasterDataImport, $request->file('file'));
        $rows = collect($sheets[0] ?? [])->values();

        // First row is the header row (matches the downloadable template).
        $rows->pull(0);

        $report = [
            'type' => $config['label'],
            'inserted' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $rowNumber = 1; // header occupies spreadsheet row 1
        foreach ($rows as $row) {
            $rowNumber++;

            $cells = array_slice($row, 0, count($config['columns']));
            $cells = array_pad($cells, count($config['columns']), null);
            $cells = array_map(fn ($cell) => $cell === null ? null : trim((string) $cell), $cells);

            // Skip fully blank rows (common with spreadsheet exports)
            if (blank(array_filter($cells, fn ($cell) => $cell !== null && $cell !== ''))) {
                continue;
            }
            if ($rowNumber - 2 >= 5000) {
                $report['errors'][] = "Import stopped at 5,000 rows maximum.";
                break;
            }

            try {
                $outcome = $this->importRow($type, $cells);
                $report[$outcome]++;
            } catch (\RuntimeException $e) {
                $report['failed']++;
                $report['errors'][] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        $report['message'] = sprintf(
            '%s bulk import finished — %d imported, %d skipped (already exist), %d failed. Total rows processed: %d.',
            $config['label'],
            $report['inserted'],
            $report['skipped'],
            $report['failed'],
            $report['inserted'] + $report['skipped'] + $report['failed'],
        );
        $report['errors'] = array_slice($report['errors'], 0, 20);

        return redirect()->route('master.index')->with('bulk_report', $report);
    }

    /**
     * Import a single normalized row. Returns 'inserted' or 'skipped',
     * or throws with a human-readable reason on validation failure.
     */
    private function importRow(string $type, array $cells): string
    {
        return match ($type) {
            'depots' => $this->importDepotRow(...$cells),
            'businesses' => $this->importBusinessRow(...$cells),
            'expense-heads' => $this->importExpenseHeadRow(...$cells),
            'users' => $this->importUserRow(...$cells),
        };
    }

    private function importDepotRow(?string $name, ?string $code, ?string $contactEmail, ?string $status): string
    {
        $data = ['name' => $name, 'code' => $code, 'contact_email' => $contactEmail];
        $this->validateRow($data, [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:150'],
        ]);

        if (Depot::where('name', $data['name'])->orWhere('code', $data['code'])->exists()) {
            return 'skipped';
        }

        Depot::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'contact_email' => $data['contact_email'] ?: null,
            'status' => $this->parseStatus($status),
        ]);

        return 'inserted';
    }

    private function importBusinessRow(?string $name, ?string $code, ?string $status): string
    {
        $data = ['name' => $name, 'code' => $code];
        $this->validateRow($data, [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        if (Business::where('name', $data['name'])->orWhere('code', $data['code'])->exists()) {
            return 'skipped';
        }

        Business::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'status' => $this->parseStatus($status),
        ]);

        return 'inserted';
    }

    private function importExpenseHeadRow(?string $name, ?string $code, ?string $isTransport, ?string $status): string
    {
        $data = ['name' => $name, 'code' => $code];
        $this->validateRow($data, [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        if (ExpenseHead::where('name', $data['name'])->orWhere('code', $data['code'])->exists()) {
            return 'skipped';
        }

        ExpenseHead::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'is_transport_related' => $this->parseBool($isTransport, false),
            'status' => $this->parseStatus($status),
        ]);

        return 'inserted';
    }

    private function importUserRow(?string $name, ?string $employeeId, ?string $email, ?string $password, ?string $depotCode): string
    {
        $data = [
            'name' => $name,
            'employee_id' => $employeeId !== null ? strtoupper(trim($employeeId)) : null,
            'email' => $email,
            'password' => $password,
        ];
        $this->validateRow($data, [
            'name' => ['required', 'string', 'max:100'],
            'employee_id' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'employee_id.required' => 'employee_id is required (every operator account needs an Employee ID).',
        ]);

        if (User::where('email', $data['email'])->exists()) {
            return 'skipped';
        }
        if (User::where('employee_id', $data['employee_id'])->exists()) {
            throw new \RuntimeException("Employee ID '{$data['employee_id']}' is already registered.");
        }

        // Bulk upload provisions Depot Operator accounts only.
        if (blank($depotCode)) {
            throw new \RuntimeException('depot_code is required (all bulk users are created as Depot Operators).');
        }
        $depot = Depot::where('code', $depotCode)->first() ?? Depot::where('name', $depotCode)->first();
        if (!$depot) {
            throw new \RuntimeException("Depot '{$depotCode}' not found by code or name.");
        }

        $user = User::create([
            'name' => $data['name'],
            'employee_id' => $data['employee_id'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'depot_operator',
            'depot_id' => $depot->id,
        ]);
        $user->syncRoles(['Depot Operator']);

        return 'inserted';
    }

    private function validateRow(array $data, array $rules, array $messages = []): void
    {
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new \RuntimeException(implode(' ', $validator->errors()->all()));
        }
    }

    private function parseStatus(?string $value): bool
    {
        return $this->parseBool($value, true);
    }

    private function parseBool(?string $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'yes', 'y', 'true', 'active', 'x'], true);
    }
}
