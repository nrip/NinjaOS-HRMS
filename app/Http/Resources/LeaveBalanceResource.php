<?php
declare(strict_types=1);
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class LeaveBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'employee_id'     => $this->employee_id,
            'leave_type'      => $this->leave_type,
            'year'            => $this->year,
            'opening_balance' => (float) $this->opening_balance,
            'accrued'         => (float) $this->accrued,
            'availed'         => (float) $this->availed,
            'pending'         => (float) $this->pending,
            'closing_balance' => (float) ($this->opening_balance + $this->accrued - $this->availed - $this->pending),
            'expiry_date'     => $this->expiry_date?->toDateString(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
