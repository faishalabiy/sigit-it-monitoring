<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssetsImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'asset_code',
            'name',
            'category',
            'brand',
            'model',
            'serial_number',
            'description',
            'condition',
            'status',
            'asset_pic',
            'location',
            'purchase_date',
            'purchase_price',
        ];
    }

    public function array(): array
    {
        return [
            [
                '002/REKA/IT-E/HRGA/AT/2012',
                'Laptop Lenovo ThinkPad T14',
                'laptop',
                'Lenovo',
                'ThinkPad T14',
                'SN123456789',
                'Laptop operasional divisi IT',
                'good',
                'fixed_asset',
                'Irawan',
                'Ruang IT',
                '2024-01-15',
                '15000000',
            ],
            [
                '003/REKA/IT-E/HRGA/AT/2012',
                'Router MikroTik RB4011',
                'network',
                'MikroTik',
                'RB4011',
                'MT123456',
                'Router jaringan kantor',
                'good',
                'available',
                '',
                'Ruang Server',
                '2024-02-10',
                '3500000',
            ],
        ];
    }
}
