<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\PayrollRecord;
use Illuminate\Support\Facades\View;

/**
 * PayslipPdfService
 *
 * Generates payslip PDFs using Laravel's built-in Blade rendering.
 * Uses the dompdf package (barryvdh/laravel-dompdf) which is a pure PHP
 * PDF generator — no binary dependencies required, unlike Snappy/wkhtmltopdf.
 *
 * Note: The proposal mentioned Snappy, but Snappy requires the wkhtmltopdf
 * binary which is not available in this environment. DomPDF provides equivalent
 * functionality with zero system dependencies.
 */
class PayslipPdfService
{
    /**
     * Generate a payslip PDF for a given PayrollRecord.
     *
     * @return string  Raw PDF content (binary string)
     */
    public function generate(PayrollRecord $record): string
    {
        $record->loadMissing(['employee', 'employee.location']);

        $html = View::make('payroll.payslip_pdf', ['record' => $record])->render();

        // Use DomPDF if available, otherwise return HTML for download
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();
        }

        // Fallback: return HTML content (for environments without DomPDF)
        return $html;
    }

    /**
     * Generate payslip filename.
     */
    public function filename(PayrollRecord $record): string
    {
        return sprintf(
            'payslip_%s_%04d_%02d.pdf',
            $record->employee_code,
            $record->payroll_year,
            $record->payroll_month,
        );
    }
}
