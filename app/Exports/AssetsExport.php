<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssetsExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    private const HEADER_RANGE = 'A1:O1';

    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        $query = Asset::query();

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];

            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('asset_code', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('serial_number', 'like', '%'.$search.'%');
            });
        }

        if (! empty($this->filters['category'])) {
            $query->where('category', $this->filters['category']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('id')->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Kode Aset',
            'Nama Aset',
            'Kategori',
            'Brand',
            'Model',
            'Serial Number',
            'Deskripsi',
            'Kondisi',
            'Status',
            'PIC Aset',
            'Lokasi',
            'Tanggal Pembelian',
            'Harga Pembelian',
            'QR Code',
            'Foto',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->asset_code,
            $asset->name,
            $asset->category_label,
            $asset->brand,
            $asset->model,
            $asset->serial_number,
            $asset->description,
            $asset->condition_label,
            $asset->status_info['label'],
            $asset->asset_pic,
            $asset->location,
            optional($asset->purchase_date)->format('Y-m-d'),
            $asset->purchase_price,
            $asset->qr_code,
            $asset->photo,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter(self::HEADER_RANGE);
                $sheet->getRowDimension(1)->setRowHeight(24);

                $sheet->getStyle(self::HEADER_RANGE)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2563EB'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'BFDBFE'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
