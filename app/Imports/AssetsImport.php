<?php

namespace App\Imports;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public function model(array $row)
    {
        $assetCode = trim((string) ($row['asset_code'] ?? ''));

        $asset = new Asset([
            'asset_code'     => $assetCode,
            'name'           => $row['name'] ?? null,
            'category'       => $row['category'] ?? null,
            'brand'          => $row['brand'] ?? null,
            'model'          => $row['model'] ?? null,
            'serial_number'  => $row['serial_number'] ?? null,
            'description'    => $row['description'] ?? null,
            'condition'      => $row['condition'] ?? 'good',
            'status'         => $row['status'] ?? 'available',
            'asset_pic'      => $row['asset_pic'] ?? null,
            'location'       => $row['location'] ?? null,
            'purchase_date'  => $row['purchase_date'] ?? null,
            'purchase_price' => $row['purchase_price'] ?? null,
        ]);

        $asset->save();

        $this->saveQrCode($asset);

        return $asset;
    }

    public function rules(): array
    {
        return [
            '*.asset_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('assets', 'asset_code'),
            ],
            '*.name' => [
                'required',
                'string',
                'max:100',
            ],
            '*.category' => [
                'required',
                Rule::in(['laptop', 'monitor', 'pc', 'printer', 'projector', 'camera', 'network', 'other']),
            ],
            '*.brand' => [
                'nullable',
                'string',
                'max:50',
            ],
            '*.model' => [
                'nullable',
                'string',
                'max:50',
            ],
            '*.serial_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('assets', 'serial_number'),
            ],
            '*.description' => [
                'nullable',
                'string',
                'max:500',
            ],
            '*.condition' => [
                'required',
                Rule::in(['good', 'fair', 'poor', 'missing']),
            ],
            '*.status' => [
                'required',
                Rule::in(['available', 'not_available', 'borrowed', 'maintenance', 'fixed_asset', 'retired']),
            ],
            '*.asset_pic' => [
                'nullable',
                // 'required_if:*.status,fixed_asset',
                'string',
                'max:100',
            ],
            '*.location' => [
                'nullable',
                'string',
                'max:100',
            ],
            '*.purchase_date' => [
                'nullable',
                'date',
            ],
            '*.purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    private function saveQrCode(Asset $asset): void
    {
        $url = route('public.landing') . '?' . http_build_query([
            'asset' => $asset->asset_code,
        ]);

        $safeAssetCode = Str::slug($asset->asset_code, '-');
        $filename = 'assets/qrcodes/' . $asset->id . '-' . $safeAssetCode . '.svg';

        $svg = QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($url);

        Storage::disk('public')->put($filename, $svg);

        $asset->forceFill([
            'qr_code' => $filename,
        ])->save();
    }
}
