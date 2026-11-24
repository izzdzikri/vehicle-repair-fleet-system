<?php

namespace Database\Seeders;

use App\Models\CompanyDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CompanyDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@teraju.test',
            'role' => 'admin',
        ]);

        // Generate sample PDF and PNG payloads
        $minimalPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n";
        $minimalPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        $ssmPdfPath = 'company_documents/demo_ssm_incorporation.pdf';
        $ssmRenewalPdfPath = 'company_documents/demo_ssm_renewal.pdf';
        $do1PdfPath = 'company_documents/demo_delivery_order_oil.pdf';
        $do2PngPath = 'company_documents/demo_delivery_order_tyres.png';
        $insPdfPath = 'company_documents/demo_insurance_policy.pdf';
        $envPdfPath = 'company_documents/demo_doe_license.pdf';
        $councilPdfPath = 'company_documents/demo_council_license.pdf';

        Storage::disk('private')->put($ssmPdfPath, $minimalPdf);
        Storage::disk('private')->put($ssmRenewalPdfPath, $minimalPdf);
        Storage::disk('private')->put($do1PdfPath, $minimalPdf);
        Storage::disk('private')->put($do2PngPath, $minimalPng);
        Storage::disk('private')->put($insPdfPath, $minimalPdf);
        Storage::disk('private')->put($envPdfPath, $minimalPdf);
        Storage::disk('private')->put($councilPdfPath, $minimalPdf);

        $documents = [
            [
                'title' => 'SSM Certificate of Incorporation (Form 9 - Teraju Setia Sdn Bhd)',
                'category' => 'ssm_certificate',
                'reference_no' => '202001034821',
                'issue_date' => Carbon::parse('2020-08-15'),
                'expiry_date' => null,
                'notes' => 'Official certificate of company registration under the Companies Act 2016 (Form 9).',
                'file_path' => $ssmPdfPath,
                'original_name' => 'SSM_Certificate_Form9_TerajuSetia.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($minimalPdf),
            ],
            [
                'title' => 'SSM Annual Business Renewal Certificate 2026/2027',
                'category' => 'ssm_certificate',
                'reference_no' => 'SSM-RN-2026-9041',
                'issue_date' => Carbon::today()->subMonths(11),
                'expiry_date' => Carbon::today()->addDays(14), // Exactly 14 days milestone
                'notes' => 'Annual business registration renewal registered with Suruhanjaya Syarikat Malaysia.',
                'file_path' => $ssmRenewalPdfPath,
                'original_name' => 'SSM_Business_Renewal_2026.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($minimalPdf),
            ],
            [
                'title' => 'Delivery Order - Castrol Magnatec Engine Oil (Batch 440)',
                'category' => 'delivery_order',
                'reference_no' => 'DO-CAS-2026-1044',
                'issue_date' => Carbon::today()->subDays(20),
                'expiry_date' => null,
                'notes' => 'Delivered 50 drums of synthetic blend 10W-40 by Castrol Malaysia Sdn Bhd.',
                'file_path' => $do1PdfPath,
                'original_name' => 'DO_Castrol_Batch440.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($minimalPdf),
            ],
            [
                'title' => 'Delivery Order - Continental Commercial Tyre Consignment',
                'category' => 'delivery_order',
                'reference_no' => 'DO-CONTI-2026-092',
                'issue_date' => Carbon::today()->subDays(5),
                'expiry_date' => null,
                'notes' => 'Consignment of 24 units 195/50R15 ContiComfortContact tyres.',
                'file_path' => $do2PngPath,
                'original_name' => 'DO_Continental_Tyres_Scan.png',
                'mime' => 'image/png',
                'size' => strlen($minimalPng),
            ],
            [
                'title' => 'Allianz Comprehensive Workshop Fire, Flood & Liability Policy',
                'category' => 'other',
                'reference_no' => 'ALZ-WKP-2025-8820',
                'issue_date' => Carbon::today()->subDays(358),
                'expiry_date' => Carbon::today()->addDays(7), // Exactly 7 days milestone
                'notes' => 'Commercial workshop premises fire, flood, machinery breakdown and public liability coverage.',
                'file_path' => $insPdfPath,
                'original_name' => 'Allianz_Workshop_Policy_2025_2026.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($minimalPdf),
            ],
            [
                'title' => 'DOE Scheduled Waste Disposal & Storage License (JAS)',
                'category' => 'other',
                'reference_no' => 'DOE-JAS-SW-4109',
                'issue_date' => Carbon::today()->subDays(375),
                'expiry_date' => Carbon::today()->subDays(10), // Expired 10 days ago
                'notes' => 'Scheduled waste management license issued by Jabatan Alam Sekitar.',
                'file_path' => $envPdfPath,
                'original_name' => 'DOE_JAS_Waste_License.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($minimalPdf),
            ],
            [
                'title' => 'Majlis Bandaraya Workshop Operating License (MBPJ/MBSJ)',
                'category' => 'other',
                'reference_no' => 'MBSJ-LESEN-2026-041',
                'issue_date' => Carbon::today()->subDays(335),
                'expiry_date' => Carbon::today()->addDays(30), // Exactly 30 days milestone
                'notes' => 'Municipal business operating premises license for automotive workshop.',
                'file_path' => $councilPdfPath,
                'original_name' => 'MBSJ_Workshop_Operating_License.pdf',
                'mime' => 'application/pdf',
                'size' => strlen($minimalPdf),
            ],
        ];

        foreach ($documents as $docData) {
            CompanyDocument::updateOrCreate(
                ['reference_no' => $docData['reference_no']],
                array_merge($docData, ['uploaded_by' => $admin->id])
            );
        }
    }
}
