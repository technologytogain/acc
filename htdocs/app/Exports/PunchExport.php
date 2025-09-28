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



class PunchExport implements FromCollection, WithHeadings, WithProperties,WithEvents{

    use Exportable;

    public function __construct($data,$extraData){
        $this->data = $data;
        $this->extraData = $extraData;
    }

    public function collection(){

        return collect($this->data);
    }

    public function headings(): array{
        return [
             [
              'ACS MEDICAL COLLEGE',
            ],
            [
                $this->extraData['subtitle2']
            ],
            [
                $this->extraData['subtitle3']
            ],
            [
                'Sr.No.',
                'Date',
                'Device UID',
                'Register No',
                'Student Name',
                'Period I',
                'Period II',
                'Period III',
                'Period IV',
                'Period V',
                'Period VI',
                'Period VII',
                'Period VIII',

            ]
        ];
    }

  /*  public function sheets(): array{
         return mergeCells(['A1:E1']);
    }*/

 /*   public static function afterSheet(AfterSheet $event)
    {
        try {
            $workSheet = $event
                ->sheet
                ->getDelegate()
                ->setMergeCells([
                    'A1:A2',
                    'B1:B2',
                    'C1:D1',
                ])
                ->freezePane('A3');

            $headers = $workSheet->getStyle('A1:D2');

            $headers
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $headers->getFont()->setBold(true);
        } catch (Exception $exception) {
            throw $exception;
        }
    }*/

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
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(25);
                
                /*$spreadsheet->getActiveSheet()->getStyle('D')->getNumberFormat()->applyFromArray(
                    [
                        'formatCode' => NumberFormat::FORMAT_TEXT
                    ]
                );*/

                //$event->sheet->getDelegate()->getStyle($cellRange)->setColumnFormat(['D'=>NumberFormat::FORMAT_TEXT]);

                $event->sheet->getStyle('D')->applyFromArray(['numberFormat' => ['formatCode' => '0']]);

                
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
