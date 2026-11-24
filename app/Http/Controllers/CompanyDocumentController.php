<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplaceDocumentFileRequest;
use App\Http\Requests\StoreCompanyDocumentRequest;
use App\Http\Requests\UpdateCompanyDocumentRequest;
use App\Models\CompanyDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyDocumentController extends Controller
{
    /**
     * Display a listing of company documents.
     */
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->value();
        $category = $request->string('category')->trim()->value();
        $expiryStatus = $request->string('expiry_status')->trim()->value();

        // Calculate status counts across all active records
        $totalCount = CompanyDocument::count();
        $expiringSoonCount = CompanyDocument::expiringSoon(30)->count();
        $expiredCount = CompanyDocument::expired()->count();
        $validCount = CompanyDocument::valid()->count();

        $documents = CompanyDocument::with('uploader')
            ->search($search)
            ->category($category)
            ->expiryStatus($expiryStatus)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, created_at DESC')
            ->paginate(15)
            ->withQueryString();

        return view('admin.documents.index', compact(
            'documents',
            'totalCount',
            'expiringSoonCount',
            'expiredCount',
            'validCount',
            'search',
            'category',
            'expiryStatus'
        ));
    }

    /**
     * Display document details.
     */
    public function show(CompanyDocument $document)
    {
        $document->load('uploader');

        return view('admin.documents.show', compact('document'));
    }

    /**
     * Store a newly created document on the private disk.
     */
    public function store(StoreCompanyDocumentRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $storedPath = $file->store('company_documents', 'private');

        $document = CompanyDocument::create([
            'title' => $request->validated('title'),
            'category' => $request->validated('category'),
            'reference_no' => $request->validated('reference_no'),
            'issue_date' => $request->validated('issue_date'),
            'expiry_date' => $request->validated('expiry_date'),
            'notes' => $request->validated('notes'),
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('admin.documents.show', $document)
            ->with('success', 'Document uploaded successfully to the secure storage hub.');
    }

    /**
     * Update the specified document's metadata.
     */
    public function update(UpdateCompanyDocumentRequest $request, CompanyDocument $document): RedirectResponse
    {
        $document->update($request->validated());

        return redirect()->route('admin.documents.show', $document)
            ->with('success', 'Document metadata updated successfully.');
    }

    /**
     * Replace the physical document file on the private disk.
     */
    public function replaceFile(ReplaceDocumentFileRequest $request, CompanyDocument $document): RedirectResponse
    {
        $file = $request->file('file');

        // Delete previous file from private disk if it exists
        if ($document->file_path && Storage::disk('private')->exists($document->file_path)) {
            Storage::disk('private')->delete($document->file_path);
        }

        $newPath = $file->store('company_documents', 'private');

        $document->update([
            'file_path' => $newPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('admin.documents.show', $document)
            ->with('success', 'Document file replaced successfully.');
    }

    /**
     * Remove the specified document from storage (soft delete).
     */
    public function destroy(CompanyDocument $document): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Unauthorized.');

        $document->delete();

        return redirect()->route('admin.documents.index')
            ->with('success', 'Document moved to trash successfully.');
    }

    /**
     * Stream the document file inline for in-browser preview.
     */
    public function preview(CompanyDocument $document)
    {
        if (! Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found on secure disk.');
        }

        return Storage::disk('private')->response(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime,
                'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Download the document file as an attachment.
     */
    public function download(CompanyDocument $document): StreamedResponse
    {
        if (! Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found on secure disk.');
        }

        return Storage::disk('private')->download(
            $document->file_path,
            $document->original_name,
            [
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
