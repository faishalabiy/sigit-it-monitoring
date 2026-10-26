<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmParameterTemplate;
use App\Models\PmSystem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PmSystemController extends Controller
{
    public function index(Request $request)
    {
        $systems = PmSystem::with('owner')->withCount(['parameters','schedules'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($query) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where('name','like',$search)->orWhere('code','like',$search)
                    ->orWhere('host','like',$search);
            }))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.pm.systems.index', compact('systems'));
    }

    public function create()
    {
        return $this->form(new PmSystem());
    }

    public function edit(PmSystem $system)
    {
        $system->load('parameters');
        return $this->form($system);
    }

    private function form(PmSystem $system)
    {
        return view('admin.pm.systems.form', [
            'system'=>$system,
            'parameters'=>PmParameterTemplate::where('is_active',true)->orderBy('category')->orderBy('name')->get(),
            'owners'=>User::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $system = PmSystem::create(collect($data)->except('parameters')->all());
            $system->parameters()->sync($this->parameterPivot($data['parameters'] ?? []));
        });
        return redirect()->route('admin.pm.systems.index')->with('success','Sistem berhasil ditambahkan.');
    }

    public function update(Request $request, PmSystem $system)
    {
        $data = $this->validated($request, $system);
        $newIds = $data['parameters'] ?? [];
        if (($data['status'] !== 'active' || $newIds === [])
            && $system->schedules()->where('status','ready')->exists()) {
            return back()->withInput()->withErrors(['parameters'=>'Sistem pada jadwal siap PM tidak boleh dinonaktifkan atau dikosongkan parameternya.']);
        }

        DB::transaction(function () use ($data, $system, $newIds) {
            $system->update(collect($data)->except('parameters')->all());
            $system->parameters()->sync($this->parameterPivot($newIds));
        });
        return redirect()->route('admin.pm.systems.index')->with('success','Sistem berhasil diperbarui.');
    }

    public function destroy(PmSystem $system)
    {
        if ($system->schedules()->exists()) {
            return back()->with('error','Sistem masih menjadi target jadwal PM. Lepaskan dari jadwal terlebih dahulu.');
        }
        $system->delete();
        return back()->with('success','Sistem berhasil dihapus.');
    }

    private function validated(Request $request, ?PmSystem $system = null): array
    {
        $codeRule = Rule::unique('pm_systems','code');
        if ($system?->exists) $codeRule->ignore($system->id);
        return $request->validate([
            'code'=>['required','string','max:50','regex:/^[A-Za-z0-9_-]+$/',$codeRule],
            'name'=>['required','string','max:200'],
            'type'=>['required',Rule::in(array_keys(PmSystem::TYPES))],
            'environment'=>['required',Rule::in(['production','staging','development','other'])],
            'criticality'=>['required',Rule::in(['low','medium','high','critical'])],
            'status'=>['required',Rule::in(['active','inactive'])],
            'target_url'=>['nullable','url:http,https','max:512'],
            'host'=>['nullable','string','max:255'],
            'owner_id'=>['nullable','integer','exists:users,id'],
            'description'=>['nullable','string','max:2000'],
            'parameters'=>['nullable','array'],
            'parameters.*'=>['integer','distinct',Rule::exists('pm_parameter_templates','id')->where('is_active',true)],
        ]);
    }

    private function parameterPivot(array $ids): array
    {
        return collect($ids)->mapWithKeys(fn ($id) => [(int) $id=>['is_required'=>true]])->all();
    }
}