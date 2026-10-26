<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmParameterTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PmParameterController extends Controller
{
    public function index(Request $request)
    {
        $parameters = PmParameterTemplate::withCount('systems')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($query) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where('name','like',$search)->orWhere('code','like',$search);
            }))->orderBy('category')->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.pm.parameters.index', compact('parameters'));
    }

    public function create()
    {
        return view('admin.pm.parameters.form', ['parameter'=>new PmParameterTemplate()]);
    }

    public function edit(PmParameterTemplate $parameter)
    {
        return view('admin.pm.parameters.form', compact('parameter'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        PmParameterTemplate::create($data);
        return redirect()->route('admin.pm.parameters.index')->with('success','Template parameter berhasil ditambahkan.');
    }

    public function update(Request $request, PmParameterTemplate $parameter)
    {
        $data = $this->validated($request, $parameter);
        if (! $data['is_active'] && $parameter->systems()
            ->whereHas('schedules', fn ($q) => $q->where('status','ready'))->exists()) {
            return back()->withInput()->withErrors(['is_active'=>'Parameter dipakai oleh sistem dengan jadwal siap PM. Ubah status jadwal menjadi draft terlebih dahulu.']);
        }
        $parameter->update($data);
        return redirect()->route('admin.pm.parameters.index')->with('success','Template parameter berhasil diperbarui.');
    }

    public function destroy(PmParameterTemplate $parameter)
    {
        if ($parameter->systems()->exists()) {
            return back()->with('error','Parameter masih digunakan oleh sistem. Lepaskan relasinya terlebih dahulu.');
        }
        $parameter->delete();
        return back()->with('success','Template parameter dihapus.');
    }

    private function validated(Request $request, ?PmParameterTemplate $parameter = null): array
    {
        $unique = Rule::unique('pm_parameter_templates','code');
        if ($parameter?->exists) $unique->ignore($parameter->id);
        $data = $request->validate([
            'code'=>['required','string','max:50','regex:/^[A-Za-z0-9_-]+$/',$unique],
            'name'=>['required','string','max:200'],
            'category'=>['required',Rule::in(array_keys(PmParameterTemplate::CATEGORIES))],
            'method'=>['required',Rule::in(array_keys(PmParameterTemplate::METHODS))],
            'unit'=>['nullable','string','max:30'],
            'expected_value'=>['nullable','string','max:255'],
            'warning_min'=>['nullable','numeric','between:-999999999,999999999'],
            'warning_max'=>['nullable','numeric','between:-999999999,999999999'],
            'failure_min'=>['nullable','numeric','between:-999999999,999999999'],
            'failure_max'=>['nullable','numeric','between:-999999999,999999999'],
            'description'=>['nullable','string','max:2000'],
            'is_active'=>['required','boolean'],
        ]);

        if (isset($data['warning_min'],$data['warning_max']) && $data['warning_min'] > $data['warning_max']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['warning_min'=>'Batas minimum warning harus lebih kecil atau sama dengan maksimum.']);
        }
        if (isset($data['failure_min'],$data['failure_max']) && $data['failure_min'] > $data['failure_max']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['failure_min'=>'Batas minimum fail harus lebih kecil atau sama dengan maksimum.']);
        }
        if (isset($data['warning_max'],$data['failure_max']) && $data['failure_max'] <= $data['warning_max']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['failure_max'=>'Batas FAIL maksimum harus lebih besar daripada WARNING maksimum.']);
        }
        if (isset($data['warning_min'],$data['failure_min']) && $data['failure_min'] >= $data['warning_min']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['failure_min'=>'Batas FAIL minimum harus lebih kecil daripada WARNING minimum.']);
        }
        return $data;
    }
}