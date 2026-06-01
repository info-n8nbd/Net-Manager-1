<?php

namespace App\Http\Controllers;

use App\Models\AutomationSetting;
use App\Models\AutoInactiveSkipDate;
use Illuminate\Http\Request;

class AutomationSettingController extends Controller
{
    private function getCompanySetting(): AutomationSetting
    {
        $companyId = auth()->user()->company_id;

        $setting = AutomationSetting::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();

        if (!$setting) {
            $setting = AutomationSetting::withoutGlobalScopes()->create([
                'company_id'           => $companyId,
                'bill_generate_time'   => '09:00',
                'disconnect_time'      => '23:59',
                'min_due_for_inactive' => 0,
            ]);
        }

        return $setting;
    }

    public function index()
    {
        $setting   = $this->getCompanySetting();
        $skipDates = AutoInactiveSkipDate::orderBy('skip_date')->get();

        return view('billing.automation-settings', compact('setting', 'skipDates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bill_generate_time'   => 'required',
            'disconnect_time'      => 'required',
            'min_due_for_inactive' => 'nullable|numeric|min:0',
        ]);

        $setting = $this->getCompanySetting();

        $setting->bill_generate_time   = $request->bill_generate_time;
        $setting->disconnect_time      = $request->disconnect_time;
        $setting->min_due_for_inactive = $request->min_due_for_inactive ?? 0;
        $setting->save();

        return redirect()
            ->route('billing.automation.settings')
            ->with('success', 'Automation settings updated successfully!');
    }

    public function addSkipDate(Request $request)
    {
        // Support both single date (skip_date) and multiple dates (skip_dates CSV)
        $rawDates = $request->input('skip_dates') ?: $request->input('skip_date');

        if (!$rawDates) {
            return redirect()->route('billing.automation.settings')
                ->with('error', 'Please select at least one skip date.');
        }

        $dates  = array_filter(array_map('trim', explode(',', $rawDates)));
        $reason = $request->input('reason');
        $added  = [];
        $errors = [];

        foreach ($dates as $date) {
            try {
                $parsed = \Carbon\Carbon::parse($date);
                AutoInactiveSkipDate::updateOrCreate(
                    ['skip_date' => $parsed->toDateString()],
                    ['reason' => $reason, 'created_by' => auth()->id()]
                );
                $added[] = $parsed->format('d M Y');
            } catch (\Exception $e) {
                $errors[] = $date;
            }
        }

        if (empty($added)) {
            return redirect()->route('billing.automation.settings')
                ->with('error', 'No valid dates could be added.');
        }

        $msg = count($added) === 1
            ? 'Skip date added: ' . $added[0]
            : count($added) . ' skip dates added: ' . implode(', ', $added);

        return redirect()->route('billing.automation.settings')->with('success', $msg);
    }

    public function updateSkipDate(Request $request, $id)
    {
        $request->validate([
            'skip_date' => 'required|date',
            'reason'    => 'nullable|string|max:255',
        ]);

        $skip = AutoInactiveSkipDate::findOrFail($id);

        if ($skip->skip_date->toDateString() !== $request->skip_date) {
            $exists = AutoInactiveSkipDate::where('skip_date', $request->skip_date)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return redirect()->route('billing.automation.settings')
                    ->with('error', 'Skip date ' . \Carbon\Carbon::parse($request->skip_date)->format('d M Y') . ' already exists.');
            }
        }

        $skip->update([
            'skip_date' => $request->skip_date,
            'reason'    => $request->reason,
        ]);

        return redirect()
            ->route('billing.automation.settings')
            ->with('success', 'Skip date updated: ' . $skip->skip_date->format('d M Y'));
    }

    public function removeSkipDate($id)
    {
        $skip = AutoInactiveSkipDate::findOrFail($id);
        $date = $skip->skip_date->format('d M Y');
        $skip->delete();

        return redirect()
            ->route('billing.automation.settings')
            ->with('success', "Skip date removed: {$date}");
    }
}
