<?php

namespace App\Services\Employees;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class EmployeeCodeGenerator
{
    public function generate(): string
    {
        return DB::transaction(function (): string {
            // Find latest employee code matching ATP[0-9]+
            $latestCode = Employee::query()
                ->where('employee_code', 'like', 'ATP%')
                ->lockForUpdate()
                ->pluck('employee_code')
                ->map(function ($code) {
                    if (preg_match('/^ATP(\d+)$/i', (string) $code, $matches)) {
                        return (int) $matches[1];
                    }

                    return 0;
                })
                ->max();

            $nextNumber = ($latestCode ? (int) $latestCode : 0) + 1;

            $candidate = sprintf('ATP%03d', $nextNumber);
            while (Employee::query()->where('employee_code', $candidate)->exists()) {
                $nextNumber++;
                $candidate = sprintf('ATP%03d', $nextNumber);
            }

            return $candidate;
        }, 5);
    }
}
