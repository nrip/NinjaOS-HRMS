<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $shifts = Shift::query()
            ->when($request->active_only, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('shifts.form', ['shift' => new Shift()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'start_time'            => ['required', 'date_format:H:i'],
            'end_time'              => ['required', 'date_format:H:i'],
            'is_night_shift'        => ['boolean'],
            'grace_period_minutes'  => ['integer', 'min:0', 'max:60'],
            'is_active'             => ['boolean'],
        ]);

        $validated['location_id'] = $request->user()->location_id;
        $shift = Shift::create($validated);

        return redirect()->route('shifts.show', $shift)->with('success', 'Shift created successfully.');
    }

    public function show(Shift $shift)
    {
        return view('shifts.show', compact('shift'));
    }

    public function edit(Shift $shift)
    {
        return view('shifts.form', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'start_time'            => ['required', 'date_format:H:i'],
            'end_time'              => ['required', 'date_format:H:i'],
            'is_night_shift'        => ['boolean'],
            'grace_period_minutes'  => ['integer', 'min:0', 'max:60'],
            'is_active'             => ['boolean'],
        ]);

        $shift->update($validated);

        return redirect()->route('shifts.show', $shift)->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return redirect()->route('shifts.index')->with('success', 'Shift deleted.');
    }
}
