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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class StudentMarksExport implements FromCollection, WithHeadings, WithProperties,WithEvents{

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
                                                                'D2:E2',
                                                                'F2:G2',
                                                            ]);
               //$event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(18);
               //$event->sheet->getDelegate()->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('D2:E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('F2:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(15);
                $event->sheet->getStyle('B')->applyFromArray(['numberFormat' => ['formatCode' => '0']]);

            },
        ];

    }

    public function properties(): array
    {
        return [
            'creator'        => 'ACS MEDICAL COLLEGE',
            'lastModifiedBy' => 'ACS MEDICAL COLLEGE',
            'title'          => 'Student Template with Data',
            'description'    => 'ACS MEDICAL COLLEGE - Student Template with Data',
            'subject'        => 'ACS MEDICAL COLLEGE - Student Template with Data',
            'keywords'       => 'student,template,data,export,spreadsheet',
            'category'       => 'student',
            'manager'        => 'ACS MEDICAL COLLEGE',
            'company'        => 'ACS MEDICAL COLLEGE',
        ];
    }

}
