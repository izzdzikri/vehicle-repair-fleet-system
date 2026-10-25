{{-- Floating AI Assistant Chatbot Partial --}}
<script>
function chatbotComponent() {
    return {
        open: false,
        showTeaser: false,
        isFullScreen: false,
        lang: '{{ session('chatbot_lang', 'en') }}',
        scheduleDays: [],
        calendarLoading: false,
        selectedDay: null,
        selectedSlot: null,
        messages: [
            {
                role: 'bot',
                text: @auth
                    @if(auth()->user()->role === 'staff')
                        'Hello **{{ auth()->user()->name }}** (Workshop Technician / Mechanic)! 🛠️\nI can help you review assigned job cards (\'Had job left?\'), check inventory spare parts stock, or search repair invoices.\n\n───────────────\n\nHai **{{ auth()->user()->name }}** (Mekanik Bengkel)! 🛠️\nSaya sedia bantu semak baki tugasan kerja anda (\'Had job left?\' / \'Kerja saya\'), cari stok alat ganti di stor, atau semak rekod invois pembaikan.'
                    @elseif(auth()->user()->role === 'admin')
                        'Welcome **{{ auth()->user()->name }}** (Workshop Administrator)! ⚙️\nAsk me for daily appointment reports (\'Status on today appointment\'), workshop bay tracking, low stock replenishment alerts, or customer invoices.\n\n───────────────\n\nSelamat datang **{{ auth()->user()->name }}** (Admin Bengkel)! ⚙️\nAnda boleh meminta laporan harian temujanji (\'Status on today appointment\'), semak lantai kerja kenderaan, amaran stok rendah/PO, atau cari rekod invois pelanggan.'
                    @elseif(auth()->user()->role === 'coordinator')
                        'Hello **{{ auth()->user()->name }}** (Service Reception Coordinator)! 📋\nI can assist with today\'s appointment arrivals, workshop bay allocations, or searching customer records.\n\n───────────────\n\nHai **{{ auth()->user()->name }}** (Penyelaras Servis)! 📋\nSaya sedia bantu semak laporan temujanji hari ini (\'Status on today appointment\'), kekosongan slot kalendar bengkel, atau cari invois & rekod pelanggan.'
                    @elseif(auth()->user()->role === 'corporate')
                        'Welcome **{{ auth()->user()->name }}** (Fleet Manager{{ auth()->user()->company ? ' - ' . auth()->user()->company->name : '' }})! 🚛\nI can help you review commercial fleet maintenance, check outstanding invoices, or schedule recurring fleet servicing.\n\n───────────────\n\nSelamat datang **{{ auth()->user()->name }}** (Pengurus Armada{{ auth()->user()->company ? ' - ' . auth()->user()->company->name : '' }})! 🚛\nSaya sedia bantu jadual penyelenggaraan lori/van syarikat anda, semak invois tertunggak, atau tempah servis berkala armada.'
                    @elseif(auth()->user()->role === 'individual')
                        'Hello **{{ auth()->user()->name }}**! Welcome to Teraju Setia Enterprise. 🚗\nYou can view your registered vehicles, request service quotes, review invoices, or book your next service slot.\n\n───────────────\n\nHai **{{ auth()->user()->name }}**! Selamat datang ke Teraju Setia Enterprise. 🚗\nAnda boleh semak kenderaan berdaftar anda, tanya anggaran harga upah, semak rekod invois, atau tempah temujanji servis terus di sini!'
                    @else
                        'Hello **{{ auth()->user()->name }}**! Welcome to Teraju Setia Enterprise. How may I assist you today?\n\n───────────────\n\nHai **{{ auth()->user()->name }}**! Selamat datang ke Teraju Setia Enterprise. Bagaimana saya boleh bantu anda hari ini?'
                    @endif
                @else
                    'Hello & Welcome to Teraju Setia Enterprise (Kluang, Johor)! 🚗💨\nI am your workshop AI assistant.\n\nYou can ask about service pricing (e.g. *\'How much for oil change & brake service?\'*), check spare parts stock, or book an appointment directly right here — no registration required!\n\n───────────────\n\nHai & Selamat datang ke Teraju Setia Enterprise (Kluang, Johor)! 🚗💨\nSaya pembantu AI bengkel anda.\n\nAnda boleh bertanya tentang kadar upah servis (*\'Berapa harga servis minyak hitam & brek?\'*), semak stok alat ganti, atau tempah temujanji servis kenderaan terus di sini — tanpa perlu mendaftar!'
                @endauth
            }
        ],
        input: '',
        loading: false,

        async init() {
            this.checkTeaserVisibility();
            this.fetchSchedule();
        },

        checkTeaserVisibility() {
            try {
                @if(request()->routeIs('login') || request()->is('login') || !auth()->check())
                    // On login page / guest landing: persistent bubble so guests notice AI assistant immediately
                    this.showTeaser = true;
                    return;
                @else
                    // On internal authenticated dashboards: display once every 30 days
                    const lastShown = localStorage.getItem('teraju_chatbot_teaser_last_shown');
                    if (!lastShown) {
                        this.showTeaser = true;
                        localStorage.setItem('teraju_chatbot_teaser_last_shown', Date.now().toString());
                        return;
                    }
                    const time = parseInt(lastShown, 10);
                    const now = Date.now();
                    const thirtyDaysMs = 30 * 24 * 60 * 60 * 1000; // 30 days
                    if (now - time > thirtyDaysMs) {
                        this.showTeaser = true;
                        localStorage.setItem('teraju_chatbot_teaser_last_shown', Date.now().toString());
                    } else {
                        this.showTeaser = false;
                    }
                @endif
            } catch(e) {
                this.showTeaser = false;
            }
        },

        dismissTeaser() {
            this.showTeaser = false;
            try {
                @auth
                    localStorage.setItem('teraju_chatbot_teaser_last_shown', Date.now().toString());
                @endauth
            } catch(e) {}
        },

        toggleFullScreen() {
            this.isFullScreen = !this.isFullScreen;
            this.$nextTick(() => {
                this.scrollToBottom();
                if (window.lucide) lucide.createIcons();
            });
        },

        async fetchSchedule() {
            if (this.scheduleDays && this.scheduleDays.length > 0) return;
            this.calendarLoading = true;
            try {
                const res = await fetch('/chatbot/schedule-availability');
                if (res.ok) {
                    const data = await res.json();
                    this.scheduleDays = data.days || [];
                }
            } catch (e) {
                console.error('Error fetching schedule availability:', e);
            }
            this.calendarLoading = false;
        },

        selectDate(day) {
            if (!day || !day.is_selectable) return;
            this.selectedDay = day;
            this.selectedSlot = null;
        },

        pickSlot(day, slot) {
            if (!slot || !slot.is_available) return;
            this.selectedSlot = slot;
            const prompt = this.lang === 'ms'
                ? `Saya pilih tarikh ${day.day_number}/${day.month} (${day.date}) jam ${slot.label}`
                : `I select date ${day.date} at ${slot.label}`;
            this.send(prompt);
        },

        async resetChat() {
            try {
                const csrfMeta = document.querySelector('meta[name=csrf-token]');
                const token = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';
                await fetch('/chatbot/reset-session', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    }
                });
            } catch(e) {}

            this.selectedDay = null;
            this.selectedSlot = null;
            this.messages = [{
                role: 'bot',
                text: 'Chat session has been reset. How may I assist you with your vehicle today? You can ask about services, spare parts, or schedule an appointment.\n\n───────────────\n\nSesi perbualan telah dimulakan semula. Bagaimana saya boleh bantu kenderaan anda hari ini? Anda boleh tanya mengenai servis, harga alat ganti, atau buat tempahan temujanji.'
            }];
            this.$nextTick(() => this.scrollToBottom());
        },

        async send(customText = null, action = null, payload = null) {
            const userMsg = (customText !== null ? customText : this.input).trim();
            if (!userMsg || this.loading) return;
            if (customText === null) this.input = '';

            this.messages.push({ role: 'user', text: userMsg });
            this.loading = true;
            this.$nextTick(() => {
                this.scrollToBottom();
                if (window.lucide) lucide.createIcons();
            });

            const csrfMeta = document.querySelector('meta[name=csrf-token]');
            const token = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';

            try {
                const reqBody = Object.assign({ message: userMsg, action: action }, payload || {});
                const res = await fetch('/chatbot/reply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(reqBody),
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Server error');
                }

                const data = await res.json();
                if (data && data.reply) {
                    if (data.lang) {
                        this.lang = data.lang;
                    }

                    if (data.show_calendar || data.action === 'select_slot') {
                        this.fetchSchedule();
                    }

                    this.messages.push({
                        role: 'bot',
                        text: data.reply,
                        action: data.action || null,
                        booking: data.booking || null,
                        service_options: data.service_options || null,
                        appointment_url: data.appointment_url || null,
                        show_calendar: data.show_calendar || (data.action === 'select_slot')
                    });
                } else {
                    throw new Error('Invalid reply format');
                }
            } catch(e) {
                console.error('Chatbot error:', e);
                const errMsg = this.lang === 'ms'
                    ? 'Maaf, ralat sambungan sementara berlaku. Sila cuba sebentar lagi atau hubungi bengkel Teraju Setia di talian 07-787 8728.'
                    : 'Sorry, a temporary connection error occurred. Please try again shortly or contact Teraju Setia workshop at 07-787 8728.';
                this.messages.push({
                    role: 'bot',
                    text: errMsg
                });
            }

            this.loading = false;
            this.$nextTick(() => {
                this.scrollToBottom();
                if (window.lucide) lucide.createIcons();
            });
        },

        pickServiceType(opt) {
            const serviceName = this.lang === 'ms' ? opt.name_ms : opt.name_en;
            this.send(serviceName, 'pick_service_type', { service_type: opt.name_en, symptoms: opt.symptoms });
        },

        confirmBooking() {
            const confirmMsg = this.lang === 'ms' ? 'Sahkan tempahan slot' : 'Confirm and proceed with booking';
            this.send(confirmMsg, 'confirm_booking');
        },

        cancelBooking() {
            const cancelMsg = this.lang === 'ms' ? 'Batal tempahan' : 'Cancel booking';
            this.send(cancelMsg, 'cancel_booking');
        },

        escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        formatMessage(text, role) {
            if (!text) return '';
            let html = this.escapeHtml(text);

            // Replace markdown links: [link text](url)
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, title, url) => {
                const linkClass = role === 'user'
                    ? 'text-blue-200 underline hover:text-white font-semibold'
                    : 'text-blue-600 underline hover:text-blue-800 font-semibold';
                return `<a href="${url}" class="${linkClass}">${title} &rarr;</a>`;
            });

            // Replace bold: **text**
            const boldClass = role === 'user' ? 'font-bold text-white' : 'font-semibold text-slate-900';
            html = html.replace(/\*\*(.*?)\*\*/g, `<strong class="${boldClass}">$1</strong>`);

            // Replace inline code: `code`
            const codeClass = role === 'user' ? 'bg-slate-800 text-blue-200 px-1 py-0.5 rounded text-[11px] font-mono' : 'bg-slate-100 text-slate-800 px-1 py-0.5 rounded text-[11px] font-mono';
            html = html.replace(/`([^`]+)`/g, `<code class="${codeClass}">$1</code>`);

            // Replace italics: *text* or _text_
            html = html.replace(/(?<!\w)\*([^\*\n]+)\*(?!\w)/g, '<em class="italic">$1</em>');
            html = html.replace(/(?<!\w)_([^_\n]+)_(?!\w)/g, '<em class="italic">$1</em>');

            // Clean up markdown bullet lists: * item or - item
            html = html.replace(/^[\*\-]\s+(.*)$/gim, '• $1');

            // Newlines to <br>
            html = html.replace(/\n/g, '<br>');

            return html;
        },

        scrollToBottom() {
            const el = this.$refs.chatBody;
            if (el) el.scrollTop = el.scrollHeight;
        }
    };
}
</script>

<div x-data="chatbotComponent()"
    @open-chatbot.window="open = true; dismissTeaser(); if ($event.detail && $event.detail.prompt) { send($event.detail.prompt); } $nextTick(() => scrollToBottom());"
    @keydown.escape.window="if(isFullScreen) isFullScreen = false"
    class="fixed bottom-4 right-4 z-50 no-print">

    {{-- Backdrop for Fullscreen Mode --}}
    <div x-show="open && isFullScreen" x-transition.opacity
        @click="isFullScreen = false"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40"
        style="display:none;"></div>

    {{-- Teaser Speech Bubble (Persistent on login page; throttled on internal dashboards) --}}
    <div x-show="!open && showTeaser" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute bottom-16 right-0 mb-1.5 p-3.5 bg-white rounded-2xl shadow-2xl border border-gray-200 text-xs text-gray-800 flex items-start gap-2.5 select-none"
        style="width: 290px; max-width: calc(100vw - 2rem); box-shadow: 0 14px 35px -5px rgba(15, 23, 42, 0.28), 0 0 0 1px rgba(0, 0, 0, 0.04); display: none;">

        {{-- Pointer triangle pointing toward the circular toggle button below --}}
        <div class="absolute -bottom-1.5 right-5 w-3.5 h-3.5 bg-white border-b border-r border-gray-200 transform rotate-45 pointer-events-none"></div>

        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mt-1 shrink-0 animate-pulse relative z-10"></span>

        <div class="flex-1 cursor-pointer relative z-10" @click="open = true; dismissTeaser(); $nextTick(() => scrollToBottom())">
            <p class="font-extrabold text-gray-900 text-xs flex items-center gap-1.5 leading-tight">
                <span>Teraju AI Workshop Assistant / Pembantu Bengkel</span>
            </p>
            <p class="text-[11px] text-gray-600 mt-1 leading-snug font-normal">
                Ask about repair rates, check schedule, or book a service slot right here!
                <span class="block mt-0.5 text-gray-500 italic">(Tanya kadar upah servis, semak kalendar, atau tempah servis kenderaan terus di sini!)</span>
            </p>
            <p class="text-[10px] text-blue-600 font-semibold mt-1.5 flex items-center gap-1">
                <span>Click to start chat / Klik untuk sembang &rarr;</span>
            </p>
        </div>

        <button type="button" @click.stop="dismissTeaser()"
            class="text-gray-400 hover:text-gray-700 -mr-1 -mt-1 p-1 rounded-md hover:bg-gray-100 transition relative z-10"
            :title="lang === 'ms' ? 'Tutup' : 'Close'">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    {{-- Chat Bubble Toggle Button (Hidden when chat is open in fullscreen mode) --}}
    <button x-show="!open || !isFullScreen"
        @click="open = !open; dismissTeaser(); $nextTick(() => { scrollToBottom(); if (window.lucide) lucide.createIcons(); })"
        class="relative w-12 h-12 sm:w-13 sm:h-13 rounded-full bg-slate-900 text-white shadow-xl
               flex items-center justify-center hover:bg-slate-800 transition transform hover:scale-105 focus:outline-none"
        style="box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.4);"
        title="Buka Pembantu AI Teraju Setia">
        
        <div x-show="!open" class="flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-600 border-2 border-white"></span>
            </span>
        </div>

        <svg x-show="open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>

    {{-- Chat Window Modal (Responsive Floating vs Full Screen Mode) --}}
    <div x-show="open" x-transition
        :class="isFullScreen
            ? 'fixed inset-2 sm:inset-5 md:inset-8 z-50 rounded-2xl shadow-2xl border border-slate-300'
            : 'absolute bottom-16 right-0 rounded-2xl shadow-2xl border border-gray-200'"
        :style="isFullScreen ? 'width: auto; height: auto;' : 'width: min(420px, calc(100vw - 1.5rem)); height: 570px;'"
        class="bg-white flex flex-col overflow-hidden text-gray-800 transition-all duration-200"
        style="display:none; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.25);">
        
        {{-- Header --}}
        <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center shrink-0 text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                        <circle cx="12" cy="5" r="2"></circle>
                        <path d="M12 7v4"></path>
                        <line x1="8" y1="16" x2="8" y2="16"></line>
                        <line x1="16" y1="16" x2="16" y2="16"></line>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-sm leading-tight text-white flex items-center gap-1.5">
                        <span>Teraju Workshop AI</span>
                        <span class="text-[10px] font-semibold uppercase px-1.5 py-0.2 rounded bg-slate-800 text-blue-300 border border-slate-700 font-mono" x-text="lang.toUpperCase()"></span>
                        @auth
                            <span class="text-[9px] font-bold uppercase px-1.5 py-0.2 rounded bg-blue-900/80 text-blue-200 border border-blue-700/60">
                                {{ auth()->user()->role }}
                            </span>
                        @endauth
                    </p>
                    <p class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        @if(!empty(config('services.gemini.api_key')))
                            <span>Online &bull; Google Gemini AI</span>
                        @else
                            <span>Online &bull; Workshop Assistant</span>
                        @endif
                        <span x-show="isFullScreen" class="text-[10px] text-slate-400 font-mono hidden sm:inline">(Full-Screen Mode)</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                {{-- Full-Screen Toggle Button --}}
                <button type="button" @click="toggleFullScreen()"
                    class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition"
                    :title="isFullScreen ? (lang === 'ms' ? 'Kecilkan Tetingkap' : 'Exit Full Screen') : (lang === 'ms' ? 'Besarkan Tetingkap (Full Screen)' : 'Full Screen Mode')">
                    {{-- Maximize Icon --}}
                    <svg x-show="!isFullScreen" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <polyline points="9 21 3 21 3 15"></polyline>
                        <line x1="21" y1="3" x2="14" y2="10"></line>
                        <line x1="3" y1="21" x2="10" y2="14"></line>
                    </svg>
                    {{-- Minimize Icon --}}
                    <svg x-show="isFullScreen" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                        <polyline points="4 14 10 14 10 20"></polyline>
                        <polyline points="20 10 14 10 14 4"></polyline>
                        <line x1="14" y1="10" x2="21" y2="3"></line>
                        <line x1="3" y1="21" x2="10" y2="14"></line>
                    </svg>
                </button>

                {{-- Reset Chat Button --}}
                <button type="button" @click="resetChat()"
                    class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition"
                    :title="lang === 'ms' ? 'Mula Semula Sesi Perbualan' : 'Reset Chat Session'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                    </svg>
                </button>

                {{-- Close Window Button --}}
                <button type="button" @click="open = false; isFullScreen = false;"
                    class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition"
                    title="Tutup Chat">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Chat Body --}}
        <div x-ref="chatBody" class="flex-1 overflow-y-auto p-3.5 bg-slate-50">
            <div :class="isFullScreen ? 'max-w-4xl mx-auto space-y-3' : 'space-y-3'">
                <template x-for="(msg, i) in messages" :key="i">
                    <div x-show="msg.text">
                        {{-- Message Bubble --}}
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="msg.role === 'user'
                                    ? 'bg-slate-900 text-white rounded-2xl rounded-br-xs px-3.5 py-2.5 text-xs max-w-[85%] shadow-xs leading-relaxed'
                                    : 'bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-bl-xs px-3.5 py-2.5 text-xs max-w-[92%] shadow-xs leading-relaxed'"
                                x-html="formatMessage(msg.text, msg.role)"
                                style="word-break: break-word;">
                            </div>
                        </div>

                        {{-- Interactive Schedule & Slot Picker Calendar Widget --}}
                        <template x-if="msg.show_calendar || msg.action === 'select_slot'">
                            <div class="mt-2.5 p-3.5 bg-white border border-blue-200 rounded-xl shadow-xs text-xs space-y-2.5 max-w-[95%]">
                                <div class="flex items-center justify-between pb-1.5 border-b border-gray-100">
                                    <span class="font-bold text-slate-800 flex items-center gap-1.5 text-[11px]">
                                        <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        <span x-text="lang === 'ms' ? 'Pilih Tarikh & Masa Servis' : 'Select Date & Time Slot'"></span>
                                    </span>
                                    <span class="text-[10px] text-gray-500 font-medium" x-text="lang === 'ms' ? 'Ahad - Jumaat (Sabtu Tutup)' : 'Sun - Fri (Sat Closed)'"></span>
                                </div>

                                <!-- Calendar Days Horizontal Strip -->
                                <div x-show="calendarLoading" class="py-3 text-center text-gray-400 text-[11px] flex items-center justify-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping"></span>
                                    <span x-text="lang === 'ms' ? 'Memuatkan jadual kekosongan bengkel...' : 'Loading workshop availability...'"></span>
                                </div>

                                <div x-show="!calendarLoading && scheduleDays && scheduleDays.length > 0" class="flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                                    <template x-for="day in scheduleDays" :key="day.date">
                                        <button type="button" @click="selectDate(day)"
                                            :disabled="!day.is_selectable"
                                            :class="{
                                                'border-blue-600 bg-blue-50/80 ring-2 ring-blue-500/20 shadow-xs': selectedDay && selectedDay.date === day.date,
                                                'border-gray-200 bg-white hover:border-blue-400 hover:bg-slate-50 cursor-pointer': day.is_selectable && (!selectedDay || selectedDay.date !== day.date),
                                                'border-gray-200 bg-gray-100/70 text-gray-400 cursor-not-allowed opacity-50': day.is_past,
                                                'border-rose-200 bg-rose-50/70 text-rose-500 cursor-not-allowed opacity-60': day.is_saturday || day.is_closed_day,
                                                'border-red-200 bg-red-50/80 text-red-600 cursor-not-allowed opacity-70': day.is_full && !(day.is_saturday || day.is_closed_day)
                                            }"
                                            class="shrink-0 w-16 py-2 px-1 rounded-lg border text-center transition flex flex-col items-center justify-center relative">
                                            
                                            <span class="text-[10px] font-bold uppercase tracking-wider" 
                                                :class="(day.is_saturday || day.is_closed_day) ? 'text-rose-500' : (day.is_past ? 'text-gray-400 line-through' : 'text-slate-600')"
                                                x-text="lang === 'ms' ? day.day_name_ms : day.day_name_en"></span>
                                            <span class="text-sm font-extrabold mt-0.5 leading-tight" 
                                                :class="day.is_past ? 'text-gray-400 line-through' : ((day.is_saturday || day.is_closed_day) ? 'text-rose-600' : 'text-slate-900')"
                                                x-text="day.day_number"></span>
                                            <span class="text-[9px] mt-1 px-1 py-0.2 rounded font-semibold whitespace-nowrap"
                                                :class="{
                                                    'bg-gray-200 text-gray-500 line-through': day.is_past,
                                                    'bg-rose-100 text-rose-700': day.is_saturday || day.is_closed_day,
                                                    'bg-red-100 text-red-700 font-bold': day.is_full && !(day.is_saturday || day.is_closed_day),
                                                    'bg-emerald-50 text-emerald-700 border border-emerald-200': day.is_selectable
                                                }"
                                                x-text="day.is_past ? (lang === 'ms' ? 'Lalu' : 'Past') : ((day.is_saturday || day.is_closed_day) ? (lang === 'ms' ? 'Tutup' : 'Closed') : (day.is_full ? (lang === 'ms' ? 'PENUH' : 'FULL') : (lang === 'ms' ? day.slots_remaining + ' slot' : day.slots_remaining + ' left')))"></span>
                                        </button>
                                    </template>
                                </div>

                                <!-- Time Slots (Shown when an available date is selected) -->
                                <div x-show="selectedDay && selectedDay.is_selectable" class="pt-2 border-t border-gray-100">
                                    <p class="text-[11px] font-semibold text-gray-700 mb-1.5 flex items-center justify-between">
                                        <span>
                                            <span x-text="lang === 'ms' ? 'Masa Tersedia: ' : 'Available Time: '"></span>
                                            <strong class="text-blue-700" x-text="selectedDay ? (lang === 'ms' ? selectedDay.day_name_ms + ', ' + selectedDay.day_number + ' ' + selectedDay.month : selectedDay.formatted_label) : ''"></strong>
                                        </span>
                                        <span class="text-[10px] text-gray-400" x-text="lang === 'ms' ? 'Had 3 kereta/slot' : 'Max 3 bays/slot'"></span>
                                    </p>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-1.5">
                                        <template x-for="slot in selectedDay ? selectedDay.slots : []" :key="slot.time">
                                            <button type="button" @click="pickSlot(selectedDay, slot)"
                                                :disabled="!slot.is_available"
                                                :class="{
                                                    'bg-slate-900 text-white font-bold ring-2 ring-slate-400': selectedSlot && selectedSlot.time === slot.time,
                                                    'bg-white text-slate-800 border border-gray-300 hover:border-slate-800 hover:bg-slate-50 font-medium active:scale-95': slot.is_available && (!selectedSlot || selectedSlot.time !== slot.time),
                                                    'bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed opacity-50': slot.is_past,
                                                    'bg-red-50 text-red-500 border border-red-200 cursor-not-allowed opacity-60 font-semibold': slot.is_full && !slot.is_past
                                                }"
                                                class="py-1.5 px-2 rounded-lg text-[11px] text-center transition flex flex-col items-center justify-center">
                                                <span x-text="slot.label"></span>
                                                <span x-show="slot.is_full && !slot.is_past" class="text-[8px] uppercase tracking-tighter" x-text="lang === 'ms' ? 'PENUH' : 'FULL'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Interactive Service Selection Chips --}}
                        <template x-if="msg.action === 'select_service_type' && msg.service_options">
                            <div class="mt-2.5 p-3 bg-white border border-slate-200 rounded-xl shadow-xs space-y-2 max-w-[95%]">
                                <p class="text-[11px] font-bold text-slate-800 flex items-center gap-1.5">
                                    <span>🛠️</span>
                                    <span x-text="lang === 'ms' ? 'Pilih Servis Pantas:' : 'Select Common Service:'"></span>
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                    <template x-for="(opt, idx) in msg.service_options" :key="idx">
                                        <button type="button"
                                            @click="pickServiceType(opt)"
                                            :disabled="loading"
                                            class="text-left p-2 rounded-lg border border-gray-200 hover:border-blue-500 hover:bg-blue-50/50 transition active:scale-95 group">
                                            <p class="text-xs font-bold text-slate-900 group-hover:text-blue-700" x-text="lang === 'ms' ? opt.name_ms : opt.name_en"></p>
                                            <p class="text-[10px] text-gray-500 mt-0.5 line-clamp-1" x-text="opt.symptoms"></p>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Direct Link Button to Created Appointment --}}
                        <template x-if="msg.appointment_url">
                            <div class="mt-2.5 ml-1 flex items-center gap-2">
                                <a :href="msg.appointment_url"
                                    class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-xs transition active:scale-95">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    <span x-text="lang === 'ms' ? 'Lihat Butiran Temujanji di Sistem' : 'View Appointment in System'"></span>
                                </a>
                            </div>
                        </template>

                        {{-- Action buttons for appointment booking confirmation --}}
                        <template x-if="msg.action === 'confirm_booking'">
                            <div class="mt-2.5 flex flex-wrap items-center gap-2 justify-start ml-1">
                                <button type="button" @click="confirmBooking()" :disabled="loading"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-xs flex items-center gap-1.5 transition active:scale-95 disabled:opacity-50">
                                    <span>✓</span>
                                    <span x-text="lang === 'ms' ? 'Ya, Sahkan Tempahan' : 'Yes, Confirm Booking'"></span>
                                </button>
                                <button type="button" @click="send(lang === 'ms' ? 'Ubah tarikh dan masa temujanji' : 'Change appointment date and time', 'edit_booking_date')" :disabled="loading"
                                    class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 px-2.5 py-1.5 rounded-lg text-xs font-medium shadow-2xs flex items-center gap-1 transition active:scale-95">
                                    <span>📅</span>
                                    <span x-text="lang === 'ms' ? 'Ubah Tarikh/Masa' : 'Change Date/Time'"></span>
                                </button>
                                <button type="button" @click="send(lang === 'ms' ? 'Ubah kenderaan temujanji' : 'Change vehicle for appointment', 'edit_booking_vehicle')" :disabled="loading"
                                    class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 px-2.5 py-1.5 rounded-lg text-xs font-medium shadow-2xs flex items-center gap-1 transition active:scale-95">
                                    <span>🚗</span>
                                    <span x-text="lang === 'ms' ? 'Ubah Kenderaan' : 'Change Vehicle'"></span>
                                </button>
                                <button type="button" @click="send(lang === 'ms' ? 'Ubah servis atau maklumat simptom' : 'Change service or symptoms', 'edit_booking_service')" :disabled="loading"
                                    class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 px-2.5 py-1.5 rounded-lg text-xs font-medium shadow-2xs flex items-center gap-1 transition active:scale-95">
                                    <span>🛠️</span>
                                    <span x-text="lang === 'ms' ? 'Ubah Servis' : 'Change Service'"></span>
                                </button>
                                <button type="button" @click="cancelBooking()" :disabled="loading"
                                    class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 px-2.5 py-1.5 rounded-lg text-xs font-medium transition active:scale-95">
                                    <span>✕</span>
                                    <span x-text="lang === 'ms' ? 'Batal' : 'Cancel'"></span>
                                </button>
                            </div>
                        </template>

                        {{-- Action buttons for invoice payment status change confirmation --}}
                        <template x-if="msg.action === 'confirm_invoice_payment'">
                            <div class="mt-2.5 flex flex-wrap items-center gap-2 justify-start ml-1">
                                <a :href="msg.invoice_url"
                                    class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-xs flex items-center gap-1.5 transition active:scale-95">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    <span x-text="lang === 'ms' ? 'Lompat ke Bahagian Bayaran' : 'Jump to Payment Section'"></span>
                                </a>
                                <button type="button" @click="send(lang === 'ms' ? 'Sahkan tukar status kepada Paid' : 'Confirm mark as paid', 'execute_invoice_payment')" :disabled="loading"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold shadow-xs flex items-center gap-1.5 transition active:scale-95 disabled:opacity-50">
                                    <span>✓</span>
                                    <span x-text="lang === 'ms' ? 'Sahkan Lunas (Paid) Sekarang' : 'Confirm Mark as Paid Now'"></span>
                                </button>
                                <button type="button" @click="send(lang === 'ms' ? 'Batal tukar status' : 'Cancel', 'cancel_invoice_payment')" :disabled="loading"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-3 py-1.5 rounded-lg text-xs font-medium transition active:scale-95">
                                    <span x-text="lang === 'ms' ? 'Batal' : 'Cancel'"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="loading" class="flex justify-start">
                    <div class="bg-white border border-gray-200 text-gray-500 rounded-2xl rounded-bl-xs px-3.5 py-2 text-xs shadow-xs flex items-center gap-2">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping"></span>
                        <span x-text="lang === 'ms' ? 'Sedang memproses maklumat...' : 'Processing request...'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Role-Aware Quick Suggestion Pills (Uniform Styling, Bilingual & Dynamic) --}}
        <div class="px-3 py-2 bg-white border-t border-gray-100 flex gap-1.5 overflow-x-auto text-[11px] no-scrollbar">
            <div :class="isFullScreen ? 'max-w-4xl mx-auto w-full flex gap-1.5 overflow-x-auto no-scrollbar' : 'flex gap-1.5 overflow-x-auto no-scrollbar'">
                @auth
                    @if(auth()->user()->role === 'staff')
                        {{-- Mechanic / Workshop Technician Pills --}}
                        <button type="button" @click="send(lang === 'ms' ? 'Baki job card saya (had job left?)' : 'My assigned job cards (had job left?)')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🛠️ Baki Job Card Aktif (Had Job Left?)' : '🛠️ Active Assigned Jobs (Had Job Left?)'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Stok mana yang rendah?' : 'Which spare parts are low in stock?')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🔩 Semak Stok Alat Ganti' : '🔩 Check Parts Stock'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Cari invois' : 'Search customer invoices')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🧾 Cari Invois' : '🧾 Search Invoices'"></span>
                        </button>
                        <button type="button" @click="fetchSchedule(); send(lang === 'ms' ? 'Tunjukkan jadual dan kalendar temujanji bengkel' : 'Show workshop appointment schedule and calendar')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📅 Kalendar Slot Bengkel' : '📅 Workshop Slot Calendar'"></span>
                        </button>

                    @elseif(auth()->user()->role === 'admin')
                        {{-- Admin Pills --}}
                        <button type="button" @click="send(lang === 'ms' ? 'Laporan harian status temujanji hari ini' : 'Daily report of today appointments')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📊 Laporan & Temujanji Hari Ini' : '📊 Today\'s Appointments & Report'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Cari invois' : 'Search customer invoices')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🧾 Cari Invois Pelanggan' : '🧾 Search Customer Invoices'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Stok mana yang rendah?' : 'Check low stock alerts')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '⚠️ Amaran Stok Rendah' : '⚠️ Low Stock Alerts'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Bila last PO topup restock dibuat?' : 'What is the purchase order restock status?')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📦 Status PO Pesanan' : '📦 Purchase Orders Status'"></span>
                        </button>
                        <button type="button" @click="fetchSchedule(); send(lang === 'ms' ? 'Tunjukkan jadual dan kalendar temujanji bengkel' : 'Show workshop appointment schedule and calendar')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📅 Kalendar Slot' : '📅 Workshop Calendar'"></span>
                        </button>

                    @elseif(auth()->user()->role === 'coordinator')
                        {{-- Coordinator Pills --}}
                        <button type="button" @click="send(lang === 'ms' ? 'Status temujanji hari ini' : 'Today appointment arrivals status')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📊 Status Temujanji Hari Ini' : '📊 Today\'s Appointment Status'"></span>
                        </button>
                        <button type="button" @click="fetchSchedule(); send(lang === 'ms' ? 'Tunjukkan jadual dan kalendar temujanji bengkel' : 'Show workshop appointment schedule and calendar')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📅 Kalendar Slot Bengkel' : '📅 Workshop Slot Calendar'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Status lantai bengkel' : 'Workshop service bay status')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🏭 Status Lantai Bengkel' : '🏭 Service Bays Status'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Cari invois' : 'Search customer invoices')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🧾 Cari Invois' : '🧾 Search Invoices'"></span>
                        </button>

                    @elseif(auth()->user()->role === 'corporate')
                        {{-- Corporate Fleet Manager Pills --}}
                        <button type="button" @click="send(lang === 'ms' ? 'Status kenderaan armada syarikat saya' : 'Status of my company fleet vehicles')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🚛 Status Armada Syarikat' : '🚛 Company Fleet Status'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Invois belum bayar syarikat saya' : 'Outstanding invoices for my company')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🧾 Invois Tertunggak Syarikat' : '🧾 Outstanding Fleet Invoices'"></span>
                        </button>
                        <button type="button" @click="fetchSchedule(); send(lang === 'ms' ? 'Saya nak tempah slot servis lori syarikat' : 'I want to schedule service for company truck')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📅 Tempah Servis Armada' : '📅 Book Fleet Servicing'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Rekod servis kenderaan syarikat saya' : 'Maintenance service records for our fleet')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📋 Rekod Penyelenggaraan' : '📋 Fleet Maintenance Records'"></span>
                        </button>

                    @else
                        {{-- Individual Car Owner Pills --}}
                        <button type="button" @click="fetchSchedule(); send(lang === 'ms' ? 'Saya nak tempah slot temujanji servis kenderaan' : 'I want to book a vehicle service appointment')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📅 Tempah Temujanji Servis' : '📅 Book Service Slot'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Kenderaan saya yang berdaftar' : 'My registered vehicles')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🚗 Kenderaan Berdaftar Saya' : '🚗 My Registered Cars'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Berapa harga servis minyak hitam dan brek?' : 'How much for engine oil and brake servicing?')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '💰 Sebut Harga Upah & Servis' : '💰 Service & Repair Quotes'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Invois kenderaan saya' : 'My service invoices')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '🧾 Invois Servis Saya' : '🧾 My Invoices'"></span>
                        </button>
                        <button type="button" @click="send(lang === 'ms' ? 'Tengok temujanji aktif saya' : 'Show my upcoming appointments')"
                            class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                            <span x-text="lang === 'ms' ? '📋 Rekod Temujanji Saya' : '📋 My Appointments'"></span>
                        </button>
                    @endif
                @else
                    {{-- Public Guest Visitor Pills (Uniform styling & bilingual) --}}
                    <button type="button" @click="fetchSchedule(); send(lang === 'ms' ? 'Saya nak book appointment servis kenderaan' : 'I want to book a vehicle service appointment')"
                        class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                        <span x-text="lang === 'ms' ? '📅 Tempah Slot Servis' : '📅 Book Service Slot'"></span>
                    </button>
                    <button type="button" @click="send(lang === 'ms' ? 'Berapa kos servis minyak hitam dan tukar pad brek?' : 'How much for engine oil service and brake pads replacement?')"
                        class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                        <span x-text="lang === 'ms' ? '💰 Kadar Upah Servis' : '💰 Service & Labour Rates'"></span>
                    </button>
                    <button type="button" @click="send(lang === 'ms' ? 'Di mana lokasi bengkel dan waktu operasi?' : 'What are the workshop location and opening hours?')"
                        class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                        <span x-text="lang === 'ms' ? '📍 Lokasi & Waktu Operasi' : '📍 Location & Operating Hours'"></span>
                    </button>
                    <button type="button" @click="send(lang === 'ms' ? 'Senaraikan alat ganti dan bateri kereta' : 'List available spare parts and car batteries')"
                        class="shrink-0 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-full px-3 py-1 font-medium shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                        <span x-text="lang === 'ms' ? '🔩 Bateri & Alat Ganti' : '🔩 Spare Parts & Batteries'"></span>
                    </button>
                @endauth
            </div>
        </div>

        {{-- Input Bar --}}
        <div class="p-2.5 border-t border-gray-200 bg-white">
            <div :class="isFullScreen ? 'max-w-4xl mx-auto flex gap-2 items-center' : 'flex gap-2 items-center'">
                <input type="text" x-model="input"
                    @keydown.enter="send()"
                    :placeholder="lang === 'ms' ? 'Tanya masalah kenderaan, tugasan kerja, harga, atau tempah servis...' : 'Ask about vehicle issues, work tasks, pricing, or book...'"
                    class="flex-1 border border-gray-300 rounded-lg px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-slate-900 bg-gray-50 focus:bg-white transition">
                
                <button @click="send()" :disabled="loading"
                    class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center
                           hover:bg-slate-800 disabled:opacity-50 shrink-0 shadow-xs transition transform active:scale-95"
                    :title="lang === 'ms' ? 'Hantar Mesej' : 'Send Message'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
