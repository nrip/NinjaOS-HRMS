<?php
declare(strict_types=1);
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'employee_code'   => $this->employee_code,
            'payroll_month'   => $this->payroll_month,
            'payroll_year'    => $this->payroll_year,
            'status'          => $this->status,
            'gross_salary'    => (float) $this->gross_salary,
            'net_pay'         => (float) $this->net_pay,
            'employee_pf'     => (float) $this->employee_pf,
            'employer_pf'     => (float) $this->employer_pf,
            'employee_esi'    => (float) $this->employee_esi,
            'employer_esi'    => (float) $this->employer_esi,
            'professional_tax' => (float) $this->professional_tax,
            'tds'             => (float) $this->tds,
            'lwp_days'        => (float) $this->lwp_days,
            // Signed URL for secure PDF download — expires in 60 minutes.
            // The mobile app uses this URL to fetch/render the PDF without
            // embedding base64 data in the response payload.
            'pdf_url'         => URL::signedRoute(
                'api.payroll.pdf',
                ['record' => $this->id],
                now()->addMinutes(60)
            ),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
