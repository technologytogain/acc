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

class StudentDownload implements FromCollection, WithHeadings, WithProperties,WithEvents{

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
                                                                'A1:N1',
                                                                'A2:N2',
                                                                'A3:N3',
                                                            ]); 
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(18);
                $event->sheet->getDelegate()->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A2:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A3:N3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(25);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(30);

                $event->sheet->getStyle('B')->applyFromArray(['numberFormat' => ['formatCode' => '0']]);

            },
        ];

    }

    public function properties(): array
    {
        return [
            'creator'        => 'ACS Medical College',
            'lastModifiedBy' => 'ACS Medical College',
            'title'          => 'Student Details',
            'description'    => 'ACS Medical College - Student Details',
            'subject'        => 'ACS Medical College - Student Details',
            'keywords'       => 'student,details,export',
            'category'       => 'student',
            'manager'        => 'ACS Medical College',
            'company'        => 'ACS Medical College',
        ];
    }

}
