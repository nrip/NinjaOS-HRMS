<?php
declare(strict_types=1);
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'employee_code'   => $this->employee_code,
            'date'            => $this->date?->toDateString(),
            'punch_in'        => $this->punch_in?->toTimeString(),
            'punch_out'       => $this->punch_out?->toTimeString(),
            'hours_worked'    => (float) $this->hours_worked,
            'overtime_hours'  => (float) $this->overtime_hours,
            'status'          => $this->status,
            'punch_source'    => $this->punch_source,
            'is_late'         => (bool) $this->is_late,
            'is_early_exit'   => (bool) $this->is_early_exit,
            'regularization_status' => $this->regularization_status,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
