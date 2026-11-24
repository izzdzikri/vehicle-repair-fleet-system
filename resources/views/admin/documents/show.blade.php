@extends('layouts.app')

@section('page-title', $document->title)

@section('content')
<div x-data="{ editModalOpen: false, replaceModalOpen: false }" class="p-4 sm:p-6 lg:p-8 space-y-6">

    {{-- Breadcrumb & Top Navigation --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.documents.index') }}"
                class="p-2 bg-white border border-gray-200 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-50 transition shadow-xs">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">{{ $document->title }}</h1>
                    @php
                        $status = $document->expiry_status;
                        $badgeConfig = match($status) {
                            'expired' => ['class' => 'bg-rose-50 text-rose-700 border-rose-200', 'dot' => 'bg-rose-500', 'label' => 'Expired'],
                            'expiring_soon' => ['class' => 'bg-amber-50 text-amber-700 border-amber-200', 'dot' => 'bg-amber-500', 'label' => 'Expiring Soon'],
                            default => ['class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500', 'label' => 'Valid'],
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeConfig['class'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $badgeConfig['dot'] }}"></span>
                        {{ $badgeConfig['label'] }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 font-mono mt-0.5">Reference: {{ $document->reference_no }}</p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Download --}}
            <a href="{{ route('admin.documents.download', $document) }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-xs">
                <i data-lucide="download" class="w-4 h-4 text-emerald-600"></i>
                Download
            </a>

            {{-- External Preview --}}
            <a href="{{ route('admin.documents.preview', $document) }}" target="_blank"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-xs">
                <i data-lucide="external-link" class="w-4 h-4 text-blue-600"></i>
                New Tab
            </a>

            @if(auth()->user()->isAdmin())
            {{-- Edit Metadata --}}
            <button type="button" @click="editModalOpen = true"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-xs">
                <i data-lucide="edit-3" class="w-4 h-4 text-gray-500"></i>
                Edit
            </button>

            {{-- Replace File --}}
            <button type="button" @click="replaceModalOpen = true"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-xs">
                <i data-lucide="refresh-cw" class="w-4 h-4 text-indigo-600"></i>
                Replace File
            </button>

            {{-- Delete --}}
            <form method="POST" action="{{ route('admin.documents.destroy', $document) }}"
                onsubmit="return confirmSubmit(event, {title:'Delete Document?', message:'This will move {{ addslashes($document->title) }} to trash.', confirmLabel:'Delete Document'})">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 border border-rose-200 rounded-xl text-xs font-semibold text-rose-700 hover:bg-rose-100 transition shadow-xs">
                    <i data-lucide="trash-2" class="w-4 h-4 text-rose-600"></i>
                    Delete
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Expiry Alert Banner if Expiring Soon or Expired --}}
    @if($document->expiry_status === 'expiring_soon')
    <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
        </div>
        <div>
            <h4 class="text-sm font-bold text-amber-900">Document Expiring Soon</h4>
            <p class="text-xs text-amber-800 mt-0.5">
                This document is scheduled to expire on <strong>{{ $document->expiry_date->format('d M Y') }}</strong> ({{ $document->expiry_status_label }}). Please initiate renewal procedures with the issuing agency to prevent compliance penalties.
            </p>
        </div>
    </div>
    @elseif($document->expiry_status === 'expired')
    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
            <i data-lucide="x-circle" class="w-4 h-4"></i>
        </div>
        <div>
            <h4 class="text-sm font-bold text-rose-900">Document Expired</h4>
            <p class="text-xs text-rose-800 mt-0.5">
                This document expired on <strong>{{ $document->expiry_date->format('d M Y') }}</strong> ({{ $document->expiry_status_label }}). Immediate renewal is required.
            </p>
        </div>
    </div>
    @endif

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Metadata Cards --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Document Information --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400">Document Information</h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-gray-400 block text-[11px]">Title</span>
                        <span class="font-bold text-gray-900 text-sm">{{ $document->title }}</span>
                    </div>

                    <div>
                        <span class="text-gray-400 block text-[11px]">Category</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-gray-100 text-gray-800 text-xs mt-0.5">
                            {{ $document->category_label }}
                        </span>
                    </div>

                    <div>
                        <span class="text-gray-400 block text-[11px]">Reference Number</span>
                        <span class="font-mono font-semibold text-gray-900">{{ $document->reference_no }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-gray-400 block text-[11px]">Issue Date</span>
                            <span class="font-medium text-gray-800">{{ $document->issue_date->format('d M Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[11px]">Expiry Date</span>
                            <span class="font-medium text-gray-800">
                                {{ $document->expiry_date ? $document->expiry_date->format('d M Y') : 'None (Perpetual)' }}
                            </span>
                        </div>
                    </div>

                    @if($document->notes)
                    <div class="pt-2 border-t border-gray-100">
                        <span class="text-gray-400 block text-[11px]">Notes</span>
                        <p class="text-gray-700 mt-1 whitespace-pre-wrap leading-relaxed">{{ $document->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- File Security & Audit Information --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400">File & Security</h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-gray-400 block text-[11px]">Original Filename</span>
                        <span class="font-mono text-gray-800 break-all">{{ $document->original_name }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-gray-400 block text-[11px]">MIME Type</span>
                            <span class="font-mono text-[11px] text-gray-700">{{ $document->mime }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[11px]">File Size</span>
                            <span class="font-medium text-gray-800">{{ $document->formatted_size }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-400 block text-[11px]">Storage Status</span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 mt-0.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            Private Disk (No Public URL)
                        </span>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <span class="text-gray-400 block text-[11px]">Uploaded By</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="font-semibold text-gray-900">{{ $document->uploader->name ?? 'System' }}</span>
                            <span class="text-gray-400 text-[11px]">({{ $document->created_at->format('d M Y, h:i A') }})</span>
                        </div>
                    </div>

                    @if($document->updated_at->ne($document->created_at))
                    <div>
                        <span class="text-gray-400 block text-[11px]">Last Updated</span>
                        <span class="text-gray-600">{{ $document->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Integrated Document Previewer --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 flex flex-col h-full min-h-[600px]">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="file-check-2" class="w-4 h-4 text-blue-600"></i>
                        <h3 class="text-sm font-bold text-gray-900">Document Stream Preview</h3>
                    </div>
                    <span class="text-[11px] text-gray-400 font-mono">Streamed via Authorized Controller</span>
                </div>

                <div class="flex-1 bg-gray-50 rounded-xl border border-gray-200/70 overflow-hidden flex items-center justify-center p-2 relative">
                    @if($document->isPdf())
                        <iframe src="{{ route('admin.documents.preview', $document) }}"
                            class="w-full h-full min-h-[550px] rounded-lg border-0"
                            title="{{ $document->title }}">
                        </iframe>
                    @elseif($document->isImage())
                        <div class="p-4 flex items-center justify-center w-full h-full">
                            <img src="{{ route('admin.documents.preview', $document) }}"
                                alt="{{ $document->title }}"
                                class="max-h-[550px] max-w-full rounded-lg object-contain shadow-xs">
                        </div>
                    @else
                        <div class="text-center p-8">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3 text-gray-400">
                                <i data-lucide="file" class="w-6 h-6"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700">{{ $document->original_name }}</p>
                            <p class="text-xs text-gray-400 mt-1">Direct preview is not available for this file type.</p>
                            <a href="{{ route('admin.documents.download', $document) }}"
                                class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                Download File
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Edit Metadata Modal (Admin Only) --}}
    @if(auth()->user()->isAdmin())
    <div x-show="editModalOpen" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto"
        style="display:none">
        <div @click.outside="editModalOpen = false"
            class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-xl w-full p-6 text-left my-8">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Edit Document Metadata</h3>
                        <p class="text-xs text-gray-500">Update title, category, reference number, or dates.</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.documents.update', $document) }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Document Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required value="{{ old('title', $document->title) }}"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>

                {{-- Category and Ref No --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category" required
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                            @foreach(\App\Models\CompanyDocument::CATEGORIES as $key => $label)
                                <option value="{{ $key }}" {{ old('category', $document->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Reference Number <span class="text-red-500">*</span></label>
                        <input type="text" name="reference_no" required value="{{ old('reference_no', $document->reference_no) }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none font-mono">
                    </div>
                </div>

                {{-- Issue Date and Expiry Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Issue Date <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" required value="{{ old('issue_date', $document->issue_date->format('Y-m-d')) }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Expiry Date <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date', $document->expiry_date?->format('Y-m-d')) }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Notes / Description <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <textarea name="notes" rows="2"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">{{ old('notes', $document->notes) }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="editModalOpen = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Replace File Modal (Admin Only) --}}
    <div x-show="replaceModalOpen" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto"
        style="display:none">
        <div @click.outside="replaceModalOpen = false"
            class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-md w-full p-6 text-left my-8">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Replace Document File</h3>
                        <p class="text-xs text-gray-500">Upload a new revision for this document.</p>
                    </div>
                </div>
                <button type="button" @click="replaceModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.documents.replace', $document) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">New File Attachment <span class="text-red-500">*</span></label>
                    <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer border border-gray-200 rounded-xl p-1 bg-gray-50">
                    <p class="text-[11px] text-gray-400 mt-1">Accepted: PDF, JPG, PNG. Max: 5 MB. Replacing the file deletes the previous attachment from the private disk.</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="replaceModalOpen = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        Confirm & Replace
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
