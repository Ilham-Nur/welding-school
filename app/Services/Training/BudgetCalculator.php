<?php

namespace App\Services\Training;

use Illuminate\Validation\ValidationException;

class BudgetCalculator
{
    public const QUANTITIES = ['manual' => 'Manual', 'participants' => 'Peserta', 'instructors' => 'Instruktur', 'assistants' => 'Asisten', 'staff' => 'Instruktur + asisten', 'machines' => 'Mesin las', 'tools' => 'Set alat kerja'];

    public const FREQUENCIES = ['manual' => 'Manual', 'days' => 'Hari pelatihan', 'practice_days' => 'Hari praktik'];

    public function calculate(array $inputs): array
    {
        $batches = collect($inputs['batches'])->keyBy('id')->all();
        $participants = array_sum(array_column($batches, 'participants'));
        if ($participants < 1) {
            throw ValidationException::withMessages(['batches' => 'Jumlah target peserta harus lebih dari nol.']);
        }
        $costs = array_fill_keys(array_keys($batches), 0);
        $shared = 0;
        $lines = [];
        foreach ($inputs['items'] as $index => $item) {
            $amounts = [];
            if ($item['scope'] === 'shared') {
                $amounts['shared'] = $this->amount($item, []);
                $shared += $amounts['shared'];
            } else {
                foreach ($batches as $id => $batch) {
                    if (! empty($item['batch_id']) && (int) $item['batch_id'] !== (int) $id) {
                        continue;
                    }
                    $amounts[$id] = $this->amount($item, $batch);
                    $costs[$id] += $amounts[$id];
                }
            }
            $lines[] = ['index' => $index, 'description' => $item['description'], 'amount' => array_sum($amounts), 'by_batch' => $amounts];
        }
        $base = array_sum($costs) + $shared;
        $reserve = (int) round($base * $inputs['contingency_percent'] / 100);
        $total = $base + $reserve;
        $feeTaxFactor = 1 + $inputs['gateway_tax_percent'] / 100;
        $feeRate = $inputs['gateway_percent'] / 100 * $feeTaxFactor;
        $fixedFee = $inputs['gateway_fixed'] * $inputs['payments_per_participant'] * $feeTaxFactor;
        $salesTaxFactor = 1 + $inputs['sales_tax_percent'] / 100;
        $denominator = 1 / $salesTaxFactor - $feeRate;
        if ($denominator <= 0) {
            throw ValidationException::withMessages(['gateway_percent' => 'Kombinasi tarif gateway dan pajak tidak menghasilkan harga yang valid.']);
        }
        // Solve P/(1+sales tax) - gateway(P) = cost per person + target markup.
        $unroundedPrice = ($total / $participants * (1 + $inputs['markup_percent'] / 100) + $fixedFee) / $denominator;
        if ($base > 1_000_000_000_000 || ! is_finite($unroundedPrice) || $unroundedPrice > 1_000_000_000 || $unroundedPrice < 0) {
            throw ValidationException::withMessages(['items' => 'Hasil perhitungan melampaui batas RAB atau harga per peserta. Periksa Qty dan tarif.']);
        }
        $price = (int) ceil($unroundedPrice);
        $revenue = $price * $participants;
        $gateway = (int) ceil(($price * $feeRate + $fixedFee) * $participants);
        $tax = (int) round($revenue - $revenue / $salesTaxFactor);
        $allocation = [];
        $remaining = $shared;
        $last = array_key_last($batches);
        foreach ($batches as $id => $batch) {
            $share = $id === $last ? $remaining : (int) floor($shared * $batch['participants'] / $participants);
            $remaining -= $share;
            $allocation[$id] = ['direct' => $costs[$id], 'shared' => $share, 'base' => $costs[$id] + $share];
        }

        return compact('participants', 'base', 'reserve', 'total', 'price', 'revenue', 'gateway', 'tax', 'shared', 'lines', 'allocation') + [
            'cost_per_participant' => $total / $participants,
            'all_in_cost' => $total + $gateway + $tax,
            'profit' => $revenue - $tax - $gateway - $total,
        ];
    }

    public function units(array $item, array $batch): float
    {
        $quantity = match ($item['quantity_basis']) {
            'manual' => 1,
            'staff' => $batch['instructors'] + $batch['assistants'],
            default => $batch[$item['quantity_basis']],
        };
        $frequency = $item['frequency_basis'] === 'manual' ? 1 : $batch[$item['frequency_basis']];

        return $quantity * $item['quantity'] * $frequency * $item['frequency'];
    }

    private function amount(array $item, array $batch): int
    {
        $amount = $this->units($item, $batch) * $item['unit_price'];
        if (! is_finite((float) $amount) || $amount > 1_000_000_000_000 || $amount < 0) {
            throw ValidationException::withMessages(['items' => 'Jumlah komponen melampaui batas perhitungan. Periksa Qty, frekuensi, dan harga satuan.']);
        }

        return (int) round($amount);
    }
}
