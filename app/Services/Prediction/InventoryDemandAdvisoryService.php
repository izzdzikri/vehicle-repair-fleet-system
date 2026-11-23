<?php

declare(strict_types=1);

namespace App\Services\Prediction;

use App\Models\JobCardPart;
use App\Models\MaintenanceAlert;
use App\Models\SparePart;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Class InventoryDemandAdvisoryService
 * 
 * Translates predicted High and Medium maintenance alerts into spare parts
 * restocking advisory recommendations.
 * 
 * STRICT CONSTRAINT: Recommendation only, never auto-purchases.
 */
class InventoryDemandAdvisoryService
{
    /**
     * Map common service alert keywords to part categories and search tokens.
     */
    protected const SERVICE_PART_KEYWORD_MAP = [
        'oil'          => ['category' => 'Lubricants', 'tokens' => ['oil', 'synthetic', 'filter']],
        'engine'       => ['category' => 'Lubricants', 'tokens' => ['oil', 'filter', 'spark']],
        'brake'        => ['category' => 'Brakes',     'tokens' => ['brake', 'pad', 'fluid']],
        'tyre'         => ['category' => 'Tyres',      'tokens' => ['tire', 'tyre', 'valve']],
        'tire'         => ['category' => 'Tyres',      'tokens' => ['tire', 'tyre', 'valve']],
        'coolant'      => ['category' => 'Fluids',     'tokens' => ['coolant', 'radiator']],
        'transmission' => ['category' => 'Fluids',     'tokens' => ['transmission', 'atf']],
        'filter'       => ['category' => 'Filters',    'tokens' => ['filter', 'air']],
        'battery'      => ['category' => 'Electrical', 'tokens' => ['battery']],
    ];

    /**
     * Generate inventory demand restock recommendations from high and medium urgency alerts.
     *
     * @param iterable<MaintenanceAlert>|Collection $alerts
     * @return array{
     *     advisory_items: array<int, array<string, mixed>>,
     *     total_high_medium_alerts: int,
     *     total_shortfall_units: int,
     *     estimated_budget: float,
     *     disclaimer: string
     * }
     */
    public function generateAdvisory(iterable $alerts): array
    {
        $alertsColl = $alerts instanceof Collection ? $alerts : collect($alerts);

        // Filter High and Medium urgency alerts only
        $targetAlerts = $alertsColl->filter(function ($a) {
            $urgency = strtolower((string) ($a->effective_urgency ?? $a->urgency));
            return in_array($urgency, ['high', 'medium'], true);
        });

        if ($targetAlerts->isEmpty()) {
            return [
                'advisory_items'           => [],
                'active_alert_count'       => $alertsColl->count(),
                'high_urgency_count'       => 0,
                'medium_urgency_count'     => 0,
                'total_high_medium_alerts' => 0,
                'total_shortfall_units'    => 0,
                'total_estimated_budget'   => 0.0,
                'estimated_budget'         => 0.0,
                'disclaimer'               => 'Advisory restock recommendation only. The system never executes automatic purchases without manual confirmation.',
            ];
        }

        // Tally required services
        $serviceCounts = [];
        foreach ($targetAlerts as $alert) {
            $type = trim((string) ($alert->alert_type ?: 'General Service'));
            $serviceCounts[$type] = ($serviceCounts[$type] ?? 0) + 1;
        }

        $allSpareParts = SparePart::all();
        $allSuppliers = Supplier::all()->keyBy('name');

        $aggregatedDemand = [];

        foreach ($serviceCounts as $serviceType => $count) {
            $matchedParts = $this->findPartsForService($serviceType, $allSpareParts);

            foreach ($matchedParts as $part) {
                $pid = $part->id;
                if (! isset($aggregatedDemand[$pid])) {
                    $supplier = $allSuppliers->get($part->supplier_name) ?? $allSuppliers->first();

                    $aggregatedDemand[$pid] = [
                        'part_id'          => $part->id,
                        'part_name'        => $part->name,
                        'part_number'      => $part->part_number ?? ('PRT-' . $part->id),
                        'category'         => $part->category ?? 'General Maintenance',
                        'current_stock'    => (int) $part->stock,
                        'min_stock'        => (int) $part->min_stock,
                        'stock_status'     => $part->stockStatus(),
                        'unit_price'       => (float) $part->unit_price,
                        'supplier_name'    => $supplier?->name ?? ($part->supplier_name ?: 'Approved Distributor'),
                        'supplier_id'      => $supplier?->id,
                        'recommended_qty'  => 0,
                        'triggered_by'     => [],
                    ];
                }

                $aggregatedDemand[$pid]['recommended_qty'] += $count;
                if (! in_array($serviceType, $aggregatedDemand[$pid]['triggered_by'], true)) {
                    $aggregatedDemand[$pid]['triggered_by'][] = "{$serviceType} ({$count} vehicles)";
                }
            }
        }

        // Calculate shortfall and estimated restock budget
        $advisoryItems = [];
        $totalShortfallUnits = 0;
        $totalEstimatedBudget = 0.0;

        foreach ($aggregatedDemand as $item) {
            // Shortfall: required units to cover fleet demand + safety buffer, minus on-hand stock
            $shortfall = max(0, ($item['recommended_qty'] + $item['min_stock']) - $item['current_stock']);
            $restockBudget = $shortfall * $item['unit_price'];

            $item['shortfall_units'] = $shortfall;
            $item['estimated_cost']  = $restockBudget;
            $item['needs_restock']   = $shortfall > 0 || $item['current_stock'] <= $item['min_stock'];

            $totalShortfallUnits += $shortfall;
            $totalEstimatedBudget += $restockBudget;

            $advisoryItems[] = $item;
        }

        // Sort items so parts with shortfall and low stock appear first
        usort($advisoryItems, function ($a, $b) {
            if ($a['needs_restock'] !== $b['needs_restock']) {
                return $a['needs_restock'] ? -1 : 1;
            }
            return $b['shortfall_units'] <=> $a['shortfall_units'];
        });

        return [
            'advisory_items'           => $advisoryItems,
            'active_alert_count'       => $alerts->count(),
            'high_urgency_count'       => $alerts->where('urgency', 'high')->count(),
            'medium_urgency_count'     => $alerts->where('urgency', 'medium')->count(),
            'total_high_medium_alerts' => $targetAlerts->count(),
            'total_shortfall_units'    => $totalShortfallUnits,
            'total_estimated_budget'   => round($totalEstimatedBudget, 2),
            'estimated_budget'         => round($totalEstimatedBudget, 2),
            'disclaimer'               => 'Advisory restock recommendation only. The system never executes automatic purchases without manual administrative confirmation.',
        ];
    }

    /**
     * Map a service type string to relevant spare parts in inventory.
     *
     * @param string $serviceType
     * @param Collection<int, SparePart> $allParts
     * @return Collection<int, SparePart>
     */
    protected function findPartsForService(string $serviceType, Collection $allParts): Collection
    {
        $typeLower = strtolower($serviceType);

        // 1. Direct name match (highest specificity)
        $exactMatches = $allParts->filter(function ($p) use ($typeLower) {
            $pName = strtolower($p->name);
            return str_contains($pName, $typeLower) || str_contains($typeLower, $pName);
        });

        if ($exactMatches->isNotEmpty()) {
            return $exactMatches;
        }

        // 2. Check keyword map
        foreach (self::SERVICE_PART_KEYWORD_MAP as $kw => $config) {
            if (str_contains($typeLower, $kw)) {
                $matches = $allParts->filter(function ($p) use ($config) {
                    $pName = strtolower($p->name);
                    $pCat  = strtolower($p->category);

                    foreach ($config['tokens'] as $tok) {
                        if (str_contains($pName, $tok) || str_contains($pCat, $tok)) {
                            return true;
                        }
                    }
                    return false;
                });

                if ($matches->isNotEmpty()) {
                    return $matches;
                }
            }
        }

        // 3. Fallback heuristic: match any word token in part name
        $tokens = array_filter(explode(' ', $typeLower), fn ($t) => strlen($t) > 3);
        $fallbackMatches = $allParts->filter(function ($p) use ($tokens) {
            $pName = strtolower($p->name);
            foreach ($tokens as $t) {
                if (str_contains($pName, $t)) {
                    return true;
                }
            }
            return false;
        });

        if ($fallbackMatches->isNotEmpty()) {
            return $fallbackMatches;
        }

        // 4. Ultimate fallback: default oil or filter
        $defaultPart = $allParts->first(fn ($p) => str_contains(strtolower($p->name), 'oil') || str_contains(strtolower($p->name), 'filter'));
        return $defaultPart ? collect([$defaultPart]) : collect();
    }
}
