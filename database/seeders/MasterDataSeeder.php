<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Budget;
use App\Models\Depot;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles and Permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $exportPermission = Permission::firstOrCreate(['name' => 'export-raw-data']);
        $manageBudgets = Permission::firstOrCreate(['name' => 'manage-budgets']);
        $manageMasterData = Permission::firstOrCreate(['name' => 'manage-master-data']);
        $viewAllDepots = Permission::firstOrCreate(['name' => 'view-all-depots']);
        $createExpense = Permission::firstOrCreate(['name' => 'create-expense']);
        $viewReports = Permission::firstOrCreate(['name' => 'view-reports']);

        $roleHeadOffice = Role::firstOrCreate(['name' => 'Head Office Admin']);
        $roleHeadOffice->syncPermissions([
            $exportPermission,
            $manageBudgets,
            $manageMasterData,
            $viewAllDepots,
            $createExpense,
            $viewReports,
        ]);

        $roleDepot = Role::firstOrCreate(['name' => 'Depot Operator']);
        $roleDepot->syncPermissions([
            $createExpense,
        ]);

        $roleAuditor = Role::firstOrCreate(['name' => 'Management / Auditor']);
        $roleAuditor->syncPermissions([
            $viewAllDepots,
            $viewReports,
        ]);

        // 2. Depots (21 locations)
        $depotsList = [
            'Jashore', 'Barisal', 'Faridpur', 'Bogra', 'Rajshahi', 'Rangpur',
            'Dinajpur', 'Thakurgaon', 'Dhaka', 'Mymensingh', 'Sylhet', 'Comilla',
            'Chattogram', 'CTG WH', 'CTG Tire WH', 'Benapole', 'Keraniganj',
            'Hemayetpur (Tire)', 'Hemaytepur (CV)', 'Jashore WH', 'Salna WH'
        ];

        $depotModels = [];
        foreach ($depotsList as $name) {
            $code = strtoupper(Str::slug(preg_replace('/[^A-Za-z0-9]/', '', $name)));
            $code = substr($code, 0, 10);
            $depot = Depot::firstOrCreate(
                ['name' => $name],
                [
                    'code' => $code,
                    'contact_email' => strtolower(Str::slug($name)) . '@aci-bd.com',
                    'status' => true,
                ]
            );
            $depotModels[$name] = $depot;
        }

        // 3. Business Units (27 Categories)
        $businessesList = [
            'Tractor', 'Harvester', 'Rental Service', 'New Machineries', 'Diesel Engine',
            'ACI Renewable Energy', 'Power Tiller', 'Spares - PT & DE', 'Marine Engine',
            'Water Pump', 'Smart Tools', 'Motorcycle', 'Commercial Vehicle', 'Mahindra',
            'Spares - Foton', 'Spares - Mahindra', 'Power Solution', 'Medical Equipment',
            'Construction Equipment', 'Tire', 'Industrial Oil', 'Spares - CE',
            'Spares - Yamaha', 'Music', 'Liqui Moly', 'Spares - Agri', 'Institutional Sales'
        ];

        $businessModels = [];
        foreach ($businessesList as $name) {
            $code = 'BU-' . strtoupper(substr(Str::slug(preg_replace('/[^A-Za-z0-9]/', '', $name)), 0, 8));
            $bu = Business::firstOrCreate(
                ['name' => $name],
                [
                    'code' => $code . '-' . rand(10, 99),
                    'status' => true,
                ]
            );
            $businessModels[$name] = $bu;
        }

        // 4. Expense Heads (39 Categories)
        $transportHeads = [
            'Carriage Inward',
            'Carriage outward and Fuel',
            'Courier Cost (Products Delivery)',
            'Courier Cost (Products Receive)',
            'Loading and unloading expenses',
            'Travelling and conveyance',
            'Postage and courier (Documents)',
        ];

        $expenseHeadsList = [
            'Salary and wages', 'Staff welfare', 'Incentive awards', 'Staff Overtime',
            'Training expenses', 'Travelling and conveyance', 'Electricity', 'Water',
            'Fuel - Generator', 'Postage and courier (Documents)', 'Telephone, internet and fax',
            'Printed stationery', 'Office stationery', 'Repairs and maintenance', 'Office rent',
            'Carriage Inward', 'Carriage outward and Fuel', 'Courier Cost (Products Delivery)',
            'Courier Cost (Products Receive)', 'Loading and unloading expenses', 'Local Purchase',
            'Security Salary', 'Fees, rates and taxes', 'Entertainment', 'Legal and professional expenses',
            'Insurance premium', 'Depreciation', 'Bank charge', 'Stock write-off expense',
            'Sundry expense', 'Service charge', 'Registration', 'Reimbursement of expenses',
            'Meeting expenses', 'Donation and gifts', 'Annual conference', 'Recruitment expenses',
            'Warranty expenses', 'IT enable services'
        ];

        $headModels = [];
        $headIndex = 1;
        foreach ($expenseHeadsList as $name) {
            $isTransport = in_array($name, $transportHeads);
            $code = 'EH-' . sprintf('%03d', $headIndex++);
            $eh = ExpenseHead::firstOrCreate(
                ['name' => $name],
                [
                    'code' => $code,
                    'is_transport_related' => $isTransport,
                    'status' => true,
                ]
            );
            $headModels[$name] = $eh;
        }

        // 5. Users (shared demo password: 123456; depot users also get an
        // employee ID so they can log in with either employee ID or email)
        // Head Office Admin
        $hoAdmin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'ACI Head Office Finance Admin',
                'employee_id' => 'EMP-001',
                'password' => Hash::make('123456'),
                'depot_id' => null,
                'role' => 'head_office',
            ]
        );
        $hoAdmin->syncRoles([$roleHeadOffice]);

        // Depot Operators
        $dhakaDepot = $depotModels['Dhaka'];
        $dhakaUser = User::firstOrCreate(
            ['email' => 'dhaka.depot@aci-bd.com'],
            [
                'name' => 'Dhaka Depot In-Charge',
                'employee_id' => 'EMP-101',
                'password' => Hash::make('123456'),
                'depot_id' => $dhakaDepot->id,
                'role' => 'depot_operator',
            ]
        );
        $dhakaUser->syncRoles([$roleDepot]);

        $jashoreDepot = $depotModels['Jashore'];
        $jashoreUser = User::firstOrCreate(
            ['email' => 'jashore.depot@aci-bd.com'],
            [
                'name' => 'Jashore Depot In-Charge',
                'employee_id' => 'EMP-102',
                'password' => Hash::make('123456'),
                'depot_id' => $jashoreDepot->id,
                'role' => 'depot_operator',
            ]
        );
        $jashoreUser->syncRoles([$roleDepot]);

        $benapoleDepot = $depotModels['Benapole'];
        $benapoleUser = User::firstOrCreate(
            ['email' => 'benapole.depot@aci-bd.com'],
            [
                'name' => 'Benapole Depot In-Charge',
                'employee_id' => 'EMP-103',
                'password' => Hash::make('123456'),
                'depot_id' => $benapoleDepot->id,
                'role' => 'depot_operator',
            ]
        );
        $benapoleUser->syncRoles([$roleDepot]);

        // Auditor
        $auditorUser = User::firstOrCreate(
            ['email' => 'auditor@aci-bd.com'],
            [
                'name' => 'Internal Audit & Management',
                'employee_id' => 'EMP-002',
                'password' => Hash::make('123456'),
                'depot_id' => null,
                'role' => 'auditor',
            ]
        );
        $auditorUser->syncRoles([$roleAuditor]);

        // 6. Budgets (Monthly & Annual, always generated for the current year)
        $currentYear = now()->year;
        $months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

        foreach ($depotModels as $depot) {
            // Allocate a monthly budget between 800,000 to 2,500,000 BDT
            $baseDepotBudget = match ($depot->name) {
                'Dhaka' => 3500000,
                'Benapole' => 2800000,
                'Chattogram' => 2400000,
                'Bogra' => 1800000,
                'Jashore' => 1900000,
                default => 1200000,
            };

            foreach ($months as $m) {
                Budget::firstOrCreate(
                    [
                        'depot_id' => $depot->id,
                        'business_id' => null,
                        'expense_head_id' => null,
                        'year' => $currentYear,
                        'month' => $m,
                    ],
                    [
                        'amount' => $baseDepotBudget,
                    ]
                );
            }

            // Annual allocation for the full fiscal year (month = 'ALL')
            Budget::firstOrCreate(
                [
                    'depot_id' => $depot->id,
                    'business_id' => null,
                    'expense_head_id' => null,
                    'year' => $currentYear,
                    'month' => Budget::MONTH_ANNUAL,
                ],
                [
                    'amount' => $baseDepotBudget * 12,
                ]
            );
        }

        // 7. Seed Sample Incurred Expenses (recent 45 days, relative to today)
        $today = Carbon::today();
        $depotsArray = array_values($depotModels);
        $businessArray = array_values($businessModels);
        $headsArray = array_values($headModels);

        $sampleRemarks = [
            'Freezer Delivery', 'Cheque Clearing Charge', 'Depot Generator Diesel',
            'Yamaha Spares Delivery', 'Urgent Stationery Dispatch', 'Quarterly Maintenance',
            'Emergency Field Staff Conveyance', 'Warehouse Security Overhead', 'Tire Transport to Regional Hub'
        ];

        // Seed ~130 sample expenses spread across the last 45 days
        for ($i = 0; $i < 130; $i++) {
            $daysAgo = rand(0, 45);
            $entryDate = $today->copy()->subDays($daysAgo);
            $depot = $depotsArray[array_rand($depotsArray)];
            $business = $businessArray[array_rand($businessArray)];
            $head = $headsArray[array_rand($headsArray)];
            $isTransport = $head->is_transport_related;

            // Generate realistic amounts (e.g. 1,500 to 185,000 BDT)
            $amount = match ($head->name) {
                'Office rent' => rand(150000, 350000),
                'Salary and wages' => rand(250000, 600000),
                'Electricity' => rand(45000, 120000),
                'Carriage outward and Fuel' => rand(35000, 190000),
                'Courier Cost (Products Delivery)' => rand(12000, 65000),
                'Fuel - Generator' => rand(20000, 85000),
                default => rand(2500, 45000),
            };

            Expense::create([
                'date' => $entryDate->toDateString(),
                'month' => strtoupper($entryDate->format('M')),
                'year' => $entryDate->year,
                'depot_id' => $depot->id,
                'business_id' => $business->id,
                'expense_head_id' => $head->id,
                'amount' => $amount,
                'remarks' => $sampleRemarks[array_rand($sampleRemarks)],
                'voucher_path' => null,
                'user_id' => $hoAdmin->id,
                'created_at' => $entryDate->setTime(rand(9, 17), rand(0, 59)),
                'updated_at' => $entryDate->setTime(rand(9, 17), rand(0, 59)),
            ]);
        }
    }
}
