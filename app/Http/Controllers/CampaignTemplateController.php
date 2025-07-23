<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CampaignTemplateController extends Controller
{
    public function downloadTemplate(Request $request)
    {
        $productType = $request->get('product_type', 'akulaku');
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set template based on product type
        switch ($productType) {
            case 'akulaku':
                $this->createAkulakuTemplate($sheet);
                break;
            case 'BNI':
                $this->createBNITemplate($sheet);
                break;
            case 'BRI':
                $this->createBRITemplate($sheet);
                break;
            case 'CashWagon':
                $this->createCashWagonTemplate($sheet);
                break;
            case 'MauCash':
                $this->createMauCashTemplate($sheet);
                break;
            case 'KoinWorks':
                $this->createKoinWorksTemplate($sheet);
                break;
            case 'KP+':
                $this->createKPPlusTemplate($sheet);
                break;
            case 'PinjamYuk':
                $this->createPinjamYukTemplate($sheet);
                break;
            case 'UangMe':
                $this->createUangMeTemplate($sheet);
                break;
            default:
                $this->createDefaultTemplate($sheet);
                break;
        }
        
        $writer = new Xlsx($spreadsheet);
        
        $filename = "template_campaign_{$productType}_" . date('Y-m-d') . '.xlsx';
        
        $temp = tempnam(sys_get_temp_dir(), 'template');
        $writer->save($temp);
        
        return Response::download($temp, $filename)->deleteFileAfterSend(true);
    }
    
    private function createAkulakuTemplate($sheet)
    {
        $sheet->setTitle('Akulaku Template');
        
        // Headers
        $headers = [
            'A1' => 'nama',
            'B1' => 'no_hp', 
            'C1' => 'outstanding',
            'D1' => 'denda',
            'E1' => 'loan_id',
            'F1' => 'due_date',
            'G1' => 'days_overdue',
            'H1' => 'product_name'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Sample data
        $sheet->setCellValue('A2', 'John Doe');
        $sheet->setCellValue('B2', '081234567890');
        $sheet->setCellValue('C2', '5000000');
        $sheet->setCellValue('D2', '500000');
        $sheet->setCellValue('E2', 'AKL001');
        $sheet->setCellValue('F2', '2024-01-15');
        $sheet->setCellValue('G2', '30');
        $sheet->setCellValue('H2', 'Akulaku Personal Loan');
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createBNITemplate($sheet)
    {
        $sheet->setTitle('BNI Template');
        
        $headers = [
            'A1' => 'nama',
            'B1' => 'phone',
            'C1' => 'outstanding',
            'D1' => 'penalty',
            'E1' => 'account_number',
            'F1' => 'product_type',
            'G1' => 'branch_code',
            'H1' => 'due_date'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $sheet->setCellValue('A2', 'Jane Smith');
        $sheet->setCellValue('B2', '081234567891');
        $sheet->setCellValue('C2', '10000000');
        $sheet->setCellValue('D2', '1000000');
        $sheet->setCellValue('E2', '1234567890');
        $sheet->setCellValue('F2', 'Credit Card');
        $sheet->setCellValue('G2', 'BNI001');
        $sheet->setCellValue('H2', '2024-01-20');
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createBRITemplate($sheet)
    {
        $sheet->setTitle('BRI Template');
        
        $headers = [
            'A1' => 'name',
            'B1' => 'nomor_telepon',
            'C1' => 'saldo',
            'D1' => 'denda',
            'E1' => 'rekening',
            'F1' => 'jenis_produk',
            'G1' => 'cabang',
            'H1' => 'tanggal_jatuh_tempo'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $sheet->setCellValue('A2', 'Ahmad Wijaya');
        $sheet->setCellValue('B2', '081234567892');
        $sheet->setCellValue('C2', '7500000');
        $sheet->setCellValue('D2', '750000');
        $sheet->setCellValue('E2', '0987654321');
        $sheet->setCellValue('F2', 'KUR');
        $sheet->setCellValue('G2', 'BRI002');
        $sheet->setCellValue('H2', '2024-01-25');
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createDefaultTemplate($sheet)
    {
        $sheet->setTitle('Default Template');
        
        $headers = [
            'A1' => 'nama',
            'B1' => 'phone',
            'C1' => 'outstanding',
            'D1' => 'denda',
            'E1' => 'customer_id',
            'F1' => 'product_name',
            'G1' => 'due_date',
            'H1' => 'notes'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $sheet->setCellValue('A2', 'Sample Customer');
        $sheet->setCellValue('B2', '081234567890');
        $sheet->setCellValue('C2', '1000000');
        $sheet->setCellValue('D2', '100000');
        $sheet->setCellValue('E2', 'CUST001');
        $sheet->setCellValue('F2', 'Default Product');
        $sheet->setCellValue('G2', '2024-01-30');
        $sheet->setCellValue('H2', 'Sample notes');
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createCashWagonTemplate($sheet)
    {
        $sheet->setTitle('CashWagon Template');
        
        $headers = [
            'A1' => 'nama',
            'B1' => 'no_hp',
            'C1' => 'outstanding',
            'D1' => 'penalty',
            'E1' => 'loan_reference',
            'F1' => 'loan_amount',
            'G1' => 'overdue_days',
            'H1' => 'status'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createMauCashTemplate($sheet)
    {
        $sheet->setTitle('MauCash Template');
        
        $headers = [
            'A1' => 'nama',
            'B1' => 'phone',
            'C1' => 'outstanding',
            'D1' => 'denda',
            'E1' => 'contract_id',
            'F1' => 'loan_type',
            'G1' => 'maturity_date',
            'H1' => 'region'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createKoinWorksTemplate($sheet)
    {
        $sheet->setTitle('KoinWorks Template');
        
        $headers = [
            'A1' => 'name',
            'B1' => 'nomor_telepon',
            'C1' => 'saldo',
            'D1' => 'penalty',
            'E1' => 'investment_id',
            'F1' => 'product_category',
            'G1' => 'due_date',
            'H1' => 'risk_level'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createKPPlusTemplate($sheet)
    {
        $sheet->setTitle('KP+ Template');
        
        $headers = [
            'A1' => 'nama',
            'B1' => 'no_hp',
            'C1' => 'outstanding',
            'D1' => 'denda',
            'E1' => 'member_id',
            'F1' => 'package_type',
            'G1' => 'expiry_date',
            'H1' => 'tier_level'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createPinjamYukTemplate($sheet)
    {
        $sheet->setTitle('PinjamYuk Template');
        
        $headers = [
            'A1' => 'nama',
            'B1' => 'phone',
            'C1' => 'outstanding',
            'D1' => 'penalty',
            'E1' => 'application_id',
            'F1' => 'loan_purpose',
            'G1' => 'installment_due',
            'H1' => 'collector_area'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function createUangMeTemplate($sheet)
    {
        $sheet->setTitle('UangMe Template');
        
        $headers = [
            'A1' => 'nama',
            'B1' => 'nomor_telepon',
            'C1' => 'saldo',
            'D1' => 'denda',
            'E1' => 'wallet_id',
            'F1' => 'transaction_type',
            'G1' => 'last_payment',
            'H1' => 'payment_method'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        $this->styleHeaders($sheet, 'A1:H1');
    }
    
    private function styleHeaders($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}