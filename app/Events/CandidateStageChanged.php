<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateStageChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Candidate $candidate,
        public readonly string $fromStage,
        public readonly string $toStage,
        public readonly User $movedBy,
        public readonly ?string $rejectionReason = null,
    ) {}
}
