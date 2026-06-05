<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ShiftTemplatesController extends Controller
{
    public function index(): Response
    {
        $companyId = Auth::user()->employee?->company_id;

        $templates = ShiftTemplate::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return Inertia::render('HRSettings/ShiftTemplates', [
            'shiftTemplates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_duration' => ['nullable', 'integer', 'min:0', 'max:180'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i', 'after:break_start_time'],
            'work_days' => ['nullable', 'array'],
            'work_days.*' => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
        ]);

        $companyId = Auth::user()->employee?->company_id;

        ShiftTemplate::create(array_merge($data, [
            'company_id' => $companyId,
            'is_active' => true,
        ]));

        return redirect()->route('hr-settings.shift-templates.index')
            ->with('success', 'Shift template created successfully.');
    }

    public function update(Request $request, ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $this->authorizeTemplate($shiftTemplate);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_duration' => ['nullable', 'integer', 'min:0', 'max:180'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i', 'after:break_start_time'],
            'work_days' => ['nullable', 'array'],
            'work_days.*' => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'is_active' => ['boolean'],
        ]);

        $shiftTemplate->update($data);

        return redirect()->route('hr-settings.shift-templates.index')
            ->with('success', 'Shift template updated successfully.');
    }

    public function destroy(ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $this->authorizeTemplate($shiftTemplate);

        $shiftTemplate->update(['is_active' => false]);

        return redirect()->route('hr-settings.shift-templates.index')
            ->with('success', 'Shift template deactivated.');
    }

    private function authorizeTemplate(ShiftTemplate $shiftTemplate): void
    {
        $companyId = Auth::user()->employee?->company_id;

        if ((int) $shiftTemplate->company_id !== (int) $companyId) {
            abort(403);
        }
    }
}
