<?php namespace Zen\Excel\Classes;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class ExportXLS implements FromCollection, WithHeadings
{
    public $titles, $rows;
    public function __construct($titles, $rows)
    {
        $this->titles = $titles;
        $this->rows = $rows;
    }

    public function headings():array
    {
        return $this->titles;
    }

    public function collection()
    {
        return collect($this->rows);
    }

    static function download($titles, $rows, $filename)
    {
        return Excel::download(new ExportXLS($titles, $rows), $filename);
    }
}