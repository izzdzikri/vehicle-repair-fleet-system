@extends('layouts.app')

@section('page-title', 'Document Management Hub')

@section('content')
<div x-data="{ uploadModalOpen: false }" class="p-4 sm:p-6 lg:p-8 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Document Management Hub</h1>
            <p class="text-sm text-gray-500 mt-1">
                Centralized secure repository for SSM certificates, delivery orders, licenses, and company records.
            </p>
        </div>
        @if(auth()->user()->isAdmin())
        <div class="flex items-center gap-2">
            <button type="button" @click="uploadModalOpen = true"
                class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-xs transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                Upload Document
            </button>
        </div>
        @endif
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total --}}
        <a href="{{ route('admin.documents.index') }}"
            class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-200/80 shadow-xs hover:border-gray-300 transition block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Documents</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="files" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-black text-gray-900 mt-2">{{ $totalCount }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Stored on secure private disk</p>
        </a>

        {{-- Valid --}}
        <a href="{{ route('admin.documents.index', ['expiry_status' => 'valid']) }}"
            class="bg-white rounded-2xl p-4 sm:p-5 border {{ request('expiry_status') === 'valid' ? 'border-emerald-500 ring-2 ring-emerald-100' : 'border-gray-200/80 hover:border-emerald-300' }} shadow-xs transition block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Valid</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-black text-emerald-700 mt-2">{{ $validCount }}</p>
            <p class="text-[11px] text-emerald-600/80 mt-0.5">Compliant & active</p>
        </a>

        {{-- Expiring Soon --}}
        <a href="{{ route('admin.documents.index', ['expiry_status' => 'expiring_soon']) }}"
            class="bg-white rounded-2xl p-4 sm:p-5 border {{ request('expiry_status') === 'expiring_soon' ? 'border-amber-500 ring-2 ring-amber-100' : 'border-gray-200/80 hover:border-amber-300' }} shadow-xs transition block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Expiring Soon</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-black text-amber-700 mt-2">{{ $expiringSoonCount }}</p>
            <p class="text-[11px] text-amber-600/80 mt-0.5">Within 30 days window</p>
        </a>

        {{-- Expired --}}
        <a href="{{ route('admin.documents.index', ['expiry_status' => 'expired']) }}"
            class="bg-white rounded-2xl p-4 sm:p-5 border {{ request('expiry_status') === 'expired' ? 'border-rose-500 ring-2 ring-rose-100' : 'border-gray-200/80 hover:border-rose-300' }} shadow-xs transition block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Expired</span>
                <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-black text-rose-700 mt-2">{{ $expiredCount }}</p>
            <p class="text-[11px] text-rose-600/80 mt-0.5">Requires immediate renewal</p>
        </a>
    </div>

    {{-- Filter and Search Bar --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-4 sm:p-5">
        <form method="GET" action="{{ route('admin.documents.index') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
            {{-- Search --}}
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search by title, reference number, or filename..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:outline-none transition">
            </div>

            {{-- Category Filter --}}
            <div class="w-full lg:w-48">
                <select name="category" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-700 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                    <option value="">All Categories</option>
                    @foreach(\App\Models\CompanyDocument::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Expiry Status Filter --}}
            <div class="w-full lg:w-44">
                <select name="expiry_status" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-700 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                    <option value="">All Expiry Statuses</option>
                    <option value="valid" {{ $expiryStatus === 'valid' ? 'selected' : '' }}>Valid</option>
                    <option value="expiring_soon" {{ $expiryStatus === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (30d)</option>
                    <option value="expired" {{ $expiryStatus === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center gap-2 shrink-0">
                <button type="submit"
                    class="px-4 py-2 bg-gray-900 text-white rounded-xl text-xs font-semibold hover:bg-gray-800 transition">
                    Filter
                </button>
                @if($search || $category || $expiryStatus)
                <a href="{{ route('admin.documents.index') }}"
                    class="px-3 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-semibold hover:bg-gray-200 transition">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Documents Table / Empty State --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        @if($documents->isEmpty())
            <div class="p-8">
                <x-empty-state
                    icon="file-text"
                    title="No company documents found"
                    subtitle="{{ $search || $category || $expiryStatus ? 'Try adjusting your filters or search keywords.' : 'Get started by uploading company SSM certificates, delivery orders, or licenses.' }}"
                    :actionHref="auth()->user()->isAdmin() ? '#' : null"
                    :actionLabel="auth()->user()->isAdmin() ? 'Upload First Document' : null"
                />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 uppercase text-[10px] font-bold tracking-wider">
                            <th class="py-3 px-4 sm:px-6">Document Details</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Reference No</th>
                            <th class="py-3 px-4">Issue & Expiry</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-normal text-gray-700">
                        @foreach($documents as $doc)
                            <tr class="hover:bg-gray-50/60 transition group">
                                {{-- Title & File --}}
                                <td class="py-3.5 px-4 sm:px-6">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5 {{ $doc->isPdf() ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">
                                            @if($doc->isPdf())
                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                            @else
                                                <i data-lucide="image" class="w-4 h-4"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.documents.show', $doc) }}" class="font-bold text-gray-900 hover:text-blue-600 transition block truncate">
                                                {{ $doc->title }}
                                            </a>
                                            <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-0.5">
                                                <span class="truncate max-w-[200px]" title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                                                <span>•</span>
                                                <span>{{ $doc->formatted_size }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @php
                                        $catColor = match($doc->category) {
                                            'ssm_certificate' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'delivery_order' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            default => 'bg-gray-100 text-gray-700 border-gray-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $catColor }}">
                                        {{ $doc->category_label }}
                                    </span>
                                </td>

                                {{-- Ref No --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-mono text-xs font-semibold text-gray-700 bg-gray-50 px-2 py-0.5 rounded border border-gray-200">
                                        {{ $doc->reference_no }}
                                    </span>
                                </td>

                                {{-- Dates --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="text-xs">
                                        <p class="text-gray-900 font-medium">
                                            {{ $doc->expiry_date ? $doc->expiry_date->format('d M Y') : 'No Expiry' }}
                                        </p>
                                        <p class="text-[11px] text-gray-400">
                                            Issued: {{ $doc->issue_date->format('d M Y') }}
                                        </p>
                                    </div>
                                </td>

                                {{-- Expiry Status Badge --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @php
                                        $status = $doc->expiry_status;
                                        $badgeConfig = match($status) {
                                            'expired' => [
                                                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'dot' => 'bg-rose-500',
                                                'label' => 'Expired',
                                            ],
                                            'expiring_soon' => [
                                                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'dot' => 'bg-amber-500',
                                                'label' => 'Expiring Soon',
                                            ],
                                            default => [
                                                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'dot' => 'bg-emerald-500',
                                                'label' => 'Valid',
                                            ],
                                        };
                                    @endphp
                                    <div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeConfig['class'] }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $badgeConfig['dot'] }}"></span>
                                            {{ $badgeConfig['label'] }}
                                        </span>
                                        @if($doc->expiry_date)
                                            <p class="text-[10px] text-gray-500 mt-0.5">
                                                {{ $doc->expiry_status_label }}
                                            </p>
                                        @endif
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- View Details --}}
                                        <a href="{{ route('admin.documents.show', $doc) }}"
                                            class="p-1.5 text-gray-500 hover:text-slate-900 hover:bg-gray-100 rounded-lg transition"
                                            title="View Details">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>

                                        {{-- Preview --}}
                                        <a href="{{ route('admin.documents.preview', $doc) }}" target="_blank"
                                            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Preview File in Browser">
                                            <i data-lucide="external-link" class="w-4 h-4"></i>
                                        </a>

                                        {{-- Download --}}
                                        <a href="{{ route('admin.documents.download', $doc) }}"
                                            class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                            title="Download File">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </a>

                                        {{-- Delete (Admin Only) --}}
                                        @if(auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}"
                                            onsubmit="return confirmSubmit(event, {title:'Delete Document?', message:'This will move {{ addslashes($doc->title) }} (Ref: {{ addslashes($doc->reference_no) }}) to trash. This action is reversible.', confirmLabel:'Delete Document'})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                title="Delete Document">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($documents->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $documents->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Upload Document Modal (Admin Only) --}}
    @if(auth()->user()->isAdmin())
    <div x-show="uploadModalOpen" x-transition.opacity
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto"
        style="display:none">
        <div @click.outside="uploadModalOpen = false"
            class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-xl w-full p-6 text-left my-8">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="file-plus" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Upload Company Document</h3>
                        <p class="text-xs text-gray-500">Files are stored securely on a private disk.</p>
                    </div>
                </div>
                <button type="button" @click="uploadModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Document Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. SSM Certificate of Incorporation (Form 9)"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>

                {{-- Category and Ref No --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category" required
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                            @foreach(\App\Models\CompanyDocument::CATEGORIES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Reference Number <span class="text-red-500">*</span></label>
                        <input type="text" name="reference_no" required placeholder="e.g. 202301048291"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none font-mono">
                    </div>
                </div>

                {{-- Issue Date and Expiry Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Issue Date <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" required value="{{ date('Y-m-d') }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Expiry Date <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <input type="date" name="expiry_date"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none">
                        <p class="text-[10px] text-gray-400 mt-0.5">Leave blank if perpetual/non-expiring.</p>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Notes / Description <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <textarea name="notes" rows="2" placeholder="Additional notes, issuing authority, remarks..."
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:outline-none"></textarea>
                </div>

                {{-- File Input --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">File Attachment <span class="text-red-500">*</span></label>
                    <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer border border-gray-200 rounded-xl p-1 bg-gray-50">
                    <p class="text-[11px] text-gray-400 mt-1">Accepted: PDF, JPG, PNG. Max size: 5 MB. Strictly verified by real MIME type.</p>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="uploadModalOpen = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        Upload to Hub
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
