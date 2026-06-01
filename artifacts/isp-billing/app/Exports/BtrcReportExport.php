<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class BtrcReportExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected array $data;
    protected string $reportType;
    protected string $title;
    protected array $companyInfo;
    protected array $columnConfig;

    public function __construct(array $data, string $reportType, string $title, array $companyInfo = [])
    {
        $this->data       = $data;
        $this->reportType = $reportType;
        $this->title      = $title;
        $this->companyInfo = $companyInfo;
        $this->columnConfig = $this->getColumnConfig();
    }

    /* ------------------------------------------------------------------ */
    /*  Data rows                                                           */
    /* ------------------------------------------------------------------ */
    public function array(): array
    {
        $rows = [];
        $sl   = 1;
        foreach ($this->data as $row) {
            $mappedRow = [];
            if ($this->reportType !== 'btrc_official') {
                $mappedRow[] = $sl++;
            }
            foreach ($this->columnConfig as $key => $config) {
                $mappedRow[] = $row[$key] ?? '';
            }
            $rows[] = $mappedRow;
        }
        return $rows;
    }

    /* ------------------------------------------------------------------ */
    /*  Column header row                                                   */
    /* ------------------------------------------------------------------ */
    public function headings(): array
    {
        $headings = $this->reportType === 'btrc_official' ? [] : ['SL No.'];
        foreach ($this->columnConfig as $config) {
            $headings[] = $config['label'];
        }
        return $headings;
    }

    /* ------------------------------------------------------------------ */
    /*  Sheet tab name                                                      */
    /* ------------------------------------------------------------------ */
    public function title(): string
    {
        $shortNames = [
            'subscriber_list' => 'Subscriber List',
            'new_connections' => 'New Connections',
            'disconnections'  => 'Disconnections',
            'ip_assignment'   => 'IP Assignment',
            'btrc_official'   => 'BTRC Clients List',
        ];
        return $shortNames[$this->reportType] ?? substr($this->reportType, 0, 31);
    }

    /* ------------------------------------------------------------------ */
    /*  Column widths                                                       */
    /* ------------------------------------------------------------------ */
    public function columnWidths(): array
    {
        if ($this->reportType === 'btrc_official') {
            // No SL column — A is the first data column
            $widths = [];
            $col = 'A';
            foreach ($this->columnConfig as $config) {
                $widths[$col] = $config['width'] ?? 15;
                $col++;
            }
            return $widths;
        }

        // Other reports have SL in column A
        $widths = ['A' => 8];
        $col = 'B';
        foreach ($this->columnConfig as $config) {
            $widths[$col] = $config['width'] ?? 15;
            $col++;
        }
        return $widths;
    }

    /* ------------------------------------------------------------------ */
    /*  Styles (applied before AfterSheet events)                          */
    /* ------------------------------------------------------------------ */
    public function styles(Worksheet $sheet): array
    {
        if ($this->reportType === 'btrc_official') {
            // Row 1 = header row (no company rows inserted)
            return [
                1 => [
                    'font'      => ['bold' => true, 'size' => 10],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a3a5c']],
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical'   => Alignment::VERTICAL_CENTER,
                                    'wrapText'   => true],
                ],
            ];
        }

        // Standard reports: company name=1, address=2, title=3, blank=4, header=5
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font'      => ['size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font'      => ['size' => 10, 'italic' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            5 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a3a5c']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                                'wrapText'   => true],
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  After-sheet events                                                  */
    /* ------------------------------------------------------------------ */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $colCount = count($this->columnConfig);

                // Last column letter
                // For btrc_official: no SL column, so last col = colCount columns (A=0 → chr(65+colCount-1))
                // For others: SL + colCount columns
                if ($this->reportType === 'btrc_official') {
                    $lastColIndex = $colCount - 1;          // 0-based
                } else {
                    $lastColIndex = $colCount;               // SL(0) + data cols
                }

                // Build column letter (supports beyond Z)
                $lastCol = $this->colLetter($lastColIndex);

                if ($this->reportType === 'btrc_official') {
                    /* ---- BTRC Official: plain format, no company header ---- */
                    $lastRow   = count($this->data) + 1;    // row 1 = header
                    $dataRange = "A1:{$lastCol}{$lastRow}";

                    // Borders on all cells
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    // Alternating row fill for data rows
                    for ($row = 2; $row <= $lastRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F8FF']],
                            ]);
                        }
                    }

                    // Row height for header
                    $sheet->getRowDimension(1)->setRowHeight(28);

                    // Force client_mobile column (M) to TEXT so Excel won't show 8.80E+12
                    // Column M = 13th column (0-indexed: 12) for btrc_official
                    $mobileColLetter = $this->colLetter(12); // 'M'
                    $sheet->getStyle("{$mobileColLetter}1:{$mobileColLetter}{$lastRow}")
                          ->getNumberFormat()
                          ->setFormatCode('@');
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $cell = $sheet->getCell("{$mobileColLetter}{$row}");
                        $val  = $cell->getValue();
                        if ($val !== null && $val !== '') {
                            $sheet->setCellValueExplicit(
                                "{$mobileColLetter}{$row}",
                                (string) $val,
                                DataType::TYPE_STRING
                            );
                        }
                    }

                } else {
                    /* ---- Standard reports: insert 4 company header rows ---- */
                    $companyName    = $this->companyInfo['name']    ?? 'SOFTYNEX';
                    $companyAddress = $this->companyInfo['address'] ?? 'Dhaka, Bangladesh';
                    $licenseNo      = $this->companyInfo['license'] ?? '';

                    $sheet->insertNewRowBefore(1, 4);
                    $lastRow = count($this->data) + 5;

                    $sheet->mergeCells("A1:{$lastCol}1");
                    $sheet->setCellValue('A1', $companyName);
                    $sheet->mergeCells("A2:{$lastCol}2");
                    $sheet->setCellValue('A2', $companyAddress . ($licenseNo ? " | BTRC License: {$licenseNo}" : ''));
                    $sheet->mergeCells("A3:{$lastCol}3");
                    $sheet->setCellValue('A3', $this->title . ' | Generated: ' . date('d-m-Y h:i A'));
                    $sheet->mergeCells("A4:{$lastCol}4");

                    $dataRange = "A5:{$lastCol}{$lastRow}";
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    for ($row = 6; $row <= $lastRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F8FF']],
                            ]);
                        }
                    }

                    $sheet->getStyle("A5:A{$lastRow}")->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getHeaderFooter()->setOddFooter('&L&D &T&CBTRC Compliance Report&RPage &P of &N');
                }

                // Page setup
                $sheet->getPageSetup()
                      ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                      ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                      ->setFitToWidth(1)
                      ->setFitToHeight(0);
            },
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Helper: convert 0-based column index to letter(s)                 */
    /* ------------------------------------------------------------------ */
    private function colLetter(int $index): string
    {
        $letter = '';
        $index++;   // 1-based
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = intdiv($index, 26);
        }
        return $letter;
    }

    /* ------------------------------------------------------------------ */
    /*  Column configuration                                               */
    /* ------------------------------------------------------------------ */
    protected function getColumnConfig(): array
    {
        return match($this->reportType) {
            'subscriber_list' => [
                'client_id'       => ['label' => 'Client ID',            'width' => 12],
                'name'            => ['label' => 'Subscriber Name',       'width' => 22],
                'father_name'     => ['label' => 'Father/Husband Name',   'width' => 22],
                'nid'             => ['label' => 'NID Number',            'width' => 18],
                'phone'           => ['label' => 'Mobile Number',         'width' => 15],
                'email'           => ['label' => 'Email',                 'width' => 22],
                'address'         => ['label' => 'Full Address',          'width' => 30],
                'zone'            => ['label' => 'Zone/Area',             'width' => 15],
                'connection_type' => ['label' => 'Connection Type',       'width' => 15],
                'username'        => ['label' => 'PPPoE Username',        'width' => 18],
                'ip_address'      => ['label' => 'IP Address',            'width' => 15],
                'mac_address'     => ['label' => 'MAC Address',           'width' => 18],
                'package'         => ['label' => 'Package Name',          'width' => 15],
                'bandwidth'       => ['label' => 'Bandwidth (DL/UL)',     'width' => 18],
                'monthly_fee'     => ['label' => 'Monthly Fee (BDT)',     'width' => 15],
                'connection_date' => ['label' => 'Connection Date',       'width' => 15],
                'status'          => ['label' => 'Status',                'width' => 12],
            ],
            'new_connections' => [
                'client_id'       => ['label' => 'Client ID',            'width' => 12],
                'name'            => ['label' => 'Subscriber Name',       'width' => 22],
                'nid'             => ['label' => 'NID Number',            'width' => 18],
                'phone'           => ['label' => 'Mobile Number',         'width' => 15],
                'address'         => ['label' => 'Full Address',          'width' => 30],
                'zone'            => ['label' => 'Zone/Area',             'width' => 15],
                'connection_type' => ['label' => 'Connection Type',       'width' => 15],
                'package'         => ['label' => 'Package Name',          'width' => 15],
                'bandwidth'       => ['label' => 'Bandwidth (DL/UL)',     'width' => 18],
                'connection_date' => ['label' => 'Connection Date',       'width' => 15],
            ],
            'disconnections' => [
                'client_id'          => ['label' => 'Client ID',            'width' => 12],
                'name'               => ['label' => 'Subscriber Name',       'width' => 22],
                'nid'                => ['label' => 'NID Number',            'width' => 18],
                'phone'              => ['label' => 'Mobile Number',         'width' => 15],
                'address'            => ['label' => 'Full Address',          'width' => 30],
                'zone'               => ['label' => 'Zone/Area',             'width' => 15],
                'connection_date'    => ['label' => 'Connection Date',       'width' => 15],
                'disconnection_date' => ['label' => 'Disconnection Date',    'width' => 18],
                'reason'             => ['label' => 'Reason',                'width' => 20],
            ],
            'ip_assignment' => [
                'client_id'       => ['label' => 'Client ID',            'width' => 12],
                'name'            => ['label' => 'Subscriber Name',       'width' => 22],
                'username'        => ['label' => 'PPPoE Username',        'width' => 18],
                'ip_address'      => ['label' => 'IP Address',            'width' => 15],
                'mac_address'     => ['label' => 'MAC Address',           'width' => 18],
                'connection_type' => ['label' => 'Connection Type',       'width' => 15],
                'zone'            => ['label' => 'Zone/Area',             'width' => 15],
                'assignment_date' => ['label' => 'Assignment Date',       'width' => 15],
                'status'          => ['label' => 'Status',                'width' => 12],
            ],
            'btrc_official' => [
                'client_type'                     => ['label' => 'client_type',                     'width' => 12],
                'connection_type'                 => ['label' => 'connection_type',                 'width' => 14],
                'client_name'                     => ['label' => 'client_name',                     'width' => 25],
                'bandwidth_distribution_point'    => ['label' => 'bandwidth_distribution_point',    'width' => 30],
                'connectivity_type'               => ['label' => 'connectivity_type',               'width' => 17],
                'activation_date'                 => ['label' => 'activation_date',                 'width' => 15],
                'bandwidth_allocation'            => ['label' => 'bandwidth_allocation',            'width' => 22],
                'allocated_ip'                    => ['label' => 'allocated_ip',                    'width' => 22],
                'division'                        => ['label' => 'division',                        'width' => 14],
                'district'                        => ['label' => 'district',                        'width' => 14],
                'thana'                           => ['label' => 'thana',                           'width' => 14],
                'address'                         => ['label' => 'address',                         'width' => 35],
                'client_mobile'                   => ['label' => 'client_mobile',                   'width' => 17],
                'client_email'                    => ['label' => 'client_email',                    'width' => 26],
                'selling_price_bdt_excluding_vat' => ['label' => 'selling_price_bdt_excluding_vat', 'width' => 32],
            ],
            default => [],
        };
    }
}
