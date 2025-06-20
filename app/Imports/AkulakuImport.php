<?php

namespace App\Imports;

use App\Models\Nasbah;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class AkulakuImport implements ToCollection, WithHeadingRow
{
    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function collection(Collection $rows)
    {
        Log::info('📊 Processing Akulaku import', ['rows' => $rows->count(), 'campaign_id' => $this->campaignId]);

        foreach ($rows as $row){
            try {
                //map Akulaku spesific columns

                $nasabah = Nasbah::create([
                    'campaign_id' => $this->campaignId,
                    'name' => $row['nama'] ?? $row['name'] ?? 'Unknown',
                    'phone' => $this->cleanPhoneNumber($row['no_hp'] ?? $row['phone'] ?? $row['nomor_telepon']),
                    'outstanding' => $this->parseAmount($row['outstanding'] ?? $row['saldo'] ?? 0),
                    'denda' => $this->parseAmount($row['denda'] ?? $row['penalty'] ?? 0),
                    'data_json' => json_encode([
                        'loan_id' => $row['loan_id'] ?? null,
                        'due_date' => $row['due_date'] ?? null,
                        'days_overdue' => $row['days_overdue'] ?? null,
                        'product_name' => $row['product_name'] ?? 'Akulaku',
                        'original_data' => $row->toArray(),
                    ]),
                    'is_called' => false,
                ]);

                Log::info('✅ Nasabah created', ['id' => $nasabah->id, 'name' => $nasabah->name]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to create nasabah', [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('✅ Akulaku import completed', ['campaign_id' => $this->campaignId]);
    }

    private function cleanPhoneNumber($phone)
    {
        //remove all non numeric Characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to Indonesian format
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

     private function parseAmount($amount)
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }
        
        // Remove currency symbols and convert to float
        $amount = preg_replace('/[^0-9.,]/', '', $amount);
        $amount = str_replace(',', '.', $amount);
        
        return (float) $amount;
    }
}
