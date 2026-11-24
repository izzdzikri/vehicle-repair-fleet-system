<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyDocumentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_admin_can_view_document_hub_and_filter_by_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $doc1 = CompanyDocument::create([
            'title' => 'SSM Annual Registration Form',
            'category' => 'ssm_certificate',
            'reference_no' => 'SSM-TEST-1001',
            'issue_date' => '2026-01-10',
            'expiry_date' => '2027-01-10',
            'notes' => 'Testing SSM filter',
            'file_path' => 'company_documents/test1.pdf',
            'original_name' => 'test1.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $admin->id,
        ]);

        $doc2 = CompanyDocument::create([
            'title' => 'Tyre Parts Delivery Order',
            'category' => 'delivery_order',
            'reference_no' => 'DO-TEST-2002',
            'issue_date' => '2026-02-15',
            'expiry_date' => null,
            'notes' => 'Testing DO filter',
            'file_path' => 'company_documents/test2.pdf',
            'original_name' => 'test2.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
            'uploaded_by' => $admin->id,
        ]);

        // Access index page
        $response = $this->actingAs($admin)->get(route('admin.documents.index'));
        $response->assertStatus(200);
        $response->assertSee('Document Management Hub');
        $response->assertSee('SSM Annual Registration Form');
        $response->assertSee('Tyre Parts Delivery Order');

        // Filter by category
        $filteredResponse = $this->actingAs($admin)->get(route('admin.documents.index', ['category' => 'ssm_certificate']));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee('SSM Annual Registration Form');
        $filteredResponse->assertDontSee('Tyre Parts Delivery Order');

        // Search by query
        $searchedResponse = $this->actingAs($admin)->get(route('admin.documents.index', ['search' => 'DO-TEST-2002']));
        $searchedResponse->assertStatus(200);
        $searchedResponse->assertSee('Tyre Parts Delivery Order');
        $searchedResponse->assertDontSee('SSM Annual Registration Form');
    }

    public function test_admin_can_upload_valid_document_to_private_disk(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $fakePdf = UploadedFile::fake()->create('company_ssm_incorporation.pdf', 800, 'application/pdf');

        $payload = [
            'title' => 'Suruhanjaya Syarikat Malaysia Certificate of Incorporation',
            'category' => 'ssm_certificate',
            'reference_no' => '202601099882',
            'issue_date' => '2026-03-01',
            'expiry_date' => null,
            'notes' => 'Perpetual registration cert under CA 2016.',
            'file' => $fakePdf,
        ];

        $response = $this->actingAs($admin)->post(route('admin.documents.store'), $payload);

        $document = CompanyDocument::where('reference_no', '202601099882')->first();
        $this->assertNotNull($document);

        $response->assertRedirect(route('admin.documents.show', $document));
        $response->assertSessionHas('success');

        $this->assertEquals('Suruhanjaya Syarikat Malaysia Certificate of Incorporation', $document->title);
        $this->assertEquals('ssm_certificate', $document->category);
        $this->assertEquals('application/pdf', $document->mime);
        $this->assertEquals($admin->id, $document->uploaded_by);

        Storage::disk('private')->assertExists($document->file_path);
    }

    public function test_invalid_upload_rejected_for_size_and_disallowed_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // Test file exceeding 5MB (5120 KB)
        $oversizedFile = UploadedFile::fake()->create('huge_manual.pdf', 6000, 'application/pdf');

        $responseOversize = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => 'Oversized Document',
            'category' => 'other',
            'reference_no' => 'OVER-001',
            'issue_date' => '2026-03-01',
            'file' => $oversizedFile,
        ]);

        $responseOversize->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('company_documents', ['reference_no' => 'OVER-001']);

        // Test disallowed file type (e.g. .exe / binary)
        $disallowedFile = UploadedFile::fake()->create('malicious.exe', 50, 'application/x-msdownload');

        $responseDisallowed = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'title' => 'Disallowed Binary',
            'category' => 'other',
            'reference_no' => 'DISALLOW-001',
            'issue_date' => '2026-03-01',
            'file' => $disallowedFile,
        ]);

        $responseDisallowed->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('company_documents', ['reference_no' => 'DISALLOW-001']);
    }

    public function test_admin_can_view_document_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        Storage::disk('private')->put('company_documents/dummy.pdf', '%PDF-1.4 dummy content');

        $document = CompanyDocument::create([
            'title' => 'Workshop Electrical Safety Audit',
            'category' => 'other',
            'reference_no' => 'ELEC-AUDIT-2026',
            'issue_date' => '2026-04-10',
            'expiry_date' => '2027-04-10',
            'notes' => 'Suruhanjaya Tenaga 3-year audit verification.',
            'file_path' => 'company_documents/dummy.pdf',
            'original_name' => 'dummy.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.documents.show', $document));
        $response->assertStatus(200);
        $response->assertSee('Workshop Electrical Safety Audit');
        $response->assertSee('ELEC-AUDIT-2026');
        $response->assertSee('Suruhanjaya Tenaga 3-year audit verification.');
    }

    public function test_admin_can_preview_and_download_document_stream(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $samplePdfContent = '%PDF-1.4 minimal stream test content';
        $path = 'company_documents/preview_test.pdf';
        Storage::disk('private')->put($path, $samplePdfContent);

        $document = CompanyDocument::create([
            'title' => 'Preview Stream Test Document',
            'category' => 'delivery_order',
            'reference_no' => 'STREAM-DO-99',
            'issue_date' => '2026-05-01',
            'file_path' => $path,
            'original_name' => 'preview_test.pdf',
            'mime' => 'application/pdf',
            'size' => strlen($samplePdfContent),
            'uploaded_by' => $admin->id,
        ]);

        // Preview stream (inline)
        $previewResponse = $this->actingAs($admin)->get(route('admin.documents.preview', $document));
        $previewResponse->assertStatus(200);
        $previewResponse->assertHeader('Content-Type', 'application/pdf');
        $previewResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', $previewResponse->headers->get('Content-Disposition') ?? '');

        // Download stream (attachment)
        $downloadResponse = $this->actingAs($admin)->get(route('admin.documents.download', $document));
        $downloadResponse->assertStatus(200);
        $this->assertStringContainsString('attachment', $downloadResponse->headers->get('Content-Disposition') ?? '');
    }

    public function test_admin_can_edit_document_metadata(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        Storage::disk('private')->put('company_documents/edit_doc.pdf', '%PDF dummy');

        $document = CompanyDocument::create([
            'title' => 'Original Document Title',
            'category' => 'other',
            'reference_no' => 'ORIG-REF-001',
            'issue_date' => '2026-02-01',
            'expiry_date' => '2026-12-31',
            'notes' => 'Original notes',
            'file_path' => 'company_documents/edit_doc.pdf',
            'original_name' => 'edit_doc.pdf',
            'mime' => 'application/pdf',
            'size' => 512,
            'uploaded_by' => $admin->id,
        ]);

        $updatePayload = [
            'title' => 'Updated Document Title',
            'category' => 'ssm_certificate',
            'reference_no' => 'UPD-REF-999',
            'issue_date' => '2026-02-01',
            'expiry_date' => '2027-02-01',
            'notes' => 'Updated revision notes.',
        ];

        $response = $this->actingAs($admin)->put(route('admin.documents.update', $document), $updatePayload);
        $response->assertRedirect(route('admin.documents.show', $document));

        $document->refresh();
        $this->assertEquals('Updated Document Title', $document->title);
        $this->assertEquals('ssm_certificate', $document->category);
        $this->assertEquals('UPD-REF-999', $document->reference_no);
        $this->assertEquals('Updated revision notes.', $document->notes);
    }

    public function test_admin_can_replace_document_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $oldPath = 'company_documents/old_version.pdf';
        Storage::disk('private')->put($oldPath, '%PDF old version content');

        $document = CompanyDocument::create([
            'title' => 'Replaceable Policy Document',
            'category' => 'other',
            'reference_no' => 'REP-POL-01',
            'issue_date' => '2026-01-01',
            'file_path' => $oldPath,
            'original_name' => 'old_version.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $admin->id,
        ]);

        Storage::disk('private')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->create('new_revised_policy.png', 400, 'image/png');

        $response = $this->actingAs($admin)->post(route('admin.documents.replace', $document), [
            'file' => $newFile,
        ]);

        $response->assertRedirect(route('admin.documents.show', $document));

        $document->refresh();
        $this->assertEquals('new_revised_policy.png', $document->original_name);
        $this->assertEquals('image/png', $document->mime);

        // Previous file deleted from private storage
        Storage::disk('private')->assertMissing($oldPath);
        // New file exists on private storage
        Storage::disk('private')->assertExists($document->file_path);
    }

    public function test_admin_can_delete_document_soft_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $document = CompanyDocument::create([
            'title' => 'Document To Delete',
            'category' => 'other',
            'reference_no' => 'DEL-REF-01',
            'issue_date' => '2026-01-01',
            'file_path' => 'company_documents/del.pdf',
            'original_name' => 'del.pdf',
            'mime' => 'application/pdf',
            'size' => 500,
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.documents.destroy', $document));
        $response->assertRedirect(route('admin.documents.index'));

        $this->assertSoftDeleted('company_documents', ['id' => $document->id]);
    }

    public function test_staff_with_permission_has_read_only_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['documents.view'],
            'status' => 'active',
        ]);

        $path = 'company_documents/staff_read.pdf';
        Storage::disk('private')->put($path, '%PDF content');

        $document = CompanyDocument::create([
            'title' => 'Staff Viewable DO',
            'category' => 'delivery_order',
            'reference_no' => 'STAFF-DO-001',
            'issue_date' => '2026-03-01',
            'file_path' => $path,
            'original_name' => 'staff_read.pdf',
            'mime' => 'application/pdf',
            'size' => 800,
            'uploaded_by' => $admin->id,
        ]);

        // Staff can list
        $this->actingAs($staff)->get(route('admin.documents.index'))->assertStatus(200);

        // Staff can view details
        $this->actingAs($staff)->get(route('admin.documents.show', $document))->assertStatus(200);

        // Staff can preview
        $this->actingAs($staff)->get(route('admin.documents.preview', $document))->assertStatus(200);

        // Staff can download
        $this->actingAs($staff)->get(route('admin.documents.download', $document))->assertStatus(200);
    }

    public function test_staff_cannot_perform_write_operations(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['documents.view'],
            'status' => 'active',
        ]);

        $document = CompanyDocument::create([
            'title' => 'Protected Document',
            'category' => 'ssm_certificate',
            'reference_no' => 'PROT-001',
            'issue_date' => '2026-03-01',
            'file_path' => 'company_documents/prot.pdf',
            'original_name' => 'prot.pdf',
            'mime' => 'application/pdf',
            'size' => 500,
            'uploaded_by' => $admin->id,
        ]);

        // Attempt upload -> 403
        $fakePdf = UploadedFile::fake()->create('illegal.pdf', 100, 'application/pdf');
        $this->actingAs($staff)->post(route('admin.documents.store'), [
            'title' => 'Illegal Upload',
            'category' => 'other',
            'reference_no' => 'ILLEGAL-01',
            'issue_date' => '2026-03-01',
            'file' => $fakePdf,
        ])->assertStatus(403);

        // Attempt update -> 403
        $this->actingAs($staff)->put(route('admin.documents.update', $document), [
            'title' => 'Tampered Title',
            'category' => 'ssm_certificate',
            'reference_no' => 'PROT-001',
            'issue_date' => '2026-03-01',
        ])->assertStatus(403);

        // Attempt replace file -> 403
        $this->actingAs($staff)->post(route('admin.documents.replace', $document), [
            'file' => $fakePdf,
        ])->assertStatus(403);

        // Attempt delete -> 403
        $this->actingAs($staff)->delete(route('admin.documents.destroy', $document))->assertStatus(403);

        $this->assertDatabaseHas('company_documents', [
            'id' => $document->id,
            'title' => 'Protected Document',
            'deleted_at' => null,
        ]);
    }

    public function test_staff_without_permission_receives_403(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => [], // no documents.view
            'status' => 'active',
        ]);

        $this->actingAs($staff)->get(route('admin.documents.index'))->assertStatus(403);
    }

    public function test_corporate_and_individual_users_receive_403(): void
    {
        $corporate = User::factory()->create(['role' => 'corporate', 'status' => 'active']);
        $individual = User::factory()->create(['role' => 'individual', 'status' => 'active']);

        // Corporate user blocked
        $this->actingAs($corporate)->get(route('admin.documents.index'))->assertStatus(403);
        $this->actingAs($corporate)->post(route('admin.documents.store'), [])->assertStatus(403);

        // Individual user blocked
        $this->actingAs($individual)->get(route('admin.documents.index'))->assertStatus(403);
        $this->actingAs($individual)->post(route('admin.documents.store'), [])->assertStatus(403);
    }

    public function test_expiry_reminder_artisan_command_notifies_admins_at_milestones(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // 30 days milestone
        $doc30 = CompanyDocument::create([
            'title' => 'Milestone 30 Days Doc',
            'category' => 'other',
            'reference_no' => 'M30-TEST',
            'issue_date' => Carbon::today()->subMonths(11),
            'expiry_date' => Carbon::today()->addDays(30),
            'file_path' => 'company_documents/m30.pdf',
            'original_name' => 'm30.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'uploaded_by' => $admin->id,
        ]);

        // 14 days milestone
        $doc14 = CompanyDocument::create([
            'title' => 'Milestone 14 Days Doc',
            'category' => 'ssm_certificate',
            'reference_no' => 'M14-TEST',
            'issue_date' => Carbon::today()->subMonths(11),
            'expiry_date' => Carbon::today()->addDays(14),
            'file_path' => 'company_documents/m14.pdf',
            'original_name' => 'm14.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'uploaded_by' => $admin->id,
        ]);

        // 7 days milestone
        $doc7 = CompanyDocument::create([
            'title' => 'Milestone 7 Days Doc',
            'category' => 'other',
            'reference_no' => 'M7-TEST',
            'issue_date' => Carbon::today()->subMonths(11),
            'expiry_date' => Carbon::today()->addDays(7),
            'file_path' => 'company_documents/m7.pdf',
            'original_name' => 'm7.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'uploaded_by' => $admin->id,
        ]);

        // 0 days milestone (expires today)
        $doc0 = CompanyDocument::create([
            'title' => 'Milestone Today Doc',
            'category' => 'delivery_order',
            'reference_no' => 'M0-TEST',
            'issue_date' => Carbon::today()->subMonths(12),
            'expiry_date' => Carbon::today(),
            'file_path' => 'company_documents/m0.pdf',
            'original_name' => 'm0.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'uploaded_by' => $admin->id,
        ]);

        // Non-milestone (e.g. 25 days) should not trigger notification
        $doc25 = CompanyDocument::create([
            'title' => 'Non Milestone Doc',
            'category' => 'other',
            'reference_no' => 'M25-TEST',
            'issue_date' => Carbon::today()->subMonths(11),
            'expiry_date' => Carbon::today()->addDays(25),
            'file_path' => 'company_documents/m25.pdf',
            'original_name' => 'm25.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'uploaded_by' => $admin->id,
        ]);

        $exitCode = Artisan::call('documents:check-expiry');
        $this->assertEquals(0, $exitCode);

        // Assert notifications dispatched for 30d, 14d, 7d, and today
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'url' => "/admin/documents/{$doc30->id}",
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'url' => "/admin/documents/{$doc14->id}",
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'url' => "/admin/documents/{$doc7->id}",
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'url' => "/admin/documents/{$doc0->id}",
        ]);

        // Assert non-milestone did not get notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $admin->id,
            'url' => "/admin/documents/{$doc25->id}",
        ]);
    }

    public function test_expiry_reminder_artisan_command_is_idempotent(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $doc = CompanyDocument::create([
            'title' => 'Idempotency Test SSM Doc',
            'category' => 'ssm_certificate',
            'reference_no' => 'IDEMP-SSM-01',
            'issue_date' => Carbon::today()->subMonths(11),
            'expiry_date' => Carbon::today()->addDays(14),
            'file_path' => 'company_documents/idemp.pdf',
            'original_name' => 'idemp.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'uploaded_by' => $admin->id,
        ]);

        // First run
        Artisan::call('documents:check-expiry');
        $initialCount = Notification::where('user_id', $admin->id)
            ->where('url', "/admin/documents/{$doc->id}")
            ->count();
        $this->assertEquals(1, $initialCount);

        // Second run on the same day/milestone
        Artisan::call('documents:check-expiry');
        $afterCount = Notification::where('user_id', $admin->id)
            ->where('url', "/admin/documents/{$doc->id}")
            ->count();
        $this->assertEquals(1, $afterCount); // Must still be exactly 1
    }
}
