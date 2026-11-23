<?php

declare(strict_types=1);

namespace App\Services\Prediction;

use App\Models\MlDataset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ZipArchive;

/**
 * Class DatasetImportService
 * 
 * Secure ingestion pipeline for external real-world fleet datasets (CSV & XLSX).
 * Implements validation, schema mapping, and automated integration with DataPreprocessingService.
 */
class DatasetImportService
{
    /** Standard canonical target fields expected by the ML engine */
    public const TARGET_FIELDS = [
        'plate_number'        => ['label' => 'Vehicle Plate / ID', 'required' => false, 'type' => 'string'],
        'service_type'        => ['label' => 'Service Type',       'required' => false, 'type' => 'string'],
        'scan_year'           => ['label' => 'Record Year',        'required' => false, 'type' => 'integer'],
        'days_since_service'  => ['label' => 'Days Since Service', 'required' => true,  'type' => 'numeric'],
        'km_since_service'    => ['label' => 'Km Since Service',   'required' => true,  'type' => 'numeric'],
        'interval_km'         => ['label' => 'Interval (Km)',      'required' => true,  'type' => 'numeric'],
        'interval_months'     => ['label' => 'Interval (Months)',  'required' => true,  'type' => 'numeric'],
        'daily_km'            => ['label' => 'Daily Usage (Km/d)', 'required' => true,  'type' => 'numeric'],
        'terrain_severity'    => ['label' => 'Terrain Severity',   'required' => false, 'type' => 'numeric'],
        'vehicle_age'         => ['label' => 'Vehicle Age',        'required' => false, 'type' => 'numeric'],
        'total_mileage'       => ['label' => 'Total Mileage',      'required' => false, 'type' => 'numeric'],
        'fuel_type'           => ['label' => 'Fuel Type',          'required' => false, 'type' => 'string'],
        'owner_type'          => ['label' => 'Owner Type',         'required' => false, 'type' => 'string'],
        'urgency_label'       => ['label' => 'Urgency Target (y)', 'required' => false, 'type' => 'string'],
    ];

    /** Heuristic aliases for auto-matching uploaded headers */
    public const ALIAS_MAP = [
        'plate_number'       => ['plate_number', 'plate', 'vehicle_id', 'vehicle', 'car_plate', 'registration'],
        'service_type'       => ['service_type', 'service', 'job_type', 'maintenance_type', 'task'],
        'scan_year'          => ['scan_year', 'year', 'record_year', 'date_year'],
        'days_since_service' => ['days_since_service', 'days_since_last_service', 'days_since', 'service_days', 'days_elapsed'],
        'km_since_service'   => ['km_since_service', 'km_since_last_service', 'mileage_since_service', 'km_since', 'km_elapsed'],
        'interval_km'        => ['interval_km', 'service_interval_km', 'rec_km', 'target_km', 'recommended_km'],
        'interval_months'    => ['interval_months', 'service_interval_months', 'rec_months', 'target_months'],
        'daily_km'           => ['daily_km', 'daily_usage', 'avg_daily_km', 'km_per_day', 'daily_mileage'],
        'terrain_severity'   => ['terrain_severity', 'terrain', 'operating_environment', 'road_condition', 'severity'],
        'vehicle_age'        => ['vehicle_age', 'age_years', 'age', 'car_age'],
        'total_mileage'      => ['total_mileage', 'odometer', 'mileage', 'current_mileage', 'lifetime_km'],
        'fuel_type'          => ['fuel_type', 'fuel', 'engine_type'],
        'owner_type'         => ['owner_type', 'owner', 'customer_type', 'fleet_type'],
        'urgency_label'      => ['urgency_label', 'urgency', 'label', 'status', 'maintenance_urgency', 'class', 'target'],
    ];

    protected DataPreprocessingService $preprocessor;

    public function __construct(?DataPreprocessingService $preprocessor = null)
    {
        $this->preprocessor = $preprocessor ?? new DataPreprocessingService();
    }

    /**
     * Parse and preview an uploaded file (CSV or XLSX) without saving permanently yet.
     *
     * @param string $filePath Absolute path or storage relative path
     * @param int $previewLimit
     * @return array{
     *     headers: array<int, string>,
     *     preview_rows: array<int, array<string, mixed>>,
     *     total_rows: int,
     *     suggested_mapping: array<string, string>
     * }
     */
    public function previewFile(string $filePath, int $previewLimit = 5, ?string $originalExtension = null): array
    {
        $ext = $originalExtension ? strtolower($originalExtension) : strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (empty($ext) || ! in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            $ext = 'csv';
        }

        if ($ext === 'csv') {
            $parsed = $this->readCsv($filePath, $previewLimit);
        } else {
            $parsed = $this->readXlsx($filePath, $previewLimit);
        }

        $suggestedMapping = $this->autoSuggestMapping($parsed['headers']);

        return [
            'headers'           => $parsed['headers'],
            'preview_rows'      => $parsed['rows'],
            'total_rows'        => $parsed['total_rows'],
            'suggested_mapping' => $suggestedMapping,
        ];
    }

    /**
     * Ingest, validate, map columns, preprocess, and optionally persist an external dataset.
     *
     * @param string $filePath Absolute path to source file
     * @param array<string, string> $columnMapping User-confirmed mapping: ['canonical_field' => 'uploaded_header']
     * @param array{
     *     dataset_name?: string,
     *     source_type?: string,
     *     user_id?: ?int,
     *     impute?: bool,
     *     clip_outliers?: bool
     * } $options
     * @param string|null $originalExtension
     * @return array{
     *     dataset: ?MlDataset,
     *     clean_rows: array<int, array<string, mixed>>,
     *     report: array<string, mixed>,
     *     row_count: int
     * }
     */
    public function importAndPreprocess(string $filePath, array $columnMapping, array $options = [], ?string $originalExtension = null): array
    {
        // 1. Read all rows from file
        $ext = $originalExtension ? strtolower($originalExtension) : strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (empty($ext) || ! in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            $ext = 'csv';
        }

        $allData = ($ext === 'csv')
            ? $this->readCsv($filePath, null)
            : $this->readXlsx($filePath, null);

        $rawRows = $allData['rows'];
        if (empty($rawRows)) {
            throw new InvalidArgumentException('Uploaded dataset is empty or unreadable.');
        }

        // 2. Map columns from uploaded headers to system canonical fields
        $mappedRows = [];
        foreach ($rawRows as $idx => $rawRow) {
            $mapped = [];
            foreach (self::TARGET_FIELDS as $canonicalKey => $meta) {
                $srcCol = $columnMapping[$canonicalKey] ?? null;
                $val = ($srcCol && isset($rawRow[$srcCol])) ? $rawRow[$srcCol] : null;

                if ($meta['type'] === 'numeric') {
                    $mapped[$canonicalKey] = ($val !== null && $val !== '' && is_numeric($val))
                        ? (float) $val
                        : null;
                } elseif ($canonicalKey === 'urgency_label') {
                    $mapped[$canonicalKey] = $val !== null ? strtoupper(trim((string) $val)) : null;
                } else {
                    $mapped[$canonicalKey] = $val !== null ? trim((string) $val) : null;
                }
            }

            // Defaults if missing
            if ($mapped['terrain_severity'] === null) {
                $mapped['terrain_severity'] = 1.0;
            }
            if ($mapped['interval_km'] === null) {
                $mapped['interval_km'] = 10000.0;
            }
            if ($mapped['interval_months'] === null) {
                $mapped['interval_months'] = 12.0;
            }
            if ($mapped['daily_km'] === null) {
                $mapped['daily_km'] = 40.0;
            }

            $mappedRows[] = $mapped;
        }

        // 3. Run mapped data through DataPreprocessingService
        $preprocessed = $this->preprocessor->preprocess($mappedRows, [
            'impute'        => $options['impute'] ?? true,
            'clip_outliers' => $options['clip_outliers'] ?? true,
            'scale'         => false,
        ]);

        $cleanRows = $preprocessed['clean_data'];
        $report = $preprocessed['report'];

        // 4. Optionally register into MlDataset table
        $dataset = null;
        if (! empty($options['dataset_name'])) {
            $datasetName = $options['dataset_name'];
            $sourceType = $options['source_type'] ?? 'kaggle_benchmark';
            $destRelativePath = 'ml_datasets/' . Str::slug($datasetName) . '_' . now()->format('Ymd_His') . '.csv';
            $destFullPath = storage_path('app/' . $destRelativePath);

            @mkdir(dirname($destFullPath), 0755, true);

            // Write preprocessed CSV
            $fp = fopen($destFullPath, 'w');
            if ($fp && ! empty($cleanRows)) {
                $headers = array_keys($cleanRows[0]);
                fputcsv($fp, $headers);
                foreach ($cleanRows as $row) {
                    fputcsv($fp, $row);
                }
                fclose($fp);
            }

            $dataset = MlDataset::create([
                'name'                 => $datasetName,
                'source_type'          => $sourceType,
                'file_path'            => $destRelativePath,
                'row_count'            => count($cleanRows),
                'feature_mapping_json' => $columnMapping,
                'uploaded_by'          => $options['user_id'] ?? null,
            ]);
        }

        return [
            'dataset'    => $dataset,
            'clean_rows' => $cleanRows,
            'report'     => $report,
            'row_count'  => count($cleanRows),
        ];
    }

    /**
     * Auto-suggest canonical field mapping for a set of file headers.
     *
     * @param array<int, string> $headers
     * @return array<string, string>
     */
    public function autoSuggestMapping(array $headers): array
    {
        $mapping = [];
        $normalizedHeaders = [];
        foreach ($headers as $h) {
            $norm = strtolower(trim(str_replace([' ', '-', '.'], '_', (string) $h)));
            $normalizedHeaders[$norm] = $h;
        }

        foreach (self::TARGET_FIELDS as $canonicalKey => $meta) {
            $aliases = self::ALIAS_MAP[$canonicalKey] ?? [$canonicalKey];
            $matched = null;

            foreach ($aliases as $alias) {
                if (isset($normalizedHeaders[$alias])) {
                    $matched = $normalizedHeaders[$alias];
                    break;
                }
            }

            if ($matched) {
                $mapping[$canonicalKey] = $matched;
            }
        }

        return $mapping;
    }

    /**
     * Read CSV using fgetcsv with BOM trimming.
     *
     * @param string $path
     * @param int|null $limit
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, total_rows: int}
     */
    public function readCsv(string $path, ?int $limit = null): array
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("File not found: [{$path}]");
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new InvalidArgumentException("Failed to open file: [{$path}]");
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rawHeaders = fgetcsv($handle);
        if (! $rawHeaders) {
            fclose($handle);
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rawHeaders);
        $rows = [];
        $rowCount = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowCount++;
            if ($limit === null || count($rows) < $limit) {
                $assoc = [];
                foreach ($headers as $idx => $h) {
                    $assoc[$h] = $data[$idx] ?? null;
                }
                $rows[] = $assoc;
            }
        }

        fclose($handle);

        return [
            'headers'    => $headers,
            'rows'       => $rows,
            'total_rows' => $rowCount,
        ];
    }

    /**
     * Lightweight pure-PHP XLSX reader using ZipArchive and SimpleXML.
     *
     * @param string $path
     * @param int|null $limit
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, total_rows: int}
     */
    public function readXlsx(string $path, ?int $limit = null): array
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("File not found: [{$path}]");
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException("Cannot open file as XLSX archive: [{$path}]");
        }

        // 1. Read shared strings if present
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $val) {
                    $sharedStrings[] = (string) ($val->t ?? ($val->r->t ?? ''));
                }
            }
        }

        // 2. Read first sheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new InvalidArgumentException('XLSX workbook has no sheet1.xml');
        }

        $sheet = simplexml_load_string($sheetXml);
        if (! $sheet || ! isset($sheet->sheetData->row)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $rawRows = [];
        foreach ($sheet->sheetData->row as $r) {
            $rowVals = [];
            foreach ($r->c as $c) {
                $type = (string) $c['t'];
                $val = (string) $c->v;
                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $val = $sharedStrings[(int) $val];
                }
                $rowVals[] = trim($val);
            }
            $rawRows[] = $rowVals;
        }

        if (empty($rawRows)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $headers = array_shift($rawRows);
        $rows = [];
        $totalCount = count($rawRows);

        foreach ($rawRows as $i => $rowVals) {
            if ($limit === null || count($rows) < $limit) {
                $assoc = [];
                foreach ($headers as $colIdx => $h) {
                    $assoc[$h] = $rowVals[$colIdx] ?? null;
                }
                $rows[] = $assoc;
            }
        }

        return [
            'headers'    => $headers,
            'rows'       => $rows,
            'total_rows' => $totalCount,
        ];
    }
}
