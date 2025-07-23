<?php

namespace App\Imports;

use App\Models\Nasbah;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;
use Exception;

class AkulakuImport implements ToCollection, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    protected $campaignId;
    protected $processedRows = 0;
    protected $errorRows = 0;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function collection(Collection $rows)
    {
        Log::info('📊 Processing Excel rows', [
            'rows_count' => $rows->count(), 
            'campaign_id' => $this->campaignId
        ]);

        if ($rows->isEmpty()) {
            Log::warning('⚠️ No data rows found in Excel file');
            return;
        }

        foreach ($rows as $index => $row) {
            try {
                // Skip empty rows
                if ($this->isEmptyRow($row)) {
                    Log::debug('⏭️ Skipping empty row', ['row_index' => $index]);
                    continue;
                }

                // Log first few rows for debugging
                if ($index < 3) {
                    Log::info('🔍 Sample row data', [
                        'row_index' => $index,
                        'row_data' => $row->toArray()
                    ]);
                }

                // Extract and clean data
                $name = $this->cleanString($row['nama'] ?? $row['name'] ?? 'Unknown');
                $phone = $this->cleanPhoneNumber($row['no_hp'] ?? $row['phone'] ?? $row['nomor_telepon'] ?? '');
                $outstanding = $this->parseAmount($row['outstanding'] ?? $row['saldo'] ?? 0);
                $denda = $this->parseAmount($row['denda'] ?? $row['penalty'] ?? 0);

                // Validate required fields
                if (empty($name) || empty($phone)) {
                    Log::warning('⚠️ Skipping row with missing required data', [
                        'row_index' => $index,
                        'name' => $name,
                        'phone' => $phone
                    ]);
                    $this->errorRows++;
                    continue;
                }

                // Create Nasbah record
                $nasbah = Nasbah::create([
                    'campaign_id' => $this->campaignId,
                    'name' => $name,
                    'phone' => $phone,
                    'outstanding' => $outstanding,
                    'denda' => $denda,
                    'data_json' => json_encode([
                        'loan_id' => $row['loan_id'] ?? null,
                        'due_date' => $row['due_date'] ?? null,
                        'days_overdue' => $row['days_overdue'] ?? null,
                        'product_name' => $row['product_name'] ?? 'Akulaku',
                        'original_data' => $row->toArray(),
                        'imported_at' => now()->toISOString(),
                    ]),
                    'is_called' => false,
                ]);

                $this->processedRows++;

                if ($this->processedRows % 100 === 0) {
                    Log::info('📈 Import progress', [
                        'processed' => $this->processedRows,
                        'errors' => $this->errorRows,
                        'campaign_id' => $this->campaignId
                    ]);
                }

            } catch (Exception $e) {
                $this->errorRows++;
                Log::error('❌ Failed to process row', [
                    'row_index' => $index,
                    'row_data' => $row->toArray(),
                    'error' => $e->getMessage(),
                    'campaign_id' => $this->campaignId
                ]);
            }
        }

        Log::info('✅ Import batch completed', [
            'campaign_id' => $this->campaignId,
            'processed_rows' => $this->processedRows,
            'error_rows' => $this->errorRows,
            'total_rows' => $rows->count()
        ]);
    }

    public function rules(): array
    {
        return [
            '*.nama' => 'sometimes|string|max:255',
            '*.name' => 'sometimes|string|max:255',
            '*.no_hp' => 'sometimes|string|max:20',
            '*.phone' => 'sometimes|string|max:20',
            '*.nomor_telepon' => 'sometimes|string|max:20',
            '*.outstanding' => 'sometimes|numeric|min:0',
            '*.saldo' => 'sometimes|numeric|min:0',
            '*.denda' => 'sometimes|numeric|min:0',
            '*.penalty' => 'sometimes|numeric|min:0',
        ];
    }

    public function batchSize(): int
    {
        return 500; // Process 500 rows at a time
    }

    public function chunkSize(): int
    {
        return 1000; // Read 1000 rows at a time
    }

    private function isEmptyRow(Collection $row): bool
    {
        $importantFields = ['nama', 'name', 'no_hp', 'phone', 'nomor_telepon'];
        
        foreach ($importantFields as $field) {
            if (!empty($row[$field])) {
                return false;
            }
        }
        
        return true;
    }

    private function cleanString(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        return trim(strip_tags($value));
    }

    private function cleanPhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to Indonesian format
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    private function parseAmount($amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }
        
        if (empty($amount)) {
            return 0.0;
        }
        
        // Remove currency symbols and convert to float
        $amount = preg_replace('/[^0-9.,]/', '', $amount);
        $amount = str_replace(',', '.', $amount);
        
        return (float) $amount;
    }
}