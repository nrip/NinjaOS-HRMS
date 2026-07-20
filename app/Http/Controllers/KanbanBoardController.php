<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Models\Candidate;
use App\Models\JobRequisition;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
class KanbanBoardController extends Controller
{
    public function board(JobRequisition $requisition): View
    {
        $stages     = Candidate::STAGES;
        $candidates = $requisition->candidates()
            ->withoutGlobalScopes()
            ->with(['stageHistories'])
            ->get()
            ->groupBy('current_stage');
        return view('ats.kanban.board', compact('requisition', 'stages', 'candidates'));
    }
    public function boardData(JobRequisition $requisition): JsonResponse
    {
        $stages     = Candidate::STAGES;
        $candidates = $requisition->candidates()
            ->withoutGlobalScopes()
            ->get()
            ->groupBy('current_stage');
        $board = [];
        foreach ($stages as $stage) {
            $board[$stage] = $candidates->get($stage, collect())->map(fn ($c) => [
                'id'         => $c->id,
                'name'       => $c->full_name,
                'stage'      => $c->current_stage,
                'source'     => $c->source,
                'experience' => $c->parsed_experience,
                'skills'     => $c->parsed_skills ?? [],
            ])->values();
        }
        return response()->json(['board' => $board, 'stages' => $stages]);
    }
}
