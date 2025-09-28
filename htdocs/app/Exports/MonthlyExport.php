<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;



class MonthlyExport implements FromCollection, WithHeadings, WithProperties,WithEvents{

    use Exportable;

    public function __construct($data,$extraData){
        $this->data = $data;
        $this->extraData = $extraData;
    }

    public function collection(){

        return collect($this->data);
    }

    public function headings(): array{

        return $this->extraData['headings'];
    }

    public function registerEvents(): array{

        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = 'A1:P1';
                $event->sheet->getDelegate()->setMergeCells([
                                                                'A1:L1',
                                                                'A2:L2',
                                                                'A3:L3',
                                                            ]);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(18);
                $event->sheet->getDelegate()->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A2:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A3:L3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(25);
                
                $event->sheet->getStyle('C')->applyFromArray(['numberFormat' => ['formatCode' => '0']]);
                $event->sheet->getStyle('AJ')->applyFromArray(['numberFormat' => ['formatCode' => '0']]);
                //$event->sheet->getStyle('AK')->applyFromArray(['numberFormat' => ['formatCode' => '0']]);
                $event->sheet->getStyle('AL')->applyFromArray(['numberFormat' => ['formatCode' => '0']]);

                
            },
        ];

    }

    public function properties(): array
    {
        return [
            'creator'        => 'ACS Medical College',
            'lastModifiedBy' => 'ACS Medical College',
            'title'          => 'Attendance Report',
            'description'    => 'ACS Medical College - Attendance Report',
            'subject'        => 'ACS Medical College - Attendance Report',
            'keywords'       => 'attendance,export,spreadsheet',
            'category'       => 'attendance',
            'manager'        => 'ACS Medical College',
            'company'        => 'ACS Medical College',
        ];
    }

    

}
