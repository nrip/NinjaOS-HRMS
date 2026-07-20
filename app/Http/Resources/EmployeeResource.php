<?php
declare(strict_types=1);
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        return [
            'id'              => $this->id,
            'employee_id'     => $this->employee_id,
            'employee_code'   => $this->employee_code,
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'full_name'       => trim("{$this->first_name} {$this->last_name}"),
            'email'           => $this->when(
                $user?->can('view-employee-pii') || $user?->id === $this->user_id,
                $this->email ?? $this->user?->email
            ),
            'phone'           => $this->when(
                $user?->can('view-employee-pii') || $user?->id === $this->user_id,
                $this->phone
            ),
            'date_of_joining' => $this->date_of_joining?->toDateString(),
            'status'          => $this->status,
            'employment_type' => $this->employment_type,
            'location'        => $this->whenLoaded('location', fn () => [
                'id'         => $this->location->id,
                'name'       => $this->location->name,
                'state_code' => $this->location->state_code,
            ]),
            'department'      => $this->whenLoaded('department', fn () => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'designation'     => $this->whenLoaded('designation', fn () => [
                'id'    => $this->designation->id,
                'title' => $this->designation->title ?? $this->designation->name ?? null,
            ]),
            'pan_number'      => $this->when($user?->can('view-employee-pii'), $this->pan_number),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
