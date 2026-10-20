<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Expense::query()->with(['depot', 'business', 'expenseHead', 'user']);

        if (!empty($this->filters['depot_id'])) {
            $query->where('depot_id', $this->filters['depot_id']);
        }
        if (!empty($this->filters['business_id'])) {
            $query->where('business_id', $this->filters['business_id']);
        }
        if (!empty($this->filters['expense_head_id'])) {
            $query->where('expense_head_id', $this->filters['expense_head_id']);
        }
        if (!empty($this->filters['from_date'])) {
            $query->whereDate('date', '>=', $this->filters['from_date']);
        }
        if (!empty($this->filters['to_date'])) {
            $query->whereDate('date', '<=', $this->filters['to_date']);
        }
        if (!empty($this->filters['year'])) {
            $query->where('year', $this->filters['year']);
        }
        if (!empty($this->filters['from_year']) || !empty($this->filters['to_year'])) {
            $query->yearRange($this->filters['from_year'] ?? null, $this->filters['to_year'] ?? null);
        }
        if (!empty($this->filters['month']) && strtoupper($this->filters['month']) !== 'ALL') {
            $query->where('month', strtoupper($this->filters['month']));
        } elseif (!empty($this->filters['from_month']) || !empty($this->filters['to_month'])) {
            $query->monthRange($this->filters['from_month'] ?? null, $this->filters['to_month'] ?? null);
        }

        return $query->latest('date');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Month',
            'Year',
            'Depot',
            'Business',
            'Expense Head',
            'Amount (BDT)',
            'Transport Mode',
            'Remarks',
            'Entered By',
            'Timestamp',
        ];
    }

    /**
     * @param Expense $expense
     */
    public function map($expense): array
    {
        return [
            $expense->id,
            $expense->date->format('Y-m-d'),
            $expense->month,
            $expense->year,
            $expense->depot?->name ?? 'N/A',
            $expense->business?->name ?? 'N/A',
            $expense->expenseHead?->name ?? 'N/A',
            (float) $expense->amount,
            $expense->transport_mode ?? '—',
            $expense->remarks ?? '—',
            $expense->user?->name ?? 'System',
            $expense->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E293B'],
                ],
            ],
        ];
    }
}
