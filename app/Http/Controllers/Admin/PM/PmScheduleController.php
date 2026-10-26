<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PmScheduleController extends Controller
{
    public function index()
    {
        $schedules = PmSchedule::with(['assignee','runs'=>fn ($q)=>$q->latest('id')->limit(1)])->withCount('systems')
            ->latest()->paginate(15);
        return view('admin.pm.schedules.index', compact('schedules'));
    }

    public function create()
    {
        return $this->form(new PmSchedule());
    }

    public function edit(PmSchedule $schedule)
    {
        $schedule->load('systems');
        return $this->form($schedule);
    }

    private function form(PmSchedule $schedule)
    {
        return view('admin.pm.schedules.form', [
            'schedule'=>$schedule,
            'systems'=>PmSystem::withCount(['parameters'=>fn ($q) => $q->where('is_active',true)])
                ->where('status','active')->orderBy('name')->get(),
            'staff'=>User::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $schedule = PmSchedule::create(collect($data)->except('systems')->all());
            $schedule->systems()->sync($data['systems'] ?? []);
        });
        return redirect()->route('admin.pm.schedules.index')->with('success','Jadwal PM berhasil ditambahkan.');
    }

    public function update(Request $request, PmSchedule $schedule)
    {
        $data = $this->validated($request, $schedule);
        DB::transaction(function () use ($data, $schedule) {
            $schedule->update(collect($data)->except('systems')->all());
            $schedule->systems()->sync($data['systems'] ?? []);
        });
        return redirect()->route('admin.pm.schedules.index')->with('success','Jadwal PM berhasil diperbarui.');
    }

    public function destroy(PmSchedule $schedule)
    {
        if ($schedule->runs()->exists()) {
            return back()->with('error','Jadwal memiliki histori pelaksanaan. Ubah status menjadi Draft daripada menghapusnya.');
        }
        $schedule->delete();
        return back()->with('success','Jadwal PM berhasil dihapus.');
    }

    private function validated(Request $request, ?PmSchedule $schedule = null): array
    {
        $unique = Rule::unique('pm_schedules','code');
        if ($schedule?->exists) $unique->ignore($schedule->id);
        $data = $request->validate([
            'code'=>['required','string','max:50','regex:/^[A-Za-z0-9_-]+$/',$unique],
            'name'=>['required','string','max:200'],
            'frequency'=>['required',Rule::in(array_keys(PmSchedule::FREQUENCIES))],
            'run_time'=>['required','date_format:H:i'],
            'day_of_week'=>['nullable','integer','between:0,6','required_if:frequency,weekly'],
            'day_of_month'=>['nullable','integer','between:1,28','required_if:frequency,monthly'],
            'timezone'=>['required',Rule::in(['Asia/Jakarta','Asia/Makassar','Asia/Jayapura','UTC'])],
            'starts_on'=>['required','date'],
            'status'=>['required',Rule::in(['draft','ready'])],
            'assigned_to'=>['nullable','integer','exists:users,id'],
            'description'=>['nullable','string','max:2000'],
            'systems'=>['nullable','array'],
            'systems.*'=>['integer','distinct',Rule::exists('pm_systems','id')->where('status','active')],
        ]);

        if ($data['frequency'] !== 'weekly') $data['day_of_week'] = null;
        if ($data['frequency'] !== 'monthly') $data['day_of_month'] = null;

        if ($data['status'] === 'ready') {
            $ids = $data['systems'] ?? [];
            if ($ids === []) throw ValidationException::withMessages(['systems'=>'Jadwal siap PM wajib memiliki minimal satu sistem target.']);
            $systemsWithoutParameters = PmSystem::whereIn('id',$ids)
                ->whereDoesntHave('parameters',fn ($q) => $q->where('is_active',true))
                ->pluck('name');
            if ($systemsWithoutParameters->isNotEmpty()) {
                throw ValidationException::withMessages(['systems'=>'Target belum memiliki parameter aktif: '.$systemsWithoutParameters->implode(', ')]);
            }

            // A ready plan must contain actionable evaluation rules for every automatic check.
            $missingRules = PmSystem::with(['parameters'=>fn ($q) => $q->where('is_active',true)])
                ->whereIn('id',$ids)->get()->flatMap(function ($system) {
                    return $system->parameters->filter(fn ($parameter) =>
                        $parameter->method !== 'manual_check'
                        && blank($parameter->expected_value)
                        && $parameter->warning_min === null
                        && $parameter->warning_max === null
                        && $parameter->failure_min === null
                        && $parameter->failure_max === null
                    )->map(fn ($parameter) => $system->name.' / '.$parameter->name);
                });
            if ($missingRules->isNotEmpty()) {
                throw ValidationException::withMessages(['systems'=>
                    'Parameter otomatis belum memiliki nilai acuan atau threshold: '.$missingRules->implode(', ')
                ]);
            }
        }

        return $data;
    }
}