<?php
namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\JobType;
use App\Models\JobCard;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Setting;
use App\Models\Notification;
use App\Mail\AppointmentStatusMail;
use App\Mail\InvoiceMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ChatbotController extends Controller
{
    /** Words that carry no entity-identifying meaning. */
    private const GENERIC = [
        'service','change','replacement','replace','part','parts','spare','price','cost','fee','charge','rate',
        'how','much','many','what','which','is','are','the','for','of','a','an','do','does','you','have','your',
        'my','me','i','to','in','on','at','and','there','any','can','get','want','need','please','tell','about',
        'with','show','list','ada','ke','yang','nak','boleh','stock','available','left','offer','provide','it',
        'this','that','one','set','kit','pair','new','price','harga','berapa','something','looking',
    ];

    /** Malay / variant spellings -> canonical English. Applied on whole words. */
    private const SYNONYMS = [
        'terima kasih' => 'thanks', 'minyak enjin' => 'engine oil', 'minyak hitam' => 'engine oil',
        'how much' => 'price', 'opening hours' => 'hours',
        'servis' => 'service', 'harga' => 'price', 'berapa' => 'price', 'kos' => 'price',
        'tayar' => 'tyre', 'tire' => 'tyre', 'tires' => 'tyres', 'brek' => 'brake', 'bateri' => 'battery',
        'minyak' => 'oil', 'kereta' => 'car', 'tempahan' => 'booking', 'tq' => 'thanks', 'thx' => 'thanks',
        'assalamualaikum' => 'hello', 'salam' => 'hello', 'aircond' => 'air conditioning', 'ac' => 'air conditioning',
    ];

    /** Intent => [keyword => weight]. */
    private const INTENTS = [
        'greeting'     => ['hi'=>2,'hello'=>2,'hey'=>2,'morning'=>2,'afternoon'=>2,'evening'=>2],
        'thanks'       => ['thanks'=>3,'thank'=>3],
        'bye'          => ['bye'=>3,'goodbye'=>3],
        'hours'        => ['hours'=>3,'hour'=>3,'open'=>2,'opening'=>2,'close'=>2,'closing'=>2,'masa'=>2,'buka'=>2,'tutup'=>2,'operation'=>2,'when'=>1],
        'location'     => ['location'=>3,'address'=>3,'where'=>2,'located'=>2,'directions'=>2,'map'=>2,'mana'=>2,'alamat'=>3,'lokasi'=>3],
        'services'     => ['services'=>2,'service'=>1,'offer'=>2,'provide'=>2,'menu'=>1],
        'price'        => ['price'=>2,'cost'=>2,'fee'=>2,'charge'=>2,'rate'=>1,'quote'=>2],
        'stock'        => ['stock'=>2,'available'=>2,'availability'=>2,'left'=>1],
        'appointments' => ['appointment'=>3,'appointments'=>3,'booking'=>3,'book'=>2,'schedule'=>1,'reservation'=>3],
        'vehicles'     => ['vehicle'=>3,'vehicles'=>3,'car'=>2,'cars'=>2,'plate'=>2],
        'invoice'      => ['invoice'=>3,'invoices'=>3,'bill'=>2,'balance'=>3,'outstanding'=>3,'unpaid'=>3,'payment'=>2,'pay'=>2],
    ];

    public function reply(Request $request) {
        $request->validate([
            'message' => 'required|string|max:500',
            'action'  => 'nullable|string|max:50',
        ]);

        $message = trim($request->message);
        $action  = $request->action;
        $user    = auth()->user();

        // Record incoming user message in conversation history for multi-turn persistence
        $history = session('chatbot_history', []);
        $history[] = ['role' => 'user', 'text' => $message];
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        session(['chatbot_history' => $history]);

        // 1. Language Detection & Strict Session Persistence
        $lang = $this->detectAndPersistLanguage($message);
        $userContext = $this->getUserContext($user);

        // Internal staff (admin, coordinator, staff) do NOT do customer vehicle registration/intake
        if (!empty($userContext['is_internal_staff'])) {
            session()->forget('chatbot_intake');
        }

        // 2. User proceeds/confirms booking
        if ($action === 'confirm_booking' || $this->isConfirmProceed($message)) {
            $pending = session('pending_chatbot_booking');
            if ($pending) {
                return $this->executeBooking($pending, $user, $lang);
            }
        }

        // 2a-0. Direct slot selection via action from interactive calendar
        if ($action === 'pick_slot' && $request->filled('date') && $request->filled('time')) {
            $intake = session('chatbot_intake', []);
            $intake['date'] = $request->input('date');
            $intake['date_label'] = Carbon::parse($request->input('date'))->format('D, d M Y');
            $intake['time'] = $request->input('time');
            $intake['time_label'] = Carbon::parse($request->input('time'))->format('g:i A');
            session(['chatbot_intake' => $intake]);
            return $this->handleBookingConversation($message, $user, $lang, $userContext);
        }

        // 2a. User edits date/time of pending booking
        if ($action === 'edit_booking_date' || preg_match('/^(ubah|tukar|change|edit)\s*(tarikh|masa|date|time|slot)/i', $message)) {
            if (session()->has('pending_chatbot_booking') || session()->has('chatbot_intake')) {
                $intake = session('pending_chatbot_booking') ?? session('chatbot_intake');
                unset($intake['date'], $intake['date_label'], $intake['time'], $intake['time_label']);
                session(['chatbot_intake' => $intake]);
                session()->forget('pending_chatbot_booking');

                $reply = $lang === 'ms'
                    ? "📅 **Pilih Tarikh & Masa Baharu**\n\nSila pilih tarikh dan slot masa baharu daripada kalendar kekosongan bengkel di bawah:"
                    : "📅 **Select New Date & Time Slot**\n\nPlease select a new date and arrival window from the interactive schedule below:";

                return response()->json([
                    'reply'         => $reply,
                    'action'        => 'select_slot',
                    'show_calendar' => true,
                    'lang'          => $lang,
                ]);
            }
        }

        // 2b. User edits vehicle details of pending booking
        if ($action === 'edit_booking_vehicle' || preg_match('/^(ubah|tukar|change|edit)\s*(kenderaan|kereta|plat|vehicle|car|plate)/i', $message)) {
            if (session()->has('pending_chatbot_booking') || session()->has('chatbot_intake')) {
                $intake = session('pending_chatbot_booking') ?? session('chatbot_intake');
                unset($intake['car_brand'], $intake['car_model'], $intake['car_year'], $intake['plate_number']);
                session(['chatbot_intake' => $intake]);
                session()->forget('pending_chatbot_booking');

                $reply = $lang === 'ms'
                    ? "🚗 **Kemaskini Maklumat Kenderaan**\n\nSila berikan Model, Tahun Kereta, dan Nombor Plat kenderaan baharu (contoh: *Perodua Myvi 2018 JQP 1122*):"
                    : "🚗 **Update Vehicle Details**\n\nPlease provide the new vehicle Make, Model, Year, and Plate Number (e.g. *Perodua Myvi 2018 JQP 1122*):";

                return response()->json(['reply' => $reply, 'lang' => $lang]);
            }
        }

        // 2c. User edits service type or symptoms
        if ($action === 'edit_booking_service' || preg_match('/^(ubah|tukar|change|edit)\s*(servis|service|simptom|symptom|masalah|problem|aduan)/i', $message)) {
            if (session()->has('pending_chatbot_booking') || session()->has('chatbot_intake')) {
                $intake = session('pending_chatbot_booking') ?? session('chatbot_intake');
                unset($intake['service_type'], $intake['symptoms']);
                session(['chatbot_intake' => $intake]);
                session()->forget('pending_chatbot_booking');

                $reply = $lang === 'ms'
                    ? "🛠️ **Kemaskini Jenis Servis / Masalah**\n\nSila terangkan simptom atau jenis servis yang anda inginkan (contoh: *Servis Minyak Hitam & Penapis*, *Pemeriksaan Brek*, atau *Diagnostic Scan*):"
                    : "🛠️ **Update Service Type / Issues**\n\nPlease describe the symptoms or service required (e.g. *Brake Inspection*, *Oil & Filter Change*, or *Engine Diagnostics Scan*):";

                return response()->json(['reply' => $reply, 'lang' => $lang]);
            }
        }

        // 2d. User indicates something is wrong on final confirmation
        if (preg_match('/(maklumat\s*salah|ada\s*salah|nak\s*betulkan|need\s*to\s*fix|information\s*wrong|correct\s*something)/i', $message)) {
            if (session()->has('pending_chatbot_booking')) {
                $reply = $lang === 'ms'
                    ? "Bahagian mana yang ingin dibetulkan? Anda boleh klik salah satu butang di bawah atau terus beritahu saya maklumat yang betul:\n\n• **Tarikh/Masa**: Klik butang [📅 Ubah Tarikh/Masa]\n• **Kenderaan**: Klik butang [🚗 Ubah Kenderaan]\n• **Servis/Simptom**: Klik butang [🛠️ Ubah Servis]"
                    : "Which detail would you like to update? You can click a button below or tell me the correct details directly:\n\n• **Date/Time**: Click [📅 Change Date/Time]\n• **Vehicle**: Click [🚗 Change Vehicle]\n• **Service/Symptoms**: Click [🛠️ Change Service]";

                return response()->json([
                    'reply'  => $reply,
                    'action' => 'confirm_booking',
                    'lang'   => $lang,
                ]);
            }
        }

        // 2e. User confirms invoice payment / mark as paid
        if ($action === 'execute_invoice_payment' || $this->isConfirmInvoicePayment($message)) {
            $pending = session('pending_invoice_payment');
            if ($pending) {
                return $this->executeInvoicePayment($pending, $user, $lang);
            }
        }

        // 3. User cancels booking or resets intake or cancels invoice payment
        if ($action === 'cancel_booking' || $action === 'cancel_invoice_payment' || $this->isCancelIntent($message)) {
            $hadInvoicePayment = session()->has('pending_invoice_payment');
            session()->forget(['pending_chatbot_booking', 'chatbot_intake', 'pending_booking_time', 'pending_invoice_payment']);
            if ($hadInvoicePayment) {
                $reply = $lang === 'ms'
                    ? "Baik, permintaan menukar status pembayaran invois telah dibatalkan. Tiada rekod bayaran dibuat. 😊"
                    : "Understood, the invoice status update request has been cancelled. No payment changes were recorded. 😊";
                return response()->json(['reply' => $reply, 'lang' => $lang]);
            }
            $reply = $lang === 'ms'
                ? "Baik, draf tempahan anda telah dibatalkan. Ada apa-apa soalan atau bantuan lain yang boleh saya bantu? 😊"
                : "No problem! I've cancelled that booking draft. What else can I assist you with? 😊";
            return response()->json(['reply' => $reply, 'lang' => $lang]);
        }

        // 4. Incomplete Phone Number Prevention (if user enters an obvious incomplete phone)
        $phoneValidation = $this->validatePhone($message);
        if ($phoneValidation['status'] === 'incomplete') {
            $reply = $lang === 'ms'
                ? "⚠️ **Nombor Telefon Tidak Lengkap**: Anda memasukkan '{$phoneValidation['raw']}'. Sila berikan nombor telefon Malaysia yang sah dengan 10-11 digit (contohnya: **012-345 6789** atau **011-1234 5678**) supaya pihak bengkel dapat mengesahkan tempahan anda."
                : "⚠️ **Incomplete Phone Number**: You entered '{$phoneValidation['raw']}'. Please provide a valid 10-11 digit Malaysian phone number (e.g. **012-345 6789** or **011-1234 5678**) so our workshop team can contact you.";
            return response()->json(['reply' => $reply, 'lang' => $lang]);
        }

        // 5. Workshop Schedule & Calendar Availability View
        // ("Tunjukkan jadual dan kalendar temujanji bengkel", "kalendar slot", "jadual bengkel", "waktu operasi")
        if ($this->isScheduleViewQuery($message)) {
            return $this->handleScheduleViewQuery($message, $user, $lang);
        }

        // 6. Staff / Mechanic Work Assistance Queries ("had job left?", "what are my jobs?", "job card saya")
        if ($this->isStaffJobQuery($message)) {
            session()->forget('chatbot_intake');
            return response()->json([
                'reply' => $this->handleStaffJobQuery($message, $user, $lang),
                'lang'  => $lang,
            ]);
        }

        // 7. Admin & Coordinator Daily Report / Today's Appointment Status ("status on today appointment", "daily report")
        if ($this->isDailyReportQuery($message)) {
            session()->forget('chatbot_intake');
            return response()->json([
                'reply' => $this->handleDailyAppointmentsReport($user, $lang),
                'lang'  => $lang,
            ]);
        }

        // 8. Direct Invoice Search ("cari invois", "search invoice", "invois belum bayar", "INV-2026-00044")
        if ($this->isInvoiceSearchQuery($message)) {
            session()->forget('chatbot_intake');
            return response()->json([
                'reply' => $this->handleInvoiceSearchQuery($message, $user, $lang),
                'lang'  => $lang,
            ]);
        }

        // 9. Invoice Payment Status Change Request ("tukar kepada paid", "mark as paid", "set kepada paid")
        if ($this->isInvoicePaymentUpdateQuery($message)) {
            session()->forget('chatbot_intake');
            return $this->handleInvoicePaymentRequest($message, $user, $lang);
        }

        // 10. Vehicle Plate Number & Appointment Details Lookup ("JQP 1122", "temujanji JQP 1122", "status JQP 1122")
        if ($this->isPlateOrAppointmentQuery($message, $userContext)) {
            session()->forget('chatbot_intake');
            return response()->json([
                'reply' => $this->handlePlateOrAppointmentQuery($message, $user, $lang, $userContext),
                'lang'  => $lang,
            ]);
        }

        // 11. Admin Inventory Helper Queries ("stok mana yang rendah", "bila last topup", etc.)
        if ($this->isInventoryQuery($message)) {
            session()->forget('chatbot_intake');
            return response()->json([
                'reply' => $this->handleInventoryQuery($message, $user, $lang),
                'lang'  => $lang,
            ]);
        }

        // 12. Service Interval / Maintenance Estimator ("bila patut servis", "last servis...")
        if ($this->isServiceEstimatorQuery($message)) {
            session()->forget('chatbot_intake');
            return response()->json([
                'reply' => $this->handleServiceEstimatorQuery($message, $user, $lang),
                'lang'  => $lang,
            ]);
        }

        // 13. Active Diagnostic Intake Session OR Booking Intent Trigger (Customer Only)
        if (session()->has('chatbot_intake') || session()->has('pending_chatbot_booking') || $this->isBookingIntent($message, $userContext)) {
            // Internal staff (admin, coordinator, staff) do NOT do customer vehicle registration here!
            if (!empty($userContext['is_internal_staff'])) {
                session()->forget(['chatbot_intake', 'pending_chatbot_booking']);
            } else {
                return $this->handleBookingConversation($message, $user, $lang, $userContext);
            }
        }

        // 11. Try Gemini AI with strict language enforcement & rich role-aware persona
        if ($geminiReply = $this->tryGemini($message, $user, $lang, $userContext)) {
            return response()->json([
                'reply' => $geminiReply,
                'lang'  => $lang,
            ]);
        }

        // 12. Default rule-based NLU fallback (strictly in selected language & role context)
        $response = $this->buildResponse($message, $user, $lang, $userContext);

        return response()->json([
            'reply' => $response,
            'lang'  => $lang,
        ]);
    }

    // ----------------------------------------------------------------
    // Language Detection & Persistence
    // ----------------------------------------------------------------

    private function detectAndPersistLanguage(string $message): string {
        $lower = strtolower($message);

        // 1. Explicit user override
        if (preg_match('/(\benglish\b|speak\s*in\s*english|in\s*english|switch\s*to\s*english)/i', $lower)) {
            session(['chatbot_lang' => 'en']);
            return 'en';
        }
        if (preg_match('/(\bbm\b|\bmelayu\b|\bbahasa\s*melayu\b|cakap\s*melayu|tukar\s*ke\s*melayu)/i', $lower)) {
            session(['chatbot_lang' => 'ms']);
            return 'ms';
        }

        // 2. Lexical word sets for responsive dynamic language switching
        $malayExclusive = [
            'hai', 'salam', 'assalamualaikum', 'nak', 'saya', 'sy', 'kereta', 'keta', 'kete', 'bila',
            'berapa', 'buka', 'tutup', 'brek', 'tayar', 'minyak', 'bateri', 'rosak', 'bunyi', 'pelik',
            'tolong', 'esok', 'lusa', 'temujanji', 'bengkel', 'sebut harga', 'tak', 'boleh', 'kena',
            'macam mana', 'apa', 'pukul', 'pagi', 'petang', 'malam', 'gegar', 'panas', 'kurang', 'rasa',
            'lampu', 'enjin', 'tempah', 'tempahan', 'sahkan', 'batal', 'tukar', 'semak', 'kat', 'dekat',
            'jadual', 'kalendar', 'waktu', 'operasi', 'lokasi', 'alamat', 'ahad', 'isnin', 'selasa', 'rabu',
            'khamis', 'jumaat', 'sabtu', 'jom', 'ya', 'betul', 'buat', 'siap', 'stok', 'ada', 'ubah',
            'maklumat', 'salah', 'betulkan'
        ];
        $englishExclusive = [
            'hello', 'hey', 'my', 'i', 'want', 'need', 'book', 'appointment', 'booking', 'service',
            'the', 'for', 'car', 'vehicle', 'how', 'much', 'when', 'where', 'what', 'please', 'schedule',
            'sure', 'lets', 'do', 'that', 'yes', 'correct', 'confirm', 'cancel', 'check', 'view',
            'hours', 'location', 'address', 'open', 'close', 'engine', 'brake', 'tire', 'tyre', 'oil',
            'strange', 'weird', 'unusual', 'sound', 'noise', 'change', 'edit', 'update', 'wrong', 'fix'
        ];

        $mCount = 0;
        foreach ($malayExclusive as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/i', $lower)) $mCount += 2;
        }
        $eCount = 0;
        foreach ($englishExclusive as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/i', $lower)) $eCount += 2;
        }

        // 3. Dynamic switching: if current message has clear linguistic preference, switch immediately!
        if ($mCount > 0 && $mCount > $eCount) {
            session(['chatbot_lang' => 'ms']);
            return 'ms';
        }
        if ($eCount > 0 && $eCount > $mCount) {
            session(['chatbot_lang' => 'en']);
            return 'en';
        }

        // 4. For neutral or ambiguous messages (e.g. "JQP 1122", "10:00 AM", "OK"), retain existing session language
        if (session()->has('chatbot_lang')) {
            return session('chatbot_lang');
        }

        session(['chatbot_lang' => 'en']);
        return 'en';
    }

    // ----------------------------------------------------------------
    // Phone Number Validation (Malaysian Standards)
    // ----------------------------------------------------------------

    private function validatePhone(string $text): array {
        // 1. Valid complete Malaysian phone number
        if (preg_match('/(?<!\d)(?:\+?60|0)(?:1[0-9][\s\-]?[0-9]{3,4}[\s\-]?[0-9]{3,4}|[3-9][0-9]{1}[\s\-]?[0-9]{3,4}[\s\-]?[0-9]{3,4})(?!\d)/', $text, $vm)) {
            $rawNumber = $vm[0];
            $cleanDigits = preg_replace('/[^\d]/', '', $rawNumber);
            $normalized = preg_replace('/^60/', '0', $cleanDigits);

            if (str_starts_with($normalized, '011')) {
                $formatted = substr($normalized, 0, 3) . '-' . substr($normalized, 3, 4) . ' ' . substr($normalized, 7);
            } elseif (str_starts_with($normalized, '01')) {
                $formatted = substr($normalized, 0, 3) . '-' . substr($normalized, 3, 3) . ' ' . substr($normalized, 6);
            } else {
                $formatted = $normalized;
            }

            return ['status' => 'valid', 'phone' => $formatted, 'raw' => $rawNumber];
        }

        // 2. Detect incomplete phone attempt starting with 01, +601, 601 with fewer than 9 digits
        if (preg_match('/(?<!\d)(?:\+?601|01)[0-9\- ]{1,6}(?!\d)/', $text, $im)) {
            $digitsOnly = preg_replace('/[^\d]/', '', $im[0]);
            if (strlen($digitsOnly) >= 2 && strlen($digitsOnly) < 9) {
                return ['status' => 'incomplete', 'raw' => trim($im[0])];
            }
        }

        // 3. Detect incomplete standalone number if labeled as phone/tel/hp/nombor
        if (preg_match('/(?:tel|phone|nombor\s*telefon|no\.?\s*tel|hp)\s*(?:ialah|adalah|:)?\s*([0-9\- ]{2,8})(?!\d)/i', $text, $labelMatch)) {
            $digitsOnly = preg_replace('/[^\d]/', '', $labelMatch[1]);
            if (strlen($digitsOnly) >= 2 && strlen($digitsOnly) < 9) {
                return ['status' => 'incomplete', 'raw' => trim($labelMatch[1])];
            }
        }

        return ['status' => 'missing'];
    }

    // ----------------------------------------------------------------
    // Interactive Schedule Availability API Endpoint
    // ----------------------------------------------------------------

    public function scheduleAvailability(Request $request) {
        $capacity = (int) Setting::get('daily_appointment_capacity', 25);
        $today = Carbon::today();
        $days = [];

        for ($i = 0; $i < 14; $i++) {
            $date = $today->copy()->addDays($i);
            $dateStr = $date->toDateString();
            $isSaturday = $date->isSaturday();
            $isFriday = $date->isFriday();
            $isPast = $date->isPast() && !$date->isToday();

            $apptCount = Appointment::whereDate('date', $dateStr)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            $isFull = $apptCount >= $capacity;
            $isSelectable = !$isSaturday && !$isFull && !$isPast;

            // Workshop operating slots (Teraju Setia Enterprise, Kluang):
            // Sun - Thu: 8:30 AM to 5:30 PM
            // Fri: 8:30 AM to 12:30 PM & 2:30 PM to 5:30 PM (Friday prayer break 12:30 PM - 2:30 PM)
            // Sat: Closed
            if ($isSaturday) {
                $timeOptions = [];
            } elseif ($isFriday) {
                $timeOptions = ['08:30', '09:30', '10:30', '11:30', '14:30', '15:30', '16:30'];
            } else {
                $timeOptions = ['08:30', '09:30', '10:30', '11:30', '13:30', '14:30', '15:30', '16:30'];
            }

            $slots = [];
            foreach ($timeOptions as $t) {
                $slotTime = Carbon::parse("{$dateStr} {$t}:00");
                $slotIsPast = $slotTime->isPast();

                $slotCount = Appointment::whereDate('date', $dateStr)
                    ->whereTime('time', "{$t}:00")
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count();

                $slotFull = $slotCount >= 3; // Max 3 vehicles per hourly window

                $slots[] = [
                    'time'         => "{$t}:00",
                    'label'        => Carbon::parse("{$t}:00")->format('g:i A'),
                    'is_past'      => $slotIsPast,
                    'is_full'      => $slotFull,
                    'is_available' => !$slotIsPast && !$slotFull && $isSelectable,
                    'slots_left'   => max(0, 3 - $slotCount),
                ];
            }

            $days[] = [
                'date'            => $dateStr,
                'day_name_en'     => $date->format('D'),
                'day_name_ms'     => match($date->dayOfWeek) {
                    0 => 'Ahad', 1 => 'Isnin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Khamis', 5 => 'Jumaat', 6 => 'Sabtu'
                },
                'day_number'      => $date->format('d'),
                'month'           => $date->format('M'),
                'formatted_label' => $date->format('D, d M Y'),
                'is_today'        => $date->isToday(),
                'is_saturday'     => $isSaturday,
                'is_closed_day'   => $isSaturday,
                'is_past'         => $isPast,
                'is_full'         => $isFull,
                'is_selectable'   => $isSelectable,
                'status_label_ms' => $isSaturday ? 'Tutup (Sabtu)' : ($isFull ? 'PENUH' : ($capacity - $apptCount) . ' slot kosong'),
                'status_label_en' => $isSaturday ? 'Closed (Sat)' : ($isFull ? 'FULL' : ($capacity - $apptCount) . ' slots left'),
                'slots_remaining' => max(0, $capacity - $apptCount),
                'slots'           => $slots,
            ];
        }

        return response()->json([
            'capacity' => $capacity,
            'today'    => $today->toDateString(),
            'days'     => $days,
        ]);
    }

    public function resetSession(Request $request) {
        session()->forget(['chatbot_intake', 'pending_chatbot_booking', 'pending_booking_time', 'chatbot_lang', 'chatbot_last', 'chatbot_history']);
        return response()->json(['success' => true]);
    }

    // ----------------------------------------------------------------
    // Workshop Schedule & Availability View Query Handler
    // ----------------------------------------------------------------

    private function isScheduleViewQuery(string $msg): bool {
        $lower = strtolower(trim($msg));

        // Negative check: if message is explicitly asking to book/set an appointment, not just view schedule
        if (preg_match('/(buat|nak|mau|hendak|set|atur|tolong\s*buat)\s*(temujanji|booking|appointment)/i', $lower)) {
            return false;
        }

        // 1. Explicit display/view verbs with schedule/calendar/slots
        if (preg_match('/(tunjukkan|lihat|tengok|semak|buka|papar|check|view|show|display)\s*(kan)?\s*.*(jadual|kalendar|slot|kekosongan|waktu\s*operasi)/i', $lower)) {
            return true;
        }

        // 2. Starts with schedule/calendar
        if (preg_match('/^(jadual|kalendar|schedule|calendar)\b/i', $lower)) {
            return true;
        }

        // 3. Phrases like "kalendar slot", "jadual slot", "jadual temujanji", "kalendar temujanji", "jadual bengkel"
        if (preg_match('/(kalendar\s*slot|jadual\s*slot|kalendar\s*temujanji|jadual\s*temujanji|jadual\s*bengkel|jadual\s*operasi|kalendar\s*bengkel)/i', $lower)) {
            return true;
        }

        // 4. Slot availability questions
        if (preg_match('/(slot\s*(kosong|available|kekosongan)|ada\s*slot\s*(kosong)?|bila\s*(ada\s*)?slot)/i', $lower)) {
            return true;
        }

        // 5. Operating hours questions
        if (preg_match('/(bila\s*bengkel\s*(buka|operasi)|waktu\s*(operasi|buka)\s*bengkel|hari\s*operasi)/i', $lower)) {
            return true;
        }

        // 6. English schedule/calendar/operating hours queries
        if (preg_match('/(workshop\s*(schedule|calendar|hours|operating)|appointment\s*(schedule|calendar)|slot\s*availability|view\s*calendar)/i', $lower)) {
            return true;
        }

        return false;
    }

    private function handleScheduleViewQuery(string $msg, $user, string $lang) {
        // Clear any stuck intake so user is in clean state
        session()->forget('chatbot_intake');

        $capacity = (int) Setting::get('daily_appointment_capacity', 25);
        $today = Carbon::today();
        $todayCount = Appointment::whereDate('date', $today)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
        $slotsLeftToday = max(0, $capacity - $todayCount);
        $dateLabel = $this->formatDateLabel($today, $lang);

        $role = $user ? $user->role : 'guest';

        if ($lang === 'ms') {
            if (in_array($role, ['admin', 'coordinator'])) {
                $reply = "📅 **Kalendar & Jadual Slot Temujanji Bengkel (Teraju Setia Enterprise, Kluang)**\n\n"
                    . "• **Hari Operasi**: Ahad hingga Jumaat (Sabtu Tutup)\n"
                    . "• **Waktu Operasi**: Ahad – Khamis: 8:30 AM – 5:30 PM | Jumaat: 8:30 AM – 12:30 PM & 2:30 PM – 5:30 PM *(Rehat Solat Jumaat: 12:30 PM – 2:30 PM)*\n"
                    . "• **Kapasiti Harian**: Maksimum **{$capacity} kenderaan** sehari (Had 3 slot per jam)\n"
                    . "• **Status Hari Ini ({$dateLabel})**: **{$todayCount} slot dijadualkan** ({$slotsLeftToday} slot kosong tinggal)\n\n"
                    . "👇 **Kalendar Slot 14 Hari Dibuka Di Bawah**:\n"
                    . "Sila klik mana-mana tarikh pada kalendar interaktif untuk melihat pecahan masa & kekosongan slot.";
            } elseif ($role === 'corporate') {
                $reply = "📅 **Jadual Slot Servis Armada Korporat (Teraju Setia Enterprise, Kluang)**\n\n"
                    . "• **Hari Operasi**: Ahad – Jumaat (Sabtu Tutup)\n"
                    . "• **Waktu Operasi**: Ahad – Khamis: 8:30 AM – 5:30 PM | Jumaat: 8:30 AM – 12:30 PM & 2:30 PM – 5:30 PM\n"
                    . "• **Status Hari Ini ({$dateLabel})**: **{$slotsLeftToday} slot kosong** sedia untuk penerimaan kenderaan komersial.\n\n"
                    . "👇 **Pilih Slot Servis Armada Dari Kalendar Di Bawah**:\n"
                    . "Klik pada tarikh dan slot jam yang bersesuaian untuk menjadualkan lori atau van syarikat anda.";
            } else {
                $reply = "📅 **Kalendar & Jadual Temujanji Bengkel Teraju Setia Enterprise (Kluang)**\n\n"
                    . "• **Hari Operasi**: Ahad – Jumaat (Sabtu Tutup)\n"
                    . "• **Waktu Operasi**: Ahad – Khamis: 8:30 AM – 5:30 PM | Jumaat: 8:30 AM – 12:30 PM & 2:30 PM – 5:30 PM *(Rehat Solat: 12:30 PM – 2:30 PM)*\n"
                    . "• **Status Hari Ini ({$dateLabel})**: **{$slotsLeftToday} slot kosong** masih tersedia.\n\n"
                    . "👇 **Kalendar Interaktif di bawah sedia untuk anda**:\n"
                    . "Sila pilih tarikh dan slot jam yang anda inginkan. Anda boleh klik tarikh untuk melihat masa yang belum penuh!";
            }
        } else {
            $reply = "📅 **Teraju Setia Enterprise Workshop Schedule & Calendar (Kluang, Johor)**\n\n"
                . "• **Operating Days**: Sunday through Friday (Saturday Closed)\n"
                . "• **Operating Hours**: Sun – Thu: 8:30 AM – 5:30 PM | Fri: 8:30 AM – 12:30 PM & 2:30 PM – 5:30 PM (Friday prayer break: 12:30 PM – 2:30 PM)\n"
                . "• **Daily Capacity**: Maximum **{$capacity} vehicles/day** (Max 3 slots per hourly window)\n"
                . "• **Today's Status ({$dateLabel})**: **{$todayCount} booked** ({$slotsLeftToday} slots remaining)\n\n"
                . "👇 **Interactive 14-Day Calendar Available Below**:\n"
                . "Please click on any date below to browse available hourly arrival windows.";
        }

        return response()->json([
            'reply'         => $reply,
            'action'        => 'select_slot',
            'show_calendar' => true,
            'lang'          => $lang,
        ]);
    }

    // ----------------------------------------------------------------
    // Thorough Automotive Diagnostic Intake Flow
    // ----------------------------------------------------------------

    private function handleBookingConversation(string $message, $user, string $lang, ?array $userContext = null) {
        $intake = session('chatbot_intake', []);

        // 1. Autofill user profile if logged in
        if ($user) {
            if (empty($intake['customer_name'])) {
                $intake['customer_name'] = $user->name;
            }
            if (empty($intake['phone']) && !empty($user->contact_no)) {
                $intake['phone'] = $user->contact_no;
            }
        }

        // 2. Validate and extract phone number
        $phoneResult = $this->validatePhone($message);
        if ($phoneResult['status'] === 'valid') {
            $intake['phone'] = $phoneResult['phone'];
        }

        // 3. Extract Malaysian Plate Number
        if (preg_match('/(tiada\s*plat|tak\s*ada\s*plat|takde\s*plat|no\s*plate|unknown\s*plate|tak\s*tahu\s*plat)/i', $message)) {
            if (empty($intake['plate_number'])) {
                $intake['plate_number'] = 'WALK-IN-' . rand(1000, 9999);
            }
        } elseif (preg_match('/(?:plat|no\.?\s*plat|pendaftaran)\s*[:=]?\s*([A-Z]{1,3}\s*\d{1,4}\s*[A-Z]?)/i', $message, $explicitPlate)) {
            $intake['plate_number'] = strtoupper(preg_replace('/\s+/', ' ', trim($explicitPlate[1])));
        } elseif (empty($intake['plate_number'])) {
            // Exclude non-plate words like JAM, HARI, SESI, BULAN, TAHUN, DAN, DARI
            if (preg_match('/(?<!\w)(?!(?:at|in|on|by|to|for|is|am|pm|an|no|po|km|rm|jam|hari|sesi|bulan|tahun|dan|dari)\b)[A-Z]{1,3}\s*\d{1,4}\s*[A-Z]?(?!\w)/i', $message, $pm)) {
                $intake['plate_number'] = strtoupper(preg_replace('/\s+/', ' ', trim($pm[0])));
            }
        }

        // 4. Extract Customer Name
        if (preg_match('/(?:nama\s*saya|nama\s*sy|nama|my\s*name\s*is|i\s*am)\s*[:=]?\s*([A-Za-z\s\.\@\']{2,30})/i', $message, $nm)) {
            $intake['customer_name'] = trim($nm[1]);
        } elseif (empty($intake['customer_name']) && ($phoneResult['status'] === 'valid' || (!empty($intake['symptoms']) && !empty($intake['car_brand']) && !$this->isBookingIntent($message)))) {
            // Only infer customer name if accompanied by a valid phone or when specifically prompted in later intake steps
            $cleaned = $message;
            if ($phoneResult['status'] === 'valid') {
                $cleaned = str_replace($phoneResult['raw'], '', $cleaned);
            }
            if (!empty($intake['plate_number'])) {
                $cleaned = str_ireplace($intake['plate_number'], '', $cleaned);
                $cleaned = str_ireplace(str_replace(' ', '', $intake['plate_number']), '', $cleaned);
            }
            $cleaned = preg_replace('/\b(perodua|proton|honda|toyota|nissan|mazda|ford|isuzu|serena|myvi|vios|alza|saga|bezza|axia|city|civic|hilux|d-?max)\b/i', '', $cleaned);
            $cleaned = preg_replace('/\b(plat|no|tel|phone|hp|esok|hari|pagi|petang|malam|jam|pukul|walk-?in|temujanji|booking|book|nak|saya|sy|datang)\b/i', '', $cleaned);
            $cleaned = preg_replace('/\b(lah|la|je|jer|aje|sahaja|pun|kan|kot|dah|dulu|tu|ni|ya|yes|ok|okay|tq|terima\s*kasih|thanks)\b/i', '', $cleaned);
            $cleaned = trim(preg_replace('/[^a-zA-Z\s\.\']/', '', $cleaned));

            if (strlen($cleaned) >= 2 && strlen($cleaned) <= 30 && preg_match('/^[a-zA-Z\s\.\']{2,30}$/', $cleaned)) {
                $words = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', $cleaned))));
                if (count($words) >= 1 && count($words) <= 4) {
                    $intake['customer_name'] = ucwords(strtolower(implode(' ', $words)));
                }
            }
        }

        // 5. Extract Car Make & Model
        $vehicleSpecs = $this->extractVehicleDetails($message);
        if (!empty($vehicleSpecs['brand'])) {
            $intake['car_brand'] = $vehicleSpecs['brand'];
        }
        if (!empty($vehicleSpecs['model'])) {
            $intake['car_model'] = $vehicleSpecs['model'];
        }
        if (!empty($vehicleSpecs['year'])) {
            $intake['car_year'] = $vehicleSpecs['year'];
        }
        if (!empty($vehicleSpecs['mileage'])) {
            $intake['mileage'] = $vehicleSpecs['mileage'];
        }

        // If user is logged in and has registered vehicles, check if plate matches
        if ($user && empty($intake['plate_number'])) {
            $ids = $this->scopeUserIds($user);
            $userVehicles = Vehicle::whereIn('user_id', $ids)->get();
            foreach ($userVehicles as $uv) {
                if (str_contains(strtoupper($message), strtoupper(str_replace(' ', '', $uv->plate_number)))) {
                    $intake['plate_number'] = $uv->plate_number;
                    $intake['car_brand']    = $uv->brand;
                    $intake['car_model']    = $uv->model;
                    $intake['car_year']     = $uv->year;
                    $intake['mileage']      = number_format($uv->mileage) . ' km';
                    break;
                }
            }
        }

        // Check agreement to previous suggestion (e.g. "sure lets do that", "jom", "setuju")
        if (preg_match('/^(sure\s*lets\s*do\s*that|lets\s*do\s*that|sure|lets\s*do\s*it|yes\s*please|jom\s*buat|boleh\s*buat|jom|setuju|proceed)$/i', trim($message))) {
            if (empty($intake['service_type'])) {
                $intake['service_type'] = 'Engine Diagnostics Scan';
            }
            if (empty($intake['symptoms'])) {
                $intake['symptoms'] = 'Kenderaan terasa luar biasa / uncharacteristic driving feel & diagnostic scan request';
            }
        }

        // 6. Extract Symptoms, Issues & Diagnostic Complaints
        $symptomInfo = $this->extractSymptomsAndIssues($message);
        if (!empty($symptomInfo['symptoms'])) {
            if (!empty($intake['symptoms'])) {
                $intake['symptoms'] .= " • " . $symptomInfo['symptoms'];
            } else {
                $intake['symptoms'] = $symptomInfo['symptoms'];
            }
        }
        if (!empty($symptomInfo['service_type'])) {
            $intake['service_type'] = $symptomInfo['service_type'];
        }

        // 7. Extract Preferred Date & Time
        $lowerMsg = strtolower($message);
        $hasDateCue = (bool) (
            preg_match('/(esok|lusa|isnin|selasa|rabu|khamis|jumaat|sabtu|tomorrow|monday|tuesday|wednesday|thursday|friday|saturday)/i', $lowerMsg)
            || preg_match('/\b\d{1,2}[-\/]\d{1,2}(?:[-\/]\d{2,4})?\b/', $lowerMsg)
            || preg_match('/\b\d{4}[-\/]\d{1,2}[-\/]\d{1,2}\b/', $lowerMsg)
        );
        $hasTimeCue = (bool) (
            preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm|pagi|petang|tengahari|malam)/i', $lowerMsg)
            || preg_match('/(?:jam|pukul)\s*(\d{1,2})/i', $lowerMsg)
        );

        if ($hasDateCue) {
            $targetDate = $this->extractDate($message);

            if ($targetDate->isSaturday()) {
                $intake['date'] = null;
                session(['chatbot_intake' => $intake]);
                $saturdayWarn = $lang === 'ms'
                    ? "⚠️ **Bengkel Tutup Pada Hari Sabtu**: Bengkel Teraju Setia Enterprise di Kluang beroperasi Ahad hingga Jumaat (Sabtu Tutup). Sila pilih tarikh lain seperti **Ahad, " . $targetDate->copy()->addDay()->format('d M Y') . "** atau pilih dari kalendar interaktif di bawah."
                    : "⚠️ **Workshop Closed on Saturdays**: Teraju Setia Enterprise in Kluang operates Sunday through Friday (Closed on Saturdays). Please select another date such as **Sunday, " . $targetDate->copy()->addDay()->format('d M Y') . "** or choose from the interactive schedule below.";

                return response()->json([
                    'reply'         => $saturdayWarn,
                    'action'        => 'select_slot',
                    'show_calendar' => true,
                    'lang'          => $lang,
                ]);
            }

            $intake['date']       = $targetDate->toDateString();
            $intake['date_label'] = $this->formatDateLabel($targetDate, $lang);
        }

        if ($hasTimeCue) {
            $targetTime = $this->extractTime($message);

            // Adjust for Friday prayer break (12:30 PM - 2:30 PM)
            $checkDate = !empty($intake['date']) ? Carbon::parse($intake['date']) : Carbon::tomorrow();
            if ($checkDate->isFriday()) {
                $hour = (int) Carbon::parse($targetTime)->format('H');
                $min  = (int) Carbon::parse($targetTime)->format('i');
                $totalMin = $hour * 60 + $min;
                if ($totalMin >= 750 && $totalMin < 870) {
                    $targetTime = '14:30:00';
                }
            }

            $intake['time']       = $targetTime;
            $intake['time_label'] = Carbon::parse($targetTime)->format('g:i A');
        }

        // Save current intake state in session
        session(['chatbot_intake' => $intake]);

        // ------------------------------------------------------------
        // Evaluate Missing Information & Question Thoroughly!
        // ------------------------------------------------------------

        // Check A: Symptoms / Problem Details missing?
        // A mechanic MUST know the issue to prepare the right parts and tools!
        if (empty($intake['symptoms'])) {
            // Check if user is asking a general question (e.g. price, location, hours) instead of describing symptoms
            if (str_contains($message, '?') || preg_match('/(berapa|harga|kos|kat\s*mana|lokasi|apa|how\s*much|cost|price|where)\b/i', $message)) {
                if ($geminiReply = $this->tryGemini($message, $user, $lang, $userContext)) {
                    $note = $lang === 'ms'
                        ? "\n\n*(Nota: Sesi tempahan temujanji anda masih dibuka. Sila kongsikan masalah kenderaan atau servis yang anda inginkan, atau taip 'batal' untuk membatalkan tempahan.)*"
                        : "\n\n*(Note: Your appointment booking is still active. Please share your vehicle symptoms or required service to proceed, or type 'cancel' to cancel.)*";
                    return response()->json(['reply' => $geminiReply . $note, 'lang' => $lang]);
                }
            }

            // Build helpful prompt acknowledging any details already provided
            $acknowledged = [];
            if (!empty($intake['car_brand'])) {
                $acknowledged[] = trim(($intake['car_brand'] ?? '') . ' ' . ($intake['car_model'] ?? ''));
            }
            if (!empty($intake['date_label'])) {
                $acknowledged[] = $intake['date_label'];
            }
            $intro = '';
            if (!empty($acknowledged)) {
                $intro = $lang === 'ms'
                    ? "Maklumat **" . implode(' & ', $acknowledged) . "** telah direkodkan. 👍\n\n"
                    : "Details for **" . implode(' & ', $acknowledged) . "** have been noted. 👍\n\n";
            }

            $q = $lang === 'ms'
                ? $intro . "Boleh kongsikan lebih lanjut mengenai masalah atau simptom kenderaan anda?\n\n"
                . "• **Apakah masalah atau simptom yang dialami?** (Contoh: *kereta rasa sengguk-sengguk / jerking*, *enjin semput semasa memecut*, *brek berdecit*, atau *lampu amaran menyala*?)\n"
                . "• **Bila ia berlaku?** (Contoh: waktu pagi, semasa membelok, atau semasa kelajuan tinggi?)\n"
                . "• *Atau adakah ini servis berkala biasa (tukar minyak hitam & penapis)?*"
                : $intro . "Could you tell us more about what issue or symptoms your vehicle is experiencing?\n\n"
                . "• **What symptoms or faults are noticeable?** (e.g., *car jerking / sengguk-sengguk*, *engine hesitation on acceleration*, *brake squeal*, or *warning lights*?)\n"
                . "• **When does it occur?** (e.g., cold start morning, when turning, or at highway speed?)\n"
                . "• *Or is this routine periodic maintenance (engine oil & filter change)?*";

            return response()->json(['reply' => $q, 'lang' => $lang]);
        }

        // Check B: Vehicle Details (Plate Number & Make/Model) missing?
        if (empty($intake['plate_number']) || empty($intake['car_brand'])) {
            // Check if user is asking an unrelated question instead of giving vehicle details
            if (empty($vehicleSpecs['brand']) && empty($intake['plate_number']) && (str_contains($message, '?') || preg_match('/(berapa|harga|kos|bila|kat\s*mana|lokasi|apa|how\s*much|cost|price|where|when)/i', $message))) {
                if ($geminiReply = $this->tryGemini($message, $user, $lang, $userContext)) {
                    $note = $lang === 'ms'
                        ? "\n\n*(Nota: Sesi tempahan temujanji anda masih dibuka. Sila berikan nombor plat & model kenderaan anda untuk teruskan, atau taip 'batal' untuk membatalkan tempahan.)*"
                        : "\n\n*(Note: Your appointment booking is still active. Please share your vehicle make, model and plate number to proceed, or type 'cancel' to cancel.)*";
                    return response()->json(['reply' => $geminiReply . $note, 'lang' => $lang]);
                }
            }

            $missingPieces = [];
            if (empty($intake['plate_number'])) $missingPieces[] = $lang === 'ms' ? 'Nombor Plat Pendaftaran' : 'Plate Number';
            if (empty($intake['car_brand']))    $missingPieces[] = $lang === 'ms' ? 'Model Kereta (contoh: Nissan Serena, Perodua Myvi)' : 'Car Make & Model (e.g. Nissan Serena, Perodua Myvi)';

            $missingText = implode($lang === 'ms' ? ' dan ' : ' and ', $missingPieces);
            $carKnown = !empty($intake['car_brand']) ? trim(($intake['car_brand'] ?? '') . ' ' . ($intake['car_model'] ?? '')) : null;

            $q = $lang === 'ms'
                ? ($carKnown
                    ? "Terima kasih atas penerangan simptom tersebut! Sila berikan **Nombor Plat Pendaftaran** bagi **{$carKnown}** anda (contoh: *JQR 1234*, atau taip *tiada plat* jika walk-in tanpa rekod plat):"
                    : "Terima kasih atas penerangan simptom tersebut! Bagi membolehkan mekanik kami menyediakan alat ganti yang tepat, sila berikan **{$missingText}** kenderaan anda.\n\nContoh: *Nissan Serena, No Plat: JQR 1234* *(atau taip 'tiada plat' jika walk-in)*")
                : ($carKnown
                    ? "Thank you for explaining the issue! Please provide the **Plate Number** for your **{$carKnown}** (e.g. *JQR 1234*, or type *no plate* if walk-in without plate):"
                    : "Thank you for explaining the issue! To ensure our mechanics prepare the correct parts and tools, please provide your **{$missingText}**.\n\nExample: *Nissan Serena, Plate: JQR 1234* *(or type 'no plate' if unknown)*");

            return response()->json(['reply' => $q, 'lang' => $lang]);
        }

        // Check C: Customer Contact (Name & Valid Phone Number) missing?
        if (empty($intake['customer_name']) || empty($intake['phone'])) {
            $missingContact = [];
            if (empty($intake['customer_name'])) $missingContact[] = $lang === 'ms' ? 'Nama Anda' : 'Your Full Name';
            if (empty($intake['phone']))         $missingContact[] = $lang === 'ms' ? 'Nombor Telefon Bimbit (10-11 digit)' : 'Mobile Phone Number (10-11 digits)';

            $contactText = implode($lang === 'ms' ? ' dan ' : ' and ', $missingContact);

            $q = $lang === 'ms'
                ? "Sila berikan **{$contactText}** supaya pihak kaunter bengkel dapat mengesahkan slot temujanji anda.\n\nContoh: *Ahmad Faris, 012-3456789*"
                : "Please provide **{$contactText}** so our service reception can confirm your appointment slot.\n\nExample: *Ahmad Faris, 012-3456789*";

            return response()->json(['reply' => $q, 'lang' => $lang]);
        }

        // Check D: Date & Time Slot missing?
        if (empty($intake['date']) || empty($intake['time'])) {
            $missingDateTime = [];
            if (empty($intake['date'])) $missingDateTime[] = $lang === 'ms' ? 'tarikh temujanji' : 'appointment date';
            if (empty($intake['time'])) $missingDateTime[] = $lang === 'ms' ? 'masa kedatangan (contoh: 9 pagi)' : 'arrival time (e.g. 9 AM)';

            $q = $lang === 'ms'
                ? "Semua maklumat kenderaan dan simptom telah lengkap direkodkan! 🚗\n\n"
                . "Sila nyatakan " . implode(' dan ', $missingDateTime) . " untuk kenderaan dibawa ke bengkel Teraju Setia di Kluang.\n"
                . "Anda boleh pilih tarikh dan slot daripada kalendar di bawah, atau terus taip (contoh: *Esok jam 9 pagi*):"
                : "All vehicle diagnostic details have been captured! 🚗\n\n"
                . "Please specify your " . implode(' and ', $missingDateTime) . " to bring your vehicle into our workshop in Kluang.\n"
                . "You can pick a slot from the schedule below, or type your preferred time (e.g., *Tomorrow at 9 AM*):";

            return response()->json([
                'reply'         => $q,
                'action'        => 'select_slot',
                'show_calendar' => true,
                'lang'          => $lang,
            ]);
        }

        // ------------------------------------------------------------
        // ALL Information Is Gathered -> Draft Comprehensive Diagnostic Intake!
        // ------------------------------------------------------------

        $brandModel = trim(($intake['car_brand'] ?? 'Customer Vehicle') . ' ' . ($intake['car_model'] ?? 'Standard'));
        $yearStr    = !empty($intake['car_year']) ? " ({$intake['car_year']})" : "";
        $mileageStr = !empty($intake['mileage']) ? " &bull; Jarak: {$intake['mileage']}" : "";
        $serviceType = $intake['service_type'] ?? 'General Vehicle Maintenance & Inspection';

        $diagnosticBrief = "=== TERAJU WORKSHOP INTAKE & DIAGNOSTIC BRIEF ===\n"
            . "• Vehicle: {$brandModel}{$yearStr}" . (!empty($intake['mileage']) ? " [{$intake['mileage']}]" : "") . "\n"
            . "• Registration Plate: {$intake['plate_number']}\n"
            . "• Customer Complaint & Symptoms: {$intake['symptoms']}\n"
            . "• Recommended Service / Inspection: {$serviceType}\n"
            . "• Customer: {$intake['customer_name']} ({$intake['phone']})\n"
            . "• Appointment: {$intake['date_label']} at {$intake['time_label']}\n"
            . "• Intake Source: Public AI Assistant Intake Flow";

        $draft = [
            'is_guest'         => !$user,
            'customer_name'    => $intake['customer_name'],
            'phone'            => $intake['phone'],
            'plate_number'     => $intake['plate_number'],
            'car_brand'        => $intake['car_brand'] ?? 'Customer Vehicle',
            'car_model'        => $intake['car_model'] ?? 'Standard',
            'car_year'         => $intake['car_year'] ?? date('Y'),
            'mileage'          => $intake['mileage'] ?? '0 km',
            'symptoms'         => $intake['symptoms'],
            'service_type'     => $serviceType,
            'date'             => $intake['date'],
            'date_label'       => $intake['date_label'],
            'time'             => $intake['time'],
            'time_label'       => $intake['time_label'],
            'diagnostic_notes' => $diagnosticBrief,
        ];

        session(['pending_chatbot_booking' => $draft]);

        $summary = $lang === 'ms'
            ? "📋 **Ringkasan Maklumat Tempahan & Pra-Diagnostik Bengkel**\n\n"
            . "Terima kasih **{$draft['customer_name']}**! Maklumat temujanji kenderaan anda telah lengkap direkodkan untuk mekanik kami:\n\n"
            . "• 🚗 **Kenderaan**: **{$brandModel}**{$yearStr} &bull; No Plat: **`{$draft['plate_number']}`**\n"
            . (!empty($draft['mileage']) && $draft['mileage'] !== '0 km' ? "• 📏 **Odometer**: {$draft['mileage']}\n" : "")
            . "• 🔧 **Aduan & Simptom Kerosakan**:\n  _{$draft['symptoms']}_\n"
            . "• 🛠️ **Servis Disyorkan**: **{$draft['service_type']}**\n"
            . "• 📅 **Tarikh & Masa**: **{$draft['date_label']}** jam **{$draft['time_label']}**\n"
            . "• 👤 **Maklumat Pelanggan**: {$draft['customer_name']} ({$draft['phone']})\n\n"
            . "🔍 **Adakah semua maklumat di atas tepat dan betul?**\n\n"
            . "• **Jika betul**: Sila klik butang **✓ Ya, Sahkan Tempahan** di bawah untuk memasukkan slot ini ke dalam jadual bengkel kami.\n"
            . "• **Jika ada yang perlu diubah**: Sila klik butang pilihan di bawah (*Ubah Tarikh/Masa*, *Ubah Kenderaan*, atau *Ubah Servis*), atau beritahu saya maklumat yang ingin dibetulkan."
            : "📋 **Appointment Summary & Workshop Diagnostic Brief**\n\n"
            . "Thank you **{$draft['customer_name']}**! Your vehicle intake details have been prepared for our mechanics:\n\n"
            . "• 🚗 **Vehicle**: **{$brandModel}**{$yearStr} &bull; Plate: **`{$draft['plate_number']}`**\n"
            . (!empty($draft['mileage']) && $draft['mileage'] !== '0 km' ? "• 📏 **Mileage**: {$draft['mileage']}\n" : "")
            . "• 🔧 **Customer Complaint & Symptoms**:\n  _{$draft['symptoms']}_\n"
            . "• 🛠️ **Recommended Service**: **{$draft['service_type']}**\n"
            . "• 📅 **Scheduled Slot**: **{$draft['date_label']}** at **{$draft['time_label']}**\n"
            . "• 👤 **Customer Contact**: {$draft['customer_name']} ({$draft['phone']})\n\n"
            . "🔍 **Is all the information above correct?**\n\n"
            . "• **If correct**: Please click **✓ Yes, Confirm Booking** below to secure your appointment slot.\n"
            . "• **If anything needs to be updated**: Click one of the edit buttons below (*Change Date/Time*, *Change Vehicle*, or *Change Service*), or tell me what to update.";

        return response()->json([
            'reply'   => $summary,
            'action'  => 'confirm_booking',
            'booking' => $draft,
            'lang'    => $lang,
        ]);
    }

    private function executeBooking(array $booking, $user, string $lang) {
        $capacity = (int) Setting::get('daily_appointment_capacity', 25);
        $count = Appointment::whereDate('date', $booking['date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        if ($count >= $capacity) {
            $msg = $lang === 'ms'
                ? "Maaf, tarikh {$booking['date_label']} telah penuh (mencapai had kapasiti bengkel). Sila pilih tarikh lain dari kalendar atau hubungi kaunter kami di 07-787 8728."
                : "Sorry, {$booking['date_label']} is currently fully booked (workshop capacity reached). Please select another date from the calendar or contact our desk at 07-787 8728.";

            return response()->json([
                'reply'         => $msg,
                'action'        => 'select_slot',
                'show_calendar' => true,
                'lang'          => $lang,
            ]);
        }

        // 1. Create or Update Vehicle with REAL specs (Make, Model, Year, Mileage)
        $cleanPlate = strtoupper(trim(preg_replace('/\s+/', ' ', $booking['plate_number'])));
        $vehicle = Vehicle::where('plate_number', $cleanPlate)->first();

        if (!$vehicle) {
            $vehicle = new Vehicle();
            $vehicle->plate_number = $cleanPlate;
            $vehicle->user_id      = $user ? $user->id : null;
        }

        if (!empty($booking['car_brand'])) {
            $vehicle->brand = $booking['car_brand'];
        }
        if (!empty($booking['car_model'])) {
            $vehicle->model = $booking['car_model'];
        }
        if (!empty($booking['car_year'])) {
            $vehicle->year = (int) $booking['car_year'];
        }
        if (!empty($booking['mileage'])) {
            $mileageNum = (int) preg_replace('/[^\d]/', '', $booking['mileage']);
            if ($mileageNum > 0) {
                $vehicle->mileage = $mileageNum;
            }
        }
        $vehicle->save();

        // 2. Create Appointment with full diagnostic notes for the mechanic!
        $appointment = Appointment::create([
            'user_id'        => $user ? $user->id : null,
            'vehicle_id'     => $vehicle->id,
            'date'           => $booking['date'],
            'time'           => $booking['time'],
            'service_type'   => $booking['service_type'],
            'notes'          => $booking['diagnostic_notes'],
            'status'         => $user ? 'pending' : 'confirmed',
            'is_walkin'      => $user ? false : true,
            'walkin_name'    => $booking['customer_name'] ?: ($user ? $user->name : 'Walk-in Customer'),
            'walkin_contact' => $booking['phone'] ?: ($user ? $user->contact_no : '—'),
        ]);

        // Send notifications if user is logged in
        if ($user) {
            $prefix = $user->role === 'corporate' ? '/client' : '/customer';
            Notification::send(
                $user->id,
                'Appointment Submitted',
                "Your {$appointment->service_type} appointment for {$vehicle->plate_number} on " . Carbon::parse($appointment->date)->format('d M Y') . " has been submitted.",
                "{$prefix}/appointments/{$appointment->id}"
            );

            if ($user->email) {
                try {
                    Mail::to($user->email)->send(new AppointmentStatusMail($appointment, 'Submitted'));
                } catch (\Throwable $e) {
                    Log::warning('Chatbot appointment confirmation email failed: ' . $e->getMessage());
                }
            }
        }

        // Clean up session intake
        session()->forget(['pending_chatbot_booking', 'chatbot_intake', 'pending_booking_time']);

        $ref = '#APT-' . str_pad($appointment->id, 5, '0', STR_PAD_LEFT);
        $brandModel = trim("{$vehicle->brand} {$vehicle->model}");

        if ($lang === 'ms') {
            $reply = "🎉 **Tempahan Berjaya Disahkan & Direkodkan!**\n\n"
                   . "• **No Rujukan**: `{$ref}`\n"
                   . "• **Pelanggan**: **{$appointment->walkin_name}** ({$appointment->walkin_contact})\n"
                   . "• **Kenderaan**: **{$vehicle->plate_number}** ({$brandModel} {$vehicle->year})\n"
                   . "• **Servis**: **{$appointment->service_type}**\n"
                   . "• **Tarikh & Masa**: **{$booking['date_label']}** pada jam **{$booking['time_label']}**\n\n"
                   . "📋 **Catatan Pra-Diagnostik Mekanik**:\n"
                   . "_{$booking['symptoms']}_\n\n"
                   . "Maklumat lengkap kerosakan dan keperluan alat ganti telah dimasukkan ke dalam sistem giliran bengkel kami. Sila bawa kenderaan anda ke **Teraju Setia Enterprise di Kluang, Johor** pada masa yang ditetapkan.\n\n"
                   . "Terima kasih kerana memilih Teraju Setia Enterprise! Pandu cermat! 🚗💨";
        } else {
            $reply = "🎉 **Appointment Successfully Confirmed!**\n\n"
                   . "• **Booking Reference**: `{$ref}`\n"
                   . "• **Customer**: **{$appointment->walkin_name}** ({$appointment->walkin_contact})\n"
                   . "• **Vehicle**: **{$vehicle->plate_number}** ({$brandModel} {$vehicle->year})\n"
                   . "• **Service**: **{$appointment->service_type}**\n"
                   . "• **Scheduled Time**: **{$booking['date_label']}** at **{$booking['time_label']}**\n\n"
                   . "📋 **Mechanic Diagnostic Notes**:\n"
                   . "_{$booking['symptoms']}_\n\n"
                   . "Your appointment and detailed diagnostic brief have been registered in our workshop queue. Please bring your vehicle to **Teraju Setia Enterprise in Kluang, Johor** at your scheduled time.\n\n"
                   . "Thank you for choosing Teraju Setia Enterprise! Drive safe! 🚗💨";
        }

        return response()->json([
            'reply'             => $reply,
            'booking_confirmed' => true,
            'lang'              => $lang,
        ]);
    }

    // ----------------------------------------------------------------
    // Extraction Helpers
    // ----------------------------------------------------------------

    private function extractVehicleDetails(string $text): array {
        $result = ['brand' => null, 'model' => null, 'year' => null, 'mileage' => null];

        $brands = [
            'perodua' => 'Perodua', 'proton' => 'Proton', 'honda' => 'Honda', 'toyota' => 'Toyota',
            'nissan' => 'Nissan', 'mazda' => 'Mazda', 'ford' => 'Ford', 'hyundai' => 'Hyundai',
            'kia' => 'Kia', 'mitsubishi' => 'Mitsubishi', 'bmw' => 'BMW', 'mercedes' => 'Mercedes-Benz',
            'isuzu' => 'Isuzu', 'hino' => 'Hino', 'subaru' => 'Subaru', 'suzuki' => 'Suzuki',
            'chery' => 'Chery', 'byd' => 'BYD', 'volvo' => 'Volvo', 'peugeot' => 'Peugeot',
        ];

        $models = [
            'myvi' => ['brand' => 'Perodua', 'model' => 'Myvi'],
            'axia' => ['brand' => 'Perodua', 'model' => 'Axia'],
            'bezza' => ['brand' => 'Perodua', 'model' => 'Bezza'],
            'alza' => ['brand' => 'Perodua', 'model' => 'Alza'],
            'ativa' => ['brand' => 'Perodua', 'model' => 'Ativa'],
            'aruz' => ['brand' => 'Perodua', 'model' => 'Aruz'],
            'viva' => ['brand' => 'Perodua', 'model' => 'Viva'],
            'kancil' => ['brand' => 'Perodua', 'model' => 'Kancil'],
            'kelisa' => ['brand' => 'Perodua', 'model' => 'Kelisa'],
            'kenari' => ['brand' => 'Perodua', 'model' => 'Kenari'],
            'saga' => ['brand' => 'Proton', 'model' => 'Saga'],
            'persona' => ['brand' => 'Proton', 'model' => 'Persona'],
            'iriz' => ['brand' => 'Proton', 'model' => 'Iriz'],
            'exora' => ['brand' => 'Proton', 'model' => 'Exora'],
            'x50' => ['brand' => 'Proton', 'model' => 'X50'],
            'x70' => ['brand' => 'Proton', 'model' => 'X70'],
            'x90' => ['brand' => 'Proton', 'model' => 'X90'],
            's70' => ['brand' => 'Proton', 'model' => 'S70'],
            'wira' => ['brand' => 'Proton', 'model' => 'Wira'],
            'waja' => ['brand' => 'Proton', 'model' => 'Waja'],
            'preve' => ['brand' => 'Proton', 'model' => 'Preve'],
            'city' => ['brand' => 'Honda', 'model' => 'City'],
            'civic' => ['brand' => 'Honda', 'model' => 'Civic'],
            'hrv' => ['brand' => 'Honda', 'model' => 'HR-V'],
            'hr-v' => ['brand' => 'Honda', 'model' => 'HR-V'],
            'crv' => ['brand' => 'Honda', 'model' => 'CR-V'],
            'cr-v' => ['brand' => 'Honda', 'model' => 'CR-V'],
            'accord' => ['brand' => 'Honda', 'model' => 'Accord'],
            'jazz' => ['brand' => 'Honda', 'model' => 'Jazz'],
            'brv' => ['brand' => 'Honda', 'model' => 'BR-V'],
            'br-v' => ['brand' => 'Honda', 'model' => 'BR-V'],
            'vios' => ['brand' => 'Toyota', 'model' => 'Vios'],
            'yaris' => ['brand' => 'Toyota', 'model' => 'Yaris'],
            'corolla' => ['brand' => 'Toyota', 'model' => 'Corolla'],
            'camry' => ['brand' => 'Toyota', 'model' => 'Camry'],
            'hilux' => ['brand' => 'Toyota', 'model' => 'Hilux'],
            'innova' => ['brand' => 'Toyota', 'model' => 'Innova'],
            'avanza' => ['brand' => 'Toyota', 'model' => 'Avanza'],
            'veloz' => ['brand' => 'Toyota', 'model' => 'Veloz'],
            'fortuner' => ['brand' => 'Toyota', 'model' => 'Fortuner'],
            'hiace' => ['brand' => 'Toyota', 'model' => 'Hiace'],
            'almera' => ['brand' => 'Nissan', 'model' => 'Almera'],
            'navara' => ['brand' => 'Nissan', 'model' => 'Navara'],
            'serena' => ['brand' => 'Nissan', 'model' => 'Serena'],
            'd-max' => ['brand' => 'Isuzu', 'model' => 'D-Max'],
            'dmax' => ['brand' => 'Isuzu', 'model' => 'D-Max'],
            'triton' => ['brand' => 'Mitsubishi', 'model' => 'Triton'],
            'ranger' => ['brand' => 'Ford', 'model' => 'Ranger'],
        ];

        $lower = strtolower($text);

        foreach ($models as $mKey => $info) {
            if (preg_match('/\b' . preg_quote($mKey, '/') . '\b/i', $lower)) {
                $result['brand'] = $info['brand'];
                $result['model'] = $info['model'];
                break;
            }
        }

        if (empty($result['brand'])) {
            foreach ($brands as $bKey => $bName) {
                if (preg_match('/\b' . preg_quote($bKey, '/') . '\b/i', $lower)) {
                    $result['brand'] = $bName;
                    break;
                }
            }
        }

        // Year extraction (e.g. 1980 to 2026, avoiding date strings like 26/10/2026)
        if (!preg_match('/[-\/]\d{4}\b|\b\d{4}[-\/]/', $text)) {
            if (preg_match('/\b(19[89]\d|20[01]\d|202[0-6])\b/', $text, $ym)) {
                $result['year'] = $ym[1];
            }
        } elseif (preg_match('/(?:tahun|year)\s*[:=]?\s*(19[89]\d|20[01]\d|202[0-6])\b/i', $text, $ymExplicit)) {
            $result['year'] = $ymExplicit[1];
        }

        // Mileage extraction (e.g. "80000 km", "65,000 km", "perbatuan 85000")
        if (preg_match('/(?:perbatuan|mileage|odometer)\s*[:=]?\s*(\d{1,3}(?:,\d{3})+|\d{4,6})\s*(?:km)?/i', $text, $km1)) {
            $result['mileage'] = $km1[1] . ' km';
        } elseif (preg_match('/\b(\d{1,3}(?:,\d{3})+|\d{4,6})\s*(?:km|kilometer)\b/i', $text, $km2)) {
            $result['mileage'] = $km2[1] . ' km';
        }

        return $result;
    }

    private function extractSymptomsAndIssues(string $text): array {
        $lower = strtolower($text);
        $symptoms = [];
        $serviceType = null;

        // Brakes
        if (preg_match('/(brek|brake|decit|berdecit|squeal|pad haus|rotor|disc|pedal lembut|spongy)/i', $lower)) {
            $symptoms[] = 'Brake system issue (squealing noise, reduced bite, or spongy pedal)';
            $serviceType = 'Brake Inspection & Replacement';
        }

        // Engine & Hesitation / Jerking
        if (preg_match('/(enjin|engine|semput|senguk|sengguk|tersengguk|jerking|knocking|ketuk|gegar|shaking|vibrat|mati sendiri|stalling|misfire|asap|overheat|panas)/i', $lower)) {
            $symptoms[] = 'Masalah enjin tersengguk-sengguk / jerking / semput (Engine Diagnostics Scan & Troubleshooting)';
            $serviceType = $serviceType ?: 'Engine Diagnostics & Troubleshooting';
        }

        // Air Conditioning
        if (preg_match('/(aircond|air-cond|ac|tak sejuk|kurang sejuk|panas|angin je|gas bocor|compressor)/i', $lower)) {
            $symptoms[] = 'Air conditioning warm / blowing air without cooling / gas pressure check';
            $serviceType = $serviceType ?: 'Air Conditioning Servicing';
        }

        // Suspension & Undercarriage
        if (preg_match('/(absorber|suspension|hantuk|bunyi ketuk|clunk|gegar bila jalan tak rata|steering gegar|bush)/i', $lower)) {
            $symptoms[] = 'Suspension knocking noise / steering wobble over road bumps';
            $serviceType = $serviceType ?: 'Suspension & Undercarriage Inspection';
        }

        // Tyres & Alignment
        if (preg_match('/(tayar|tire|tyre|alignment|balancing|makan tepi|haus sebelah)/i', $lower)) {
            $symptoms[] = 'Tyre wear unevenness / steering alignment pull';
            $serviceType = $serviceType ?: 'Tyre Alignment & Balancing';
        }

        // Battery & Electrical
        if (preg_match('/(bateri|battery|tak boleh start|susah start|alternator|kong)/i', $lower)) {
            $symptoms[] = 'Battery cranking weakness / electrical charging inspection';
            $serviceType = $serviceType ?: 'Battery Replacement & Check';
        }

        // Routine / Periodic Service
        if (preg_match('/(servis berkala|periodic service|minyak hitam|tukar minyak|oil change|major service|minor service)/i', $lower)) {
            $symptoms[] = 'Scheduled preventive maintenance (oil, fluids, and multipoint inspection)';
            $serviceType = $serviceType ?: 'Engine Oil & Filter Service';
        }

        // General / Unusual Driving Feel & Diagnostics ("kereta rasa pelik", "pelik", "weird", "strange", "unusual")
        if (preg_match('/(pelik|weird|strange|unusual|rasa pelik|bunyi pelik|tak sedap|tak kena|driving feel|diagnostic|diagnostik)/i', $lower)) {
            $symptoms[] = 'Kenderaan terasa luar biasa / uncharacteristic driving feel & unusual vehicle behaviour';
            $serviceType = $serviceType ?: 'Engine Diagnostics Scan';
        }

        // Raw capture if user explained a custom issue
        if (empty($symptoms) && strlen(trim($text)) >= 8 && !preg_match('/^(ya|yes|proceed|confirm|ok|okay|batal|cancel)/i', $lower)) {
            // If message is descriptive, treat as issue description
            if (preg_match('/(bunyi|rosak|masalah|pecah|bocor|leaking|longgar|loose|tukar|check|periksa|pelik|gegar)/i', $lower)) {
                $symptoms[] = trim($text);
            }
        }

        return [
            'symptoms'     => !empty($symptoms) ? implode('; ', $symptoms) : null,
            'service_type' => $serviceType,
        ];
    }

    private function hasDateOrTimeCue(string $text): bool {
        $lower = strtolower($text);
        return (bool) (
            preg_match('/(esok|lusa|isnin|selasa|rabu|khamis|jumaat|sabtu|tomorrow|monday|tuesday|wednesday|thursday|friday|saturday)/i', $lower)
            || preg_match('/\b\d{1,2}[-\/]\d{1,2}(?:[-\/]\d{2,4})?\b/', $lower)
            || preg_match('/\b\d{4}[-\/]\d{1,2}[-\/]\d{1,2}\b/', $lower)
            || preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm|pagi|petang|tengahari|malam)/i', $lower)
            || preg_match('/jam\s*(\d{1,2})/i', $lower)
            || preg_match('/pukul\s*(\d{1,2})/i', $lower)
        );
    }

    // ----------------------------------------------------------------
    // NLU and General Responses (Dual Language Support)
    // ----------------------------------------------------------------

    private function buildResponse(string $raw, $user, string $lang, ?array $userContext = null): string {
        $norm   = $this->normalise($raw);
        $tokens = $this->tokens($norm);

        if (empty($tokens)) {
            return $this->fallback($user, $lang);
        }

        // 1. Exact part number / barcode
        if ($part = $this->partByNumber($raw)) {
            $this->remember('part', $part->id);
            return $this->partReply($part, $lang);
        }

        $intents  = $this->scoreIntents($tokens);
        $top      = $intents ? array_key_first($intents) : null;
        $topScore = $top ? $intents[$top] : 0;

        // 2. Short social intents
        if (in_array($top, ['greeting', 'thanks', 'bye'], true) && count($tokens) <= 5 && $topScore >= 2) {
            return $this->socialReply($top, $user, $lang, $userContext);
        }

        // 3. Workshop intents
        if (in_array($top, ['hours', 'location', 'appointments', 'vehicles', 'invoice'], true) && $topScore >= 2) {
            return match ($top) {
                'hours'        => $this->hoursReply($lang),
                'location'     => $this->locationReply($lang),
                'appointments' => $this->appointmentsReply($user, $lang),
                'vehicles'     => $this->vehiclesReply($user, $lang),
                'invoice'      => $this->invoiceReply($user, $lang),
            };
        }

        // 4. Catalogue entity (job type or spare part)
        $entity = $this->findEntity($tokens, $intents);
        if ($entity) {
            $this->remember($entity['type'], $entity['model']->id);
            return $this->entityReply($entity, $lang);
        }

        // 5. Generic catalogue questions
        if ($top === 'services' && $topScore >= 2) {
            return $this->servicesReply(false, $lang);
        }
        if (in_array($top, ['price', 'stock'], true)) {
            if ($ctx = $this->recall()) {
                return $ctx['type'] === 'part'
                    ? $this->partReply($ctx['model'], $lang)
                    : $this->jobTypeReply($ctx['model'], $lang);
            }
            return $this->hasWord($tokens, ['service','labour','servis']) || $this->mentionsService($norm)
                ? $this->servicesReply(true, $lang)
                : $this->partsListReply($lang);
        }
        if (in_array($top, ['greeting', 'thanks', 'bye'], true)) {
            return $this->socialReply($top, $user, $lang);
        }

        Log::info('chatbot.unmatched', ['q' => $raw, 'user' => $user->id ?? null]);
        return $this->fallback($user, $lang);
    }

    private function normalise(string $s): string {
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9\s\-]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        foreach (self::SYNONYMS as $from => $to) {
            $s = preg_replace('/\b' . preg_quote($from, '/') . '\b/', $to, $s);
        }
        return $s;
    }

    private function tokens(string $norm): array {
        return array_values(array_filter(preg_split('/[^a-z0-9]+/', $norm)));
    }

    private function same(string $a, string $b): bool {
        if ($a === $b) return true;
        $sa = rtrim($a, 's');
        $sb = rtrim($b, 's');
        if ($sa === $sb) return true;
        $len = min(strlen($a), strlen($b));
        if ($len < 5) return false;
        return levenshtein($sa, $sb) <= ($len >= 8 ? 2 : 1);
    }

    private function hasWord(array $tokens, array $words): bool {
        foreach ($tokens as $t) {
            foreach ($words as $w) {
                if ($this->same($t, $w)) return true;
            }
        }
        return false;
    }

    private function mentionsService(string $norm): bool {
        return (bool) preg_match('/\b(service|labour|labor)\b/', $norm);
    }

    private function scoreIntents(array $tokens): array {
        $scores = [];
        foreach (self::INTENTS as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $kw => $weight) {
                foreach ($tokens as $t) {
                    if ($this->same($t, $kw)) { $score += $weight; break; }
                }
            }
            if ($score > 0) $scores[$intent] = $score;
        }
        arsort($scores);
        return $scores;
    }

    private function significant(array $tokens): array {
        return array_values(array_filter($tokens, fn($t) => strlen($t) >= 2 && !in_array($t, self::GENERIC, true)));
    }

    private function overlap(array $query, string $entityText): float {
        $entity = $this->significant($this->tokens($this->normalise($entityText)));
        if (empty($entity) || empty($query)) return 0.0;

        $matched = 0;
        foreach ($query as $q) {
            foreach ($entity as $e) {
                if ($this->same($q, $e)) { $matched++; break; }
            }
        }
        return $matched === 0 ? 0.0 : $matched / sqrt(count($entity) * count($query));
    }

    private function findEntity(array $tokens, array $intents): ?array {
        $query = $this->significant($tokens);
        if (empty($query)) return null;

        $partCue    = isset($intents['stock']) || $this->hasWord($tokens, ['part', 'spare']);
        $serviceCue = $this->hasWord($tokens, ['service', 'labour', 'labor']);

        $candidates = [];

        foreach (JobType::all() as $jt) {
            $s = $this->overlap($query, $jt->name . ' ' . $jt->category);
            if ($s > 0) $candidates[] = ['type' => 'job', 'model' => $jt, 'score' => $s * ($serviceCue ? 1.15 : 1.0)];
        }
        foreach ($candidatesPart = SparePart::all() as $p) {
            $s = $this->overlap($query, $p->name . ' ' . $p->brand . ' ' . $p->category);
            if ($s > 0) $candidates[] = ['type' => 'part', 'model' => $p, 'score' => $s * ($partCue ? 1.15 : 1.0)];
        }

        if (empty($candidates)) return null;

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        $best = $candidates[0];
        if ($best['score'] < 0.4) return null;

        $best['related'] = array_values(array_filter(
            array_slice($candidates, 1, 3),
            fn($c) => $c['score'] >= $best['score'] * 0.9 && $c['score'] >= 0.4
        ));

        return $best;
    }

    private function partByNumber(string $raw): ?SparePart {
        if (!preg_match_all('/[A-Za-z0-9]+(?:-[A-Za-z0-9]+)+/', $raw, $m)) return null;
        foreach ($m[0] as $candidate) {
            $part = SparePart::whereRaw('LOWER(part_number) = ?', [strtolower($candidate)])->first();
            if ($part) return $part;
        }
        return null;
    }

    private function remember(string $type, int $id): void {
        session(['chatbot_last' => ['type' => $type === 'job' ? 'job' : 'part', 'id' => $id]]);
    }

    private function recall(): ?array {
        $last = session('chatbot_last');
        if (!$last) return null;
        $model = $last['type'] === 'part' ? SparePart::find($last['id']) : JobType::find($last['id']);
        return $model ? ['type' => $last['type'], 'model' => $model] : null;
    }

    private function entityReply(array $entity, string $lang): string {
        $reply = $entity['type'] === 'part'
            ? $this->partReply($entity['model'], $lang)
            : $this->jobTypeReply($entity['model'], $lang);

        if (!empty($entity['related'])) {
            $names = array_map(fn($c) => $c['model']->name, $entity['related']);
            $reply .= "\n\n" . ($lang === 'ms' ? "Adakah anda maksudkan: " : "Did you mean: ") . implode(', ', $names) . '?';
        }
        return $reply;
    }

    private function jobTypeReply(JobType $jt, string $lang): string {
        $price = $jt->base_price > 0 ? 'RM ' . number_format($jt->base_price, 2) : ($lang === 'ms' ? 'Hubungi untuk sebut harga' : 'Contact us for a quote');

        if ($lang === 'ms') {
            $reply = "🔧 **{$jt->name}**\n"
                   . "• Kategori: {$jt->category}\n"
                   . "• Anggaran Upah: **{$price}**\n"
                   . "• Anggaran Masa: ~{$jt->estimated_time}\n";
            if ($jt->isIntervalTracked()) {
                $reply .= "• Selang Servis Dicadangkan: {$jt->interval_label}\n";
            }
            return $reply . "\n_Caj upah berasingan daripada alat ganti. Untuk tempah, anda boleh beritahu saya tarikh & model kereta anda!_";
        }

        $reply = "🔧 **{$jt->name}**\n"
               . "• Category: {$jt->category}\n"
               . "• Labour charge: **{$price}**\n"
               . "• Estimated duration: ~{$jt->estimated_time}\n";
        if ($jt->isIntervalTracked()) {
            $reply .= "• Recommended interval: {$jt->interval_label}\n";
        }
        return $reply . "\n_Labour is billed separately from parts. To book this, simply reply with your car model & preferred date!_";
    }

    private function partReply(SparePart $part, string $lang): string {
        if ($part->stock <= 0) {
            $note = $lang === 'ms' ? '❌ Kehabisan stok' : '❌ Out of stock';
        } elseif ($part->stock <= $part->min_stock) {
            $note = $lang === 'ms' ? "⚠ Stok rendah — baki {$part->stock} unit" : "⚠ Low stock — only {$part->stock} unit(s) left";
        } else {
            $note = $lang === 'ms' ? "✅ Ada stok — {$part->stock} unit tersedia" : "✅ In stock — {$part->stock} units available";
        }

        if ($lang === 'ms') {
            return "🔩 **{$part->name}**\n"
                 . ($part->brand ? "• Jenama: {$part->brand}\n" : '')
                 . "• No Alat Ganti: `{$part->part_number}`\n"
                 . "• Kategori: {$part->category}\n"
                 . "• Harga Seunit: **RM " . number_format($part->unit_price, 2) . "**\n"
                 . "• Status Stok: {$note}\n\n"
                 . "_Pengebilan akhir bergantung kepada alat ganti sebenar yang digunakan dalam job card._";
        }

        return "🔩 **{$part->name}**\n"
             . ($part->brand ? "• Brand: {$part->brand}\n" : '')
             . "• Part No: `{$part->part_number}`\n"
             . "• Category: {$part->category}\n"
             . "• Unit Price: **RM " . number_format($part->unit_price, 2) . "**\n"
             . "• Availability: {$note}\n\n"
             . "_Final billing is based on the actual parts fitted to your vehicle._";
    }

    private function servicesReply(bool $withPrices = false, string $lang = 'en'): string {
        $jobTypes = JobType::orderBy('category')->orderBy('name')->get();
        if ($jobTypes->isEmpty()) {
            return $lang === 'ms'
                ? 'Kami menyediakan servis penyelenggaraan dan pembaikan kenderaan menyeluruh. Sila hubungi kaunter bengkel untuk maklumat lanjut.'
                : 'We offer a full range of vehicle maintenance and repair services. Please contact us for details.';
        }

        $header = $lang === 'ms' ? "🔧 **Senarai Perkhidmatan Teraju Setia Enterprise**:\n\n" : "🔧 **Our Workshop Services**:\n\n";
        $reply = $header;

        foreach ($jobTypes->groupBy('category') as $cat => $types) {
            $reply .= "**{$cat}**\n";
            foreach ($types as $t) {
                $reply .= "• {$t->name} (~{$t->estimated_time})";
                if ($withPrices && $t->base_price > 0) {
                    $reply .= ' — RM ' . number_format($t->base_price, 2);
                }
                $reply .= "\n";
            }
            $reply .= "\n";
        }

        if ($lang === 'ms') {
            return $reply . ($withPrices ? '_Kadar upah asas; alat ganti dikira berasingan._' : "Tanya saya mengenai perkhidmatan khusus untuk mengetahui kadar upah!");
        }

        return $reply . ($withPrices ? '_Base labour charges; spare parts billed extra._' : "Ask me about a specific service for its rate!");
    }

    private function partsListReply(string $lang): string {
        $grouped = SparePart::orderBy('category')->orderBy('name')->get()->groupBy('category');
        $reply = $lang === 'ms' ? "🔩 **Senarai Alat Ganti & Komponen Bengkel**:\n\n" : "🔩 **Current Spare Parts & Prices**:\n\n";

        foreach ($grouped as $cat => $items) {
            $reply .= "**{$cat}**\n";
            foreach ($items as $p) {
                $avail = $p->stock <= 0 ? ($lang === 'ms' ? '❌ Habis' : '❌ Out')
                       : ($p->stock <= $p->min_stock ? ($lang === 'ms' ? "⚠ Baki {$p->stock}" : "⚠ Low ({$p->stock})") : ($lang === 'ms' ? "✅ {$p->stock} unit" : "✅ {$p->stock} in stock"));
                $reply .= "• {$p->name}" . ($p->brand ? " ({$p->brand})" : '')
                        . ' — RM ' . number_format($p->unit_price, 2) . " · {$avail}\n";
            }
            $reply .= "\n";
        }
        return $reply . ($lang === 'ms' ? 'Tanya saya nama mana-mana alat ganti untuk maklumat lanjut.' : 'Ask me the name of any part for full details.');
    }

    private function socialReply(string $intent, $user, string $lang, ?array $userContext = null): string {
        $ctx = $userContext ?: $this->getUserContext($user);

        if ($lang === 'ms') {
            if ($intent === 'greeting') {
                return match ($ctx['role']) {
                    'admin' => "Selamat datang Tuan **{$ctx['name']}** (Pentadbir Bengkel Teraju Setia)! 👑\n\nSaya sedia membantu anda dengan laporan harian temujanji, semakan invois pelanggan, atau amaran stok alat ganti.",
                    'coordinator' => "Hai Cik/Puan **{$ctx['name']}** (Penyelaras Servis & Kaunter)! 📋\n\nSaya boleh bantu semak jadual temujanji hari ini, agihan kenderaan kepada mekanik, atau carian invois.",
                    'staff' => "Hai Encik **{$ctx['name']}** (Mekanik / " . ($user->staff_role ?? 'Teknisi') . ")! 🛠️\n\nSaya sedia bantu anda semak **Job Card aktif (had job left?)**, semak baki stok alat ganti di stor, atau spesifikasi teknikal.",
                    'corporate' => "Selamat datang **{$ctx['name']}** (Pengurus Armada " . ($ctx['company'] ?: 'Korporat') . ")! 🚛\n\nSaya pembantu akaun fleet anda. Anda boleh semak kenderaan armada, jadual servis berjadual lori/van, atau invois tertunggak syarikat.",
                    'individual' => "Hai Encik/Puan **{$ctx['name']}**! 🚗\n\nSelamat kembali ke Teraju Setia Enterprise. Anda boleh semak rekod servis kenderaan, semak anggaran kos, atau tempah temujanji baharu.",
                    default => "Salam sejahtera & Selamat datang ke Teraju Setia Enterprise! 🚗💨\n\nSaya pembantu pintar AI bengkel anda di Kluang, Johor. Anda boleh bertanya sebut harga servis, semak stok alat ganti, atau tempah slot temujanji terus di sini tanpa pendaftaran!",
                };
            }

            return match ($intent) {
                'thanks'   => "Sama-sama! 😊 Ada apa-apa lagi soalan mengenai operasi atau kenderaan yang boleh saya bantu?",
                'bye'      => "Terima kasih! Pandu dengan cermat dan jumpa lagi di Teraju Setia Enterprise. 👋",
                default    => "Hai! Bagaimana saya boleh bantu anda hari ini?",
            };
        }

        if ($intent === 'greeting') {
            return match ($ctx['role']) {
                'admin' => "Welcome **{$ctx['name']}** (Workshop Owner & Administrator)! 👑\n\nI am your operational copilot. Ask me for today's appointment reports, customer invoice lookups, or low stock PO replenishment alerts.",
                'coordinator' => "Hello **{$ctx['name']}** (Service Reception Coordinator)! 📋\n\nI can help you review today's appointment arrivals, mechanic bay allocations, or search customer invoices.",
                'staff' => "Hello **{$ctx['name']}** (Mechanic / " . ($user->staff_role ?? 'Technician') . ")! 🛠️\n\nI can assist you with **assigned Job Cards ('had job left?')**, checking store spare parts stock, or torque & fluid specifications.",
                'corporate' => "Welcome **{$ctx['name']}** (Fleet Manager - " . ($ctx['company'] ?: 'Corporate Fleet') . ")! 🚛\n\nI am your dedicated fleet accounts assistant. Ask me about your company's commercial vehicles, scheduled maintenance, or outstanding invoices.",
                'individual' => "Hello **{$ctx['name']}**! 🚗\n\nWelcome back to Teraju Setia Enterprise. You can check your vehicle service history, repair quotes, or book your next service slot.",
                default => "Welcome to Teraju Setia Enterprise! 🚗💨\n\nI am your workshop AI assistant in Kluang, Johor. Ask me about repair quotes, spare parts pricing, or book an appointment directly right here!",
            };
        }

        return match ($intent) {
            'thanks'   => "You're very welcome! 😊 Is there anything else I can assist you with today?",
            'bye'      => 'Goodbye! Drive safely and see you at Teraju Setia. Feel free to chat anytime. 👋',
            default    => 'Hello! How may I assist you today?',
        };
    }

    private function hoursReply(string $lang): string {
        if ($lang === 'ms') {
            return "🕐 **Waktu Operasi Teraju Setia Enterprise (Kluang, Johor)**:\n\n"
                 . "• **Ahad – Khamis**: 8:30 AM – 5:30 PM\n"
                 . "• **Jumaat**: 8:30 AM – 12:30 PM & 2:30 PM – 5:30 PM *(Rehat Solat Jumaat: 12:30 PM – 2:30 PM)*\n"
                 . "• **Sabtu & Cuti Umum**: Tutup\n\n"
                 . "Sebarang pertanyaan atau bantuan temujanji, sila hubungi kami di talian **07-787 8728**.";
        }

        return "🕐 **Workshop Operating Hours (Teraju Setia Enterprise, Kluang)**:\n\n"
             . "• **Sunday – Thursday**: 8:30 AM – 5:30 PM\n"
             . "• **Friday**: 8:30 AM – 12:30 PM & 2:30 PM – 5:30 PM *(Friday prayer break: 12:30 PM – 2:30 PM)*\n"
             . "• **Saturday & Public Holidays**: Closed\n\n"
             . "For inquiries or appointment assistance, please call us at **07-787 8728**.";
    }

    private function locationReply(string $lang): string {
        if ($lang === 'ms') {
            return "📍 **Lokasi Teraju Setia Enterprise**:\n\n"
                 . "No. 1, Jalan Malinja 2,\n"
                 . "Taman Perindustrian Matahari,\n"
                 . "86000 Kluang, Johor Darul Ta'zim.\n"
                 . "*(No. Telefon: 07-787 8728)*\n\n"
                 . "🚗 _Panduan_: Terletak di Taman Perindustrian Matahari, Kluang dengan akses mudah dari laluan utama Kluang.";
        }

        return "📍 **Workshop Location (Teraju Setia Enterprise)**:\n\n"
             . "No. 1, Jalan Malinja 2,\n"
             . "Taman Perindustrian Matahari,\n"
             . "86000 Kluang, Johor Darul Ta'zim, Malaysia.\n"
             . "*(Phone: 07-787 8728)*\n\n"
             . "🚗 _Directions_: Located within Taman Perindustrian Matahari, Kluang with straightforward access from major Kluang routes.";
    }

    private function appointmentsReply($user, string $lang): string {
        if (!$user) {
            return $lang === 'ms'
                ? "Untuk menyemak rekod temujanji berdaftar, sila log masuk ke akaun anda terlebih dahulu.\n\nSebagai tetamu awam, anda boleh membuat tempahan temujanji baharu secara terus di sini!"
                : "To view existing registered appointments, please log in first.\n\nAs a guest you can book a new walk-in service appointment directly right here!";
        }

        $appointments = Appointment::whereIn('user_id', $this->scopeUserIds($user))
            ->with('vehicle')
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest()->take(3)->get();

        if ($appointments->isEmpty()) {
            return $lang === 'ms'
                ? "Anda tiada temujanji yang sedang aktif atau menunggu buat masa ini.\n\nJika anda ingin membuat servis kenderaan, beritahu saya bila anda ingin datang!"
                : "You currently have no upcoming appointments.\n\nWould you like to schedule one? Just let me know your preferred date and vehicle!";
        }

        $header = $lang === 'ms' ? "📅 **Temujanji Aktif Anda**:\n\n" : "📅 **Your Upcoming Appointments**:\n\n";
        $reply = $header;

        foreach ($appointments as $apt) {
            $reply .= "• **{$apt->service_type}**\n"
                    . '  Kenderaan: ' . ($apt->vehicle->plate_number ?? '—') . "\n"
                    . '  Tarikh: ' . $apt->date->format('d M Y') . " pada {$apt->time}\n"
                    . '  Status: ' . ucfirst($apt->status) . "\n\n";
        }
        return $reply;
    }

    private function vehiclesReply($user, string $lang): string {
        if (!$user) return $lang === 'ms' ? 'Untuk melihat maklumat kenderaan anda, sila log masuk terlebih dahulu.' : 'To view your vehicle details, please log in first.';

        $vehicles = Vehicle::whereIn('user_id', $this->scopeUserIds($user))->orderBy('plate_number')->get();
        if ($vehicles->isEmpty()) {
            return $lang === 'ms'
                ? 'Anda belum mendaftarkan sebarang kenderaan. Anda boleh menambah kenderaan di bahagian *My Vehicles*.'
                : 'You have no registered vehicles yet. You can add one through the Vehicles section.';
        }

        $reply = $lang === 'ms' ? "🚗 **Kenderaan Berdaftar Anda**:\n\n" : "🚗 **Your Registered Vehicles**:\n\n";
        foreach ($vehicles as $v) {
            $reply .= "• **{$v->plate_number}** — {$v->brand} {$v->model} ({$v->year})\n"
                    . '  Odometer: ' . number_format($v->mileage) . " km\n\n";
        }
        return $reply;
    }

    private function invoiceReply($user, string $lang): string {
        if (!$user) return $lang === 'ms' ? 'Untuk menyemak invois atau baki, sila log masuk terlebih dahulu.' : 'To check invoices or balances, please log in first.';

        $ids      = $this->scopeUserIds($user);
        $invoices = Invoice::whereHas('jobCard.appointment', fn($q) => $q->whereIn('user_id', $ids))
            ->whereIn('status', ['unpaid', 'partial'])
            ->latest()->take(5)->get();

        if ($invoices->isEmpty()) {
            return $lang === 'ms' ? '✅ Anda tiada invois tertunggak.' : '✅ You have no outstanding invoices.';
        }

        $reply = $lang === 'ms' ? "🧾 **Invois Tertunggak**:\n\n" : "🧾 **Outstanding Invoices**:\n\n";
        foreach ($invoices as $inv) {
            $reply .= "• `{$inv->invoice_number}` — Baki: **RM " . number_format($inv->balance, 2) . "**"
                    . ' (' . ucfirst($inv->status) . ")\n";
        }
        $reply .= "\nJumlah Perlu Dibayar: **RM " . number_format($invoices->sum(fn($i) => $i->balance), 2) . "**";
        return $reply;
    }

    private function fallback($user, string $lang): string {
        if ($lang === 'ms') {
            return "Maaf, saya tidak berapa faham soalan itu. Berikut adalah antara perkara yang boleh saya bantu:\n\n"
                 . "• **Kadar Harga Servis** — contoh: *'Berapa harga servis minyak hitam?'*\n"
                 . "• **Semakan Alat Ganti & Bateri** — contoh: *'Berapa harga bateri dan pad brek?'*\n"
                 . "• **Diagnosis Masalah Kenderaan** — contoh: *'Kenapa brek berdecit bila tekan pedal?'*\n"
                 . "• **Tempahan Temujanji Servis** — contoh: *'Saya nak book appointment servis'* (kami sediakan kalendar interaktif!)\n"
                 . "• **Waktu Operasi & Lokasi Bengkel** di Kluang, Johor\n\n"
                 . "Untuk bantuan teknikal segera, sila hubungi meja perkhidmatan kami di **07-787 8728**.";
        }

        return "I'm not sure about that. Here is what I can assist you with:\n\n"
             . "• **Service Pricing & Labour** — try *'How much is an engine oil change?'*\n"
             . "• **Spare Parts & Stock** — try *'Price of brake pad'*\n"
             . "• **Vehicle Symptom Diagnostics** — try *'Why is my brake squealing?'*\n"
             . "• **Direct Appointment Booking** — try *'I want to book an appointment'*\n"
             . "• **Workshop Operating Hours & Location** in Kluang, Johor\n\n"
             . "For urgent inquiries, please call our service desk at **07-787 8728**.";
    }

    private function scopeUserIds($user): array {
        if ($user->role === 'corporate' && $user->company_id) {
            return User::where('company_id', $user->company_id)->pluck('id')->all();
        }
        return [$user->id];
    }

    private function isConfirmProceed(string $msg): bool {
        if (!session()->has('pending_chatbot_booking')) {
            return false;
        }
        $lower = strtolower(trim($msg));
        if (preg_match('/^(proceed|confirm|sahkan|setuju|teruskan|buatkan|deal|ya|yes|ok|okay|baik|baiklah|betul|correct|tq|terima kasih|thank you|thanks)$/i', $lower)) {
            return true;
        }
        if (preg_match('/\b(sahkan tempahan|confirm booking|proceed booking|sahkan slot|confirm slot|ya sahkan|yes confirm|maklumat betul|ya betul|semua betul|ok terima kasih|ok tq|ok thank you|terima kasih banyak)\b/i', $lower)) {
            return true;
        }
        return false;
    }

    private function isCancelIntent(string $msg): bool {
        $lower = strtolower(trim($msg));
        if (preg_match('/^(batal|cancel|tak jadi|jangan|stop|batal tempahan|cancel booking)$/i', $lower)) {
            return true;
        }
        if (preg_match('/\b(batal tempahan|cancel booking|batalkan|tak jadi lah|tak jadi book)\b/i', $lower)) {
            return true;
        }
        return false;
    }

    private function isBookingIntent(string $msg, ?array $userContext = null): bool {
        $lower = strtolower(trim($msg));

        // 0. Internal workshop personnel (Admin, Coordinator, Staff) do NOT use customer booking/intake
        if (!empty($userContext['is_internal_staff'])) {
            return false;
        }

        // 0b. Vehicle plate or appointment ID inquiry is NOT a booking intent
        if ($this->isPlateOrAppointmentQuery($msg, $userContext)) {
            return false;
        }

        // 1. Schedule / Calendar viewing is NOT a booking intent
        if ($this->isScheduleViewQuery($msg)) {
            return false;
        }

        // 2. Daily workshop report is NOT a booking intent
        if ($this->isDailyReportQuery($msg)) {
            return false;
        }

        // 3. Inventory / PO queries are NOT a booking intent
        if ($this->isInventoryQuery($msg)) {
            return false;
        }

        // 3b. Invoice payment queries are NOT a booking intent
        if ($this->isInvoicePaymentUpdateQuery($msg)) {
            return false;
        }

        // 4. Checking own existing appointments is NOT a new booking intent
        if (preg_match('/(check|view|see|show|list|status|tengok|lihat|semak|rekod)\s*(my|upcoming|existing|aktif|rekod)?\s*(appointment|booking|temujanji)/i', $lower)
            || preg_match('/^(my appointments|upcoming appointments|temujanji aktif|rekod temujanji)/i', $lower)
            || preg_match('/(temujanji\s*aktif|rekod\s*temujanji)/i', $lower)) {
            return false;
        }

        // 5. Slot selection from interactive calendar widget
        if (preg_match('/(saya\s*pilih\s*tarikh|i\s*select\s*date)/i', $lower)) {
            return true;
        }

        // 5b. Agreement / Affirmative response to previous suggestion or diagnostic booking
        if (preg_match('/^(sure\s*lets\s*do\s*that|lets\s*do\s*that|sure\s*lets\s*do\s*it|sure|lets\s*do\s*it|yes\s*please|jom\s*buat|boleh\s*buat|jom|setuju|proceed)$/i', $lower)) {
            return true;
        }

        // 5c. Customer expresses vehicle abnormal feeling or breakdown symptoms
        if (preg_match('/(kereta|keta|kenderaan|car|enjin|engine|brek|brake).*(rasa\s*(pelik|senguk|sengguk)|pelik|senguk|sengguk|tersengguk|jerking|weird|strange|unusual|rosak|masalah|problem|berbunyi|gegar|semput|tak\s*boleh\s*start|tak\s*sejuk)/i', $lower)
            || preg_match('/\b(senguk|sengguk|tersengguk|jerking|semput)\b/i', $lower)
            || preg_match('/^(kereta\s*saya\s*rasa\s*pelik|kereta\s*rasa\s*pelik|kereta\s*rosak|my\s*car\s*feels\s*weird|car\s*feels\s*strange)$/i', $lower)) {
            return true;
        }

        // 6. Explicit booking action in Malay / English
        if (preg_match('/\b(book|booking|tempah|tempahan|temujanji|appointment|walk-?in)\b/i', $lower)) {
            return true;
        }

        if (preg_match('/(buat|nak|mau|hendak|mohon|set|atur|tolong\s*(buat|set|tempah|book))\s*(kan)?\s*.*(appointment|booking|servis|service|temujanji|slot)/i', $lower)) {
            return true;
        }

        if (preg_match('/(nak|mau|hendak|want to|would like to|can i|boleh ke|boleh tak|boleh lah)\s*(hantar|bawa|bawak|servis|baiki|periksa|check|repair|send|bring)\s*(kereta|keta|lori|kenderaan|car|vehicle)?/i', $lower)) {
            return true;
        }

        if (preg_match('/(nak|mau|hendak|want to)\s*(servis|service|repair|baiki|tukar\s*minyak|oil change)/i', $lower)) {
            return true;
        }

        if (preg_match('/(schedule|book|make)\s*(an|a)?\s*(appointment|service|slot)/i', $lower)
            || preg_match('/(want|would like|need)\s*to\s*(book|schedule|make)\s*(an|a)?\s*(appointment|service|slot)/i', $lower)) {
            return true;
        }

        return false;
    }

    // ----------------------------------------------------------------
    // User Role Identification & Context
    // ----------------------------------------------------------------

    private function getUserContext($user): array {
        if (!$user) {
            return [
                'role'              => 'guest',
                'name'              => 'Pelawat Awam / Guest Customer',
                'title'             => 'Pelawat Awam (Public Guest)',
                'company'           => null,
                'is_internal_staff' => false,
                'is_mechanic'       => false,
                'is_fleet_manager'  => false,
                'description'       => 'Public visitor seeking service rates, spare parts, and walk-in appointment bookings.',
            ];
        }

        $role = $user->role;
        $companyName = $user->company ? $user->company->name : null;

        return match ($role) {
            'admin' => [
                'role'              => 'admin',
                'name'              => $user->name,
                'title'             => 'Pemilik & Pentadbir Bengkel (Workshop Owner & Administrator)',
                'company'           => 'Teraju Setia Enterprise',
                'is_internal_staff' => true,
                'is_mechanic'       => false,
                'is_fleet_manager'  => false,
                'description'       => 'Full workshop executive authority: daily appointment reports, mechanic workload, inventory restock POs, invoices, fleet accounts.',
            ],
            'coordinator' => [
                'role'              => 'coordinator',
                'name'              => $user->name,
                'title'             => 'Penyelaras Servis & Kaunter (Service Reception Coordinator)',
                'company'           => 'Teraju Setia Enterprise',
                'is_internal_staff' => true,
                'is_mechanic'       => false,
                'is_fleet_manager'  => false,
                'description'       => 'Service desk operations: appointment schedule, assigning vehicles to mechanics, customer intake, and queue tracking.',
            ],
            'staff' => [
                'role'              => 'staff',
                'name'              => $user->name,
                'title'             => 'Mekanik / Juruteknik (' . ($user->staff_role ?? 'Teknisi Bengkel') . ')',
                'company'           => 'Teraju Setia Enterprise',
                'is_internal_staff' => true,
                'is_mechanic'       => true,
                'is_fleet_manager'  => false,
                'description'       => 'Workshop mechanic: executing assigned job cards, diagnosing faults, requesting spare parts from store, and repair stages.',
            ],
            'corporate' => [
                'role'              => 'corporate',
                'name'              => $user->name,
                'title'             => 'Pengurus Armada Komersial (Fleet Operations Manager)',
                'company'           => $companyName ?: 'Syarikat Armada Korporat',
                'is_internal_staff' => false,
                'is_mechanic'       => false,
                'is_fleet_manager'  => true,
                'description'       => 'Corporate fleet client managing commercial lorries, vans, and pickups; tracking fleet servicing, maintenance intervals, and monthly invoices.',
            ],
            'individual' => [
                'role'              => 'individual',
                'name'              => $user->name,
                'title'             => 'Pelanggan Individu (Private Vehicle Owner)',
                'company'           => null,
                'is_internal_staff' => false,
                'is_mechanic'       => false,
                'is_fleet_manager'  => false,
                'description'       => 'Private vehicle owner: personal car service history, repair estimates, and routine booking.',
            ],
            default => [
                'role'              => 'guest',
                'name'              => $user->name,
                'title'             => 'Pengguna Berdaftar',
                'company'           => null,
                'is_internal_staff' => false,
                'is_mechanic'       => false,
                'is_fleet_manager'  => false,
                'description'       => 'Registered customer.',
            ],
        };
    }

    // ----------------------------------------------------------------
    // Staff / Mechanic Work Queries ("had job left?", "kerja saya")
    // ----------------------------------------------------------------

    private function isStaffJobQuery(string $msg): bool {
        $lower = strtolower(trim($msg));
        return (bool) (
            preg_match('/\b(had\s*job\s*left|any\s*jobs?\s*left|jobs?\s*left|what\s*are\s*my\s*jobs|my\s*jobs|my\s*job\s*cards?|assigned\s*jobs?|pending\s*jobs?)\b/i', $lower)
            || preg_match('/(ada|baki)?\s*(kerja|tugasan|job\s*card|job)\s*(saya)?\s*(tinggal|left|belum\s*siap|aktif|hari\s*ini)?/i', $lower)
            || preg_match('/(apa|senarai|list)\s*(kerja|tugasan|job\s*card)\s*(saya)?/i', $lower)
            || preg_match('/(kerja|tugasan)\s*(bengkel|mekanik)?\s*(saya|hari\s*ini)/i', $lower)
            || preg_match('/\b(job\s*card\s*aktif|status\s*kerja\s*saya)\b/i', $lower)
        );
    }

    private function handleStaffJobQuery(string $msg, $user, string $lang): string {
        if (!$user) {
            return $lang === 'ms'
                ? "⚠️ Sila log masuk ke akaun mekanik atau kakitangan bengkel anda untuk menyemak senarai Job Card yang ditugaskan."
                : "⚠️ Please log in with your workshop staff or mechanic account to view your assigned Job Cards.";
        }

        // Mechanic / Technician query
        if ($user->role === 'staff') {
            $activeJobs = JobCard::where('staff_id', $user->id)
                ->where('current_stage', '!=', 'completed')
                ->with(['vehicle', 'jobType', 'appointment'])
                ->orderByRaw("FIELD(current_stage, 'repairing', 'waiting_parts', 'diagnosing', 'received', 'quality_check')")
                ->get();

            if ($activeJobs->isEmpty()) {
                return $lang === 'ms'
                    ? "✅ **Tiada Job Card Tertunggak! (No Jobs Left)**\n\n"
                    . "Syabas Encik **{$user->name}**, anda telah menyiapkan semua tugasan pembaikan yang ditugaskan kepada anda setakat ini.\n\n"
                    . "Sila semak dengan penyelaras servis jika ada kenderaan baru yang baru didaftarkan di kaunter, atau semak baki stok alat ganti di stor."
                    : "✅ **No Pending Job Cards! (No Jobs Left)**\n\n"
                    . "Great job **{$user->name}**, all repair tasks assigned to you are currently completed!\n\n"
                    . "Please check with service reception for any newly arrived vehicles or check spare parts store stock.";
            }

            $completedToday = JobCard::where('staff_id', $user->id)
                ->where('current_stage', 'completed')
                ->whereDate('updated_at', Carbon::today())
                ->count();

            $stageLabelsMs = [
                'received'      => '📥 Baru Diterima',
                'diagnosing'    => '🔍 Sedang Didiagnosis',
                'waiting_parts' => '⏳ Menunggu Alat Ganti',
                'repairing'     => '🔧 Sedang Dibaiki',
                'quality_check' => '✅ Ujian Kualiti (QC)',
            ];

            $reply = $lang === 'ms'
                ? "🛠️ **Tugasan Bengkel Anda (Tinggal " . $activeJobs->count() . " Job Card Belum Siap)**:\n"
                . "• Nama Mekanik: **{$user->name}** (" . ($user->staff_role ?? 'Teknisi Bengkel') . ")\n"
                . "• Siap Hari Ini: **{$completedToday} kenderaan**\n\n"
                : "🛠️ **Your Active Repair Tasks (" . $activeJobs->count() . " Jobs Remaining)**:\n"
                . "• Mechanic: **{$user->name}** (" . ($user->staff_role ?? 'Workshop Technician') . ")\n"
                . "• Completed Today: **{$completedToday} vehicles**\n\n";

            $i = 1;
            foreach ($activeJobs as $job) {
                $stageText = $lang === 'ms'
                    ? ($stageLabelsMs[$job->current_stage] ?? ucfirst($job->current_stage))
                    : strtoupper(str_replace('_', ' ', $job->current_stage));

                $plate = $job->vehicle->plate_number ?? '—';
                $vehDesc = trim(($job->vehicle->brand ?? '') . ' ' . ($job->vehicle->model ?? ''));
                $service = $job->jobType->name ?? ($job->appointment->service_type ?? 'Servis Am');

                $complaintRaw = $job->symptoms;
                if (is_array($complaintRaw)) {
                    $complaint = implode('; ', array_filter($complaintRaw));
                } elseif (!empty($complaintRaw)) {
                    $complaint = (string) $complaintRaw;
                } elseif ($job->appointment && $job->appointment->notes) {
                    $complaint = \Illuminate\Support\Str::limit($job->appointment->notes, 75);
                } else {
                    $complaint = 'Pemeriksaan rutin';
                }

                $estTime = $job->estimated_completion ? Carbon::parse($job->estimated_completion)->format('d M, g:i A') : 'Hari ini';

                $reply .= "{$i}. **Job Card #JC-" . str_pad($job->id, 5, '0', STR_PAD_LEFT) . "** &bull; [Buka Job Card #{$job->id}](/staff/job-cards/{$job->id})\n"
                        . "   • **Kenderaan**: `{$plate}` ({$vehDesc})\n"
                        . "   • **Peringkat**: `{$stageText}`\n"
                        . "   • **Servis**: {$service}\n"
                        . "   • **Aduan / Catatan**: _{$complaint}_\n"
                        . "   • **Sasaran Siap**: {$estTime}\n\n";
                $i++;
            }

            $reply .= $lang === 'ms'
                ? "💡 _Tip Mekanik_: Klik pautan Job Card di atas untuk mengemas kini peringkat pembaikan atau menempah alat ganti dari stor."
                : "💡 _Technician Tip_: Click on any Job Card link above to update its stage or request spare parts from store.";

            return $reply;
        }

        // Admin or Coordinator query
        if (in_array($user->role, ['admin', 'coordinator'])) {
            $activeJobs = JobCard::where('current_stage', '!=', 'completed')
                ->with(['vehicle', 'jobType', 'staff'])
                ->latest()
                ->take(6)
                ->get();

            $totalActive = JobCard::where('current_stage', '!=', 'completed')->count();

            $reply = $lang === 'ms'
                ? "🏭 **Status Lantai Bengkel (" . $totalActive . " Job Card Sedang Berjalan)**:\n\n"
                : "🏭 **Workshop Floor Pulse (" . $totalActive . " Active Job Cards)**:\n\n";

            foreach ($activeJobs as $job) {
                $plate = $job->vehicle->plate_number ?? '—';
                $staffName = $job->staff->name ?? 'Belum Diagih';
                $stage = strtoupper(str_replace('_', ' ', $job->current_stage));
                $reply .= "• **#JC-" . str_pad($job->id, 5, '0', STR_PAD_LEFT) . "** &bull; `{$plate}` &bull; Mekanik: **{$staffName}** [{$stage}]\n";
            }

            $reply .= "\n👉 Buka [Papan Pengurusan Job Card](/admin/job-cards) untuk agihan atau kemas kini.";
            return $reply;
        }

        return $lang === 'ms'
            ? "Fungsi semakan baki tugasan Job Card adalah khusus untuk kakitangan mekanik bengkel Teraju Setia. Untuk menyemak status temujanji anda, sila tanya 'Status temujanji saya'."
            : "Job Card task lookup is for Teraju Setia workshop mechanics. To check your vehicle service status, ask 'Status of my appointment'.";
    }

    // ----------------------------------------------------------------
    // Admin & Coordinator Daily Report / Appointment Status
    // ----------------------------------------------------------------

    private function isDailyReportQuery(string $msg): bool {
        $lower = strtolower(trim($msg));

        // Don't mistake calendar schedule view for daily internal report
        if ($this->isScheduleViewQuery($msg)) {
            return false;
        }

        return (bool) (
            preg_match('/(daily\s*report|laporan\s*harian|ringkasan\s*hari\s*ini|workshop\s*pulse)/i', $lower)
            || preg_match('/(status|report|laporan|summary)\s*(on|of)?\s*(today|hari\s*ini)?\s*(appointment|appointments|temujanji)/i', $lower)
            || preg_match('/(today\s*appointments?|today\s*schedule|appointment\s*hari\s*ini|temujanji\s*hari\s*ini|jadual\s*hari\s*ini)/i', $lower)
            || preg_match('/(status|keadaan)\s*(bengkel|temujanji)\s*hari\s*ini/i', $lower)
        );
    }

    private function handleDailyAppointmentsReport($user, string $lang): string {
        if (!$user || !in_array($user->role, ['admin', 'coordinator'])) {
            return $lang === 'ms'
                ? "⚠️ Laporan harian bengkel adalah terhad untuk akses **Pentadbir (Admin)** dan **Penyelaras Servis (Coordinator)** sahaja."
                : "⚠️ Daily workshop operational reports are restricted to **Admin** and **Coordinator** roles.";
        }

        $today = Carbon::today();
        $capacity = (int) Setting::get('daily_appointment_capacity', 25);
        $appts = Appointment::whereDate('date', $today)
            ->with(['vehicle', 'user'])
            ->orderBy('time')
            ->get();

        $confirmed = $appts->where('status', 'confirmed')->count();
        $pending   = $appts->where('status', 'pending')->count();
        $completed = $appts->where('status', 'completed')->count();
        $cancelled = $appts->where('status', 'cancelled')->count();
        $totalAppts = $appts->count();
        $utilization = $capacity > 0 ? round(($totalAppts / $capacity) * 100) : 0;

        $activeJobCards = JobCard::where('current_stage', '!=', 'completed')->count();
        $completedJobsToday = JobCard::where('current_stage', 'completed')
            ->whereDate('updated_at', $today)
            ->count();
        $lowStockParts = SparePart::whereColumn('stock', '<=', 'min_stock')->count();

        $dateLabel = $this->formatDateLabel($today, $lang);

        $linkPrefix = match($user ? $user->role : 'admin') {
            'coordinator' => '/coordinator/appointments/',
            default       => '/admin/appointments/',
        };

        if ($lang === 'ms') {
            $reply = "📊 **Laporan Operasi & Status Temujanji Hari Ini ({$dateLabel})**\n\n"
                   . "• **Kapasiti Digunakan**: **{$totalAppts} / {$capacity} slot** ({$utilization}%)\n"
                   . "• **Pecahan Status Temujanji**:\n"
                   . "  🟢 Disahkan (Confirmed): **{$confirmed}**\n"
                   . "  🟡 Menunggu (Pending): **{$pending}**\n"
                   . "  🔵 Selesai (Completed): **{$completed}**\n"
                   . ($cancelled > 0 ? "  🔴 Dibatalkan: **{$cancelled}**\n" : "")
                   . "\n";

            if ($appts->isEmpty()) {
                $reply .= "ℹ️ _Tiada temujanji dijadualkan untuk hari ini setakat ini._\n\n";
            } else {
                $reply .= "📋 **Jadual Temujanji Hari Ini**:\n";
                foreach ($appts->take(8) as $apt) {
                    $timeFormatted = Carbon::parse($apt->time)->format('g:i A');
                    $plate = $apt->vehicle->plate_number ?? 'GUEST';
                    $plateStr = $plate !== 'GUEST' ? "[**{$plate}**]({$linkPrefix}{$apt->id})" : "**GUEST**";
                    $cust = $apt->walkin_name ?: ($apt->user->name ?? 'Pelanggan');
                    $badge = match($apt->status) {
                        'confirmed' => '🟢 Sah',
                        'completed' => '🔵 Selesai',
                        'cancelled' => '🔴 Batal',
                        default     => '🟡 Pending',
                    };
                    $reply .= "• `{$timeFormatted}` &bull; {$plateStr} &bull; {$cust} &bull; _{$apt->service_type}_ [{$badge}]\n";
                }
                if ($appts->count() > 8) {
                    $reply .= "_+ " . ($appts->count() - 8) . " temujanji lagi hari ini._\n";
                }
                $reply .= "\n";
            }

            $reply .= "🏭 **Denyut Lantai Bengkel**:\n"
                   . "• Job Card Sedang Aktif Dibaiki: **{$activeJobCards} kenderaan**\n"
                   . "• Kenderaan Selesai Dibaiki Hari Ini: **{$completedJobsToday} unit**\n"
                   . "• Alat Ganti Stok Rendah: **{$lowStockParts} item** " . ($lowStockParts > 0 ? "⚠️ (Perlu PO restock)" : "✅") . "\n\n"
                   . "💡 **Tip**: Taip nombor plat mana-mana kenderaan (contoh: *'JQP 1122'*) untuk melihat butiran penuh temujanji, mekanik bertugas & status Job Card lantai secara terperinci.\n\n"
                   . "👉 Pautan Pantas: [Urus Temujanji](/admin/appointments) &bull; [Lantai Job Cards](/admin/job-cards) &bull; [Pesanan PO](/admin/purchase-orders)";

            return $reply;
        }

        $reply = "📊 **Daily Workshop & Appointment Report ({$dateLabel})**\n\n"
               . "• **Capacity Utilization**: **{$totalAppts} / {$capacity} slots** ({$utilization}%)\n"
               . "• **Appointment Status Breakdown**:\n"
               . "  🟢 Confirmed: **{$confirmed}**\n"
               . "  🟡 Pending: **{$pending}**\n"
               . "  🔵 Completed: **{$completed}**\n"
               . ($cancelled > 0 ? "  🔴 Cancelled: **{$cancelled}**\n" : "")
               . "\n";

        if ($appts->isEmpty()) {
            $reply .= "ℹ️ _No appointments booked for today yet._\n\n";
        } else {
            $reply .= "📋 **Today's Scheduled Bay Arrivals**:\n";
            foreach ($appts->take(8) as $apt) {
                $timeFormatted = Carbon::parse($apt->time)->format('g:i A');
                $plate = $apt->vehicle->plate_number ?? 'GUEST';
                $plateStr = $plate !== 'GUEST' ? "[**{$plate}**]({$linkPrefix}{$apt->id})" : "**GUEST**";
                $cust = $apt->walkin_name ?: ($apt->user->name ?? 'Customer');
                $badge = ucfirst($apt->status);
                $reply .= "• `{$timeFormatted}` &bull; {$plateStr} &bull; {$cust} &bull; _{$apt->service_type}_ [{$badge}]\n";
            }
            if ($appts->count() > 8) {
                $reply .= "_+ " . ($appts->count() - 8) . " more appointments today._\n";
            }
            $reply .= "\n";
        }

        $reply .= "🏭 **Workshop Floor Pulse**:\n"
               . "• Active Repair Job Cards: **{$activeJobCards} vehicles**\n"
               . "• Repairs Completed Today: **{$completedJobsToday} vehicles**\n"
               . "• Parts Below Minimum Stock: **{$lowStockParts} items** " . ($lowStockParts > 0 ? "⚠️" : "✅") . "\n\n"
               . "💡 **Tip**: Type any vehicle plate number (e.g. *'JQP 1122'*) to inspect complete appointment details, assigned technician & floor Job Card status.\n\n"
               . "👉 Quick Links: [Manage Appointments](/admin/appointments) &bull; [Job Cards Floor](/admin/job-cards) &bull; [Purchase Orders](/admin/purchase-orders)";

        return $reply;
    }

    // ----------------------------------------------------------------
    // Direct Invoice Search in Chatbot
    // ----------------------------------------------------------------

    private function isInvoiceSearchQuery(string $msg): bool {
        $lower = strtolower(trim($msg));
        return (bool) (
            preg_match('/\bINV(?:-\d{4})?-\d{1,6}\b/i', $msg)
            || preg_match('/(cari|search|find|semak|check|status|lihat|view)\s*(invois|invoice|bil|bill)/i', $lower)
            || preg_match('/(invois|invoice|bil)\s*(belum\s*bayar|tertunggak|unpaid|overdue|outstanding)/i', $lower)
            || preg_match('/(invois|invoice)\s*(saya|syarikat|pelanggan|customer|no|nombor|\#)/i', $lower)
        );
    }

    private function handleInvoiceSearchQuery(string $msg, $user, string $lang): string {
        $lower = strtolower(trim($msg));

        $query = Invoice::with(['jobCard.vehicle', 'jobCard.appointment.user']);

        // Permissions scoping
        if ($user) {
            if ($user->role === 'corporate') {
                $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
                $query->whereHas('jobCard.appointment', fn($q) => $q->whereIn('user_id', $companyUserIds));
            } elseif ($user->role === 'individual') {
                $query->whereHas('jobCard.appointment', fn($q) => $q->where('user_id', $user->id));
            }
        } else {
            return $lang === 'ms'
                ? "🔒 Sila log masuk ke akaun anda untuk melihat atau mencari invois kenderaan anda. Jika anda tetamu korporat atau individu, invois boleh diakses melalui dashboard akaun."
                : "🔒 Please log in to your account to view or search invoices. Corporate and retail invoices are accessible from your portal.";
        }

        // Match exact invoice number (e.g. INV-2026-00044, 00044, 44)
        if (preg_match('/INV(?:-\d{4})?-(\d{1,6})/i', $msg, $invNumMatch)) {
            $numPart = $invNumMatch[1];
            $query->where(function($q) use ($numPart, $invNumMatch) {
                $q->where('invoice_number', 'LIKE', "%{$invNumMatch[0]}%")
                  ->orWhere('invoice_number', 'LIKE', "%{$numPart}%")
                  ->orWhere('id', (int) $numPart);
            });
        } elseif (preg_match('/(?:no\.?|nombor|number|\#)\s*(\d{1,6})\b/i', $msg, $digitMatch)) {
            $query->where(function($q) use ($digitMatch) {
                $q->where('invoice_number', 'LIKE', "%{$digitMatch[1]}%")
                  ->orWhere('id', (int) $digitMatch[1]);
            });
        } elseif (str_contains($lower, 'belum bayar') || str_contains($lower, 'unpaid') || str_contains($lower, 'tertunggak') || str_contains($lower, 'outstanding')) {
            $query->whereIn('status', ['unpaid', 'partial']);
        } elseif (str_contains($lower, 'paid') || str_contains($lower, 'selesai bayar') || str_contains($lower, 'dah bayar')) {
            $query->where('status', 'paid');
        } else {
            // Check if searching by plate number
            if (preg_match('/(?<!\w)[A-Z]{1,3}\s*\d{1,4}\s*[A-Z]?(?!\w)/i', $msg, $plateMatch)) {
                $plate = strtoupper(trim($plateMatch[0]));
                $query->whereHas('jobCard.vehicle', fn($q) => $q->where('plate_number', 'LIKE', "%{$plate}%"));
            } else {
                // Check customer name search
                $cleanedTerm = trim(preg_replace('/(cari|search|find|semak|check|invois|invoice|bil|bill|pelanggan|customer)/i', '', $msg));
                if (strlen($cleanedTerm) >= 3) {
                    $query->where(function($q) use ($cleanedTerm) {
                        $q->where('customer_name', 'LIKE', "%{$cleanedTerm}%")
                          ->orWhere('invoice_number', 'LIKE', "%{$cleanedTerm}%");
                    });
                }
            }
        }

        $invoices = $query->latest()->take(5)->get();

        if ($invoices->isEmpty()) {
            return $lang === 'ms'
                ? "🔍 **Tiada Invois Dijumpai**\n\nTiada rekod invois yang sepadan dengan carian anda. Sila pastikan nombor invois (contoh: `INV-2026-00044`), nombor plat kenderaan, atau nama pelanggan adalah tepat."
                : "🔍 **No Invoices Found**\n\nNo invoice records matched your search query. Please ensure the invoice number (e.g. `INV-2026-00044`), vehicle plate, or customer name is correct.";
        }

        // Remember the most relevant invoice in session for follow-up actions (e.g. "tukar kepada paid")
        $firstInv = $invoices->first();
        session([
            'chatbot_last_invoice_id'     => $firstInv->id,
            'chatbot_last_invoice_number' => $firstInv->invoice_number,
        ]);

        $linkPrefix = match($user->role) {
            'admin'       => '/admin/invoices/',
            'coordinator' => '/coordinator/invoices/',
            'staff'       => '/staff/invoices/',
            'corporate'   => '/client/invoices/',
            'individual'  => '/customer/invoices/',
            default       => '/customer/invoices/',
        };

        $header = $lang === 'ms'
            ? "🧾 **Keputusan Carian Invois (" . $invoices->count() . " dijumpai)**:\n\n"
            : "🧾 **Invoice Search Results (" . $invoices->count() . " found)**:\n\n";

        $reply = $header;

        foreach ($invoices as $inv) {
            $badge = match($inv->status) {
                'paid'    => '🟢 LUNAS / PAID',
                'partial' => '🟡 SEBAHAGIAN / PARTIAL',
                default   => '🔴 BELUM BAYAR / UNPAID',
            };

            $plate = $inv->jobCard->vehicle->plate_number ?? '—';
            $carDesc = trim(($inv->jobCard->vehicle->brand ?? '') . ' ' . ($inv->jobCard->vehicle->model ?? ''));
            $balance = number_format($inv->balance, 2);
            $total = number_format($inv->total, 2);
            $dueDate = $inv->due_date ? Carbon::parse($inv->due_date)->format('d M Y') : '—';

            $actionLinks = "[Lihat Invois]({$linkPrefix}{$inv->id})";
            if (in_array($user->role, ['admin', 'staff']) && $inv->balance > 0) {
                $actionLinks .= " &bull; [💳 Rekod Bayaran]({$linkPrefix}{$inv->id}#payment-section)";
            }

            $reply .= "• **{$inv->invoice_number}** &bull; {$actionLinks}\n"
                    . "  Status: **{$badge}**\n"
                    . "  Pelanggan: **{$inv->customer_name}**" . ($plate !== '—' ? " ({$plate} - {$carDesc})" : "") . "\n"
                    . "  Jumlah: **RM {$total}** &bull; Baki Tertunggak: **RM {$balance}**\n"
                    . "  Tarikh Matang: {$dueDate}\n\n";
        }

        if ($user->role === 'corporate' || $user->role === 'admin') {
            $totalOutstanding = $invoices->whereIn('status', ['unpaid', 'partial'])->sum('balance');
            if ($totalOutstanding > 0) {
                $reply .= "💡 " . ($lang === 'ms' ? "Jumlah Baki Tertunggak: **RM " . number_format($totalOutstanding, 2) . "**\n\n" : "Total Outstanding Due: **RM " . number_format($totalOutstanding, 2) . "**\n\n");
            }
        }

        if (in_array($user->role, ['admin', 'staff']) && $firstInv->balance > 0) {
            $reply .= "💡 " . ($lang === 'ms'
                ? "Tip: Anda boleh taip *'tukar kepada paid'* untuk mengesahkan bayaran invois ini, atau klik [💳 Rekod Bayaran]({$linkPrefix}{$firstInv->id}#payment-section) untuk lompat terus ke bahagian rekod bayaran.\n"
                : "Tip: You can type *'mark as paid'* to confirm settlement, or click [💳 Record Payment]({$linkPrefix}{$firstInv->id}#payment-section) to jump directly to the payment section.\n");
        }

        return $reply;
    }

    // ----------------------------------------------------------------
    // Invoice Payment Status / Mark as Paid Handler
    // ----------------------------------------------------------------

    private function isInvoicePaymentUpdateQuery(string $msg): bool {
        $lower = strtolower(trim($msg));

        // Negative check: avoid cancellations
        if (preg_match('/^(batal|cancel|tak jadi)$/i', $lower)) {
            return false;
        }

        return (bool) (
            preg_match('/(tukar|ubah|set|mark|jadikan|kemaskini|update|change)\s*(status)?\s*(invois|invoice)?\s*(kepada|ke|as|to|jadi)?\s*(paid|lunas|berbayar|settled|cleared)/i', $lower)
            || preg_match('/^(tukar\s*kepada\s*paid|tukar\s*ke\s*paid|set\s*to\s*paid|mark\s*as\s*paid|mark\s*paid|set\s*paid|paid\s*kan)$/i', $lower)
            || preg_match('/(invois|invoice)\s*(dah\s*bayar|sudah\s*bayar|dah\s*paid|sudah\s*paid)/i', $lower)
            || preg_match('/(mark|set|tukar)\s*(invois|invoice)\s*.*(paid|lunas)/i', $lower)
            || preg_match('/(bayar|rekod\s*bayaran)\s*(invois|invoice)/i', $lower)
        );
    }

    private function isConfirmInvoicePayment(string $msg): bool {
        if (!session()->has('pending_invoice_payment')) {
            return false;
        }
        $lower = strtolower(trim($msg));
        if (preg_match('/^(ya|yes|sahkan|confirm|proceed|ok|okay|setuju|lunas|paid|sahkan bayaran|confirm payment|sahkan paid)$/i', $lower)) {
            return true;
        }
        if (preg_match('/\b(sahkan bayaran|sahkan lunas|confirm payment|mark as paid now|sahkan paid)\b/i', $lower)) {
            return true;
        }
        return false;
    }

    private function handleInvoicePaymentRequest(string $msg, $user, string $lang) {
        session()->forget('chatbot_intake');

        $linkPrefix = match($user ? $user->role : 'guest') {
            'admin'       => '/admin/invoices/',
            'staff'       => '/staff/invoices/',
            'coordinator' => '/coordinator/invoices/',
            'corporate'   => '/client/invoices/',
            'individual'  => '/customer/invoices/',
            default       => '/customer/invoices/',
        };

        // 1. Check permissions
        if (!$user || !in_array($user->role, ['admin', 'staff'])) {
            $reply = $lang === 'ms'
                ? "🔒 **Kebenaran Diperlukan (Permission Restricted)**\n\nPertukaran status pembayaran invois kepada **LUNAS / PAID** hanya boleh disahkan oleh pentadbir bengkel (**Admin**) atau kakitangan bertugas (**Staff**).\n\nJika anda pelanggan, sila buat bayaran di kaunter bengkel atau hubungi kami di talian 07-787 8728."
                : "🔒 **Permission Restricted**\n\nMarking invoices as **PAID** is restricted to workshop **Admin** and **Staff** accounts only.\n\nCustomers may contact service reception at 07-787 8728 for payment processing.";
            return response()->json(['reply' => $reply, 'lang' => $lang]);
        }

        // 2. Identify target invoice
        $invoice = null;

        // Check if invoice number specified in message
        if (preg_match('/INV(?:-\d{4})?-(\d{1,6})/i', $msg, $invNumMatch)) {
            $numPart = $invNumMatch[1];
            $invoice = Invoice::with(['jobCard.vehicle', 'jobCard.appointment.user', 'payments'])
                ->where(function($q) use ($numPart, $invNumMatch) {
                    $q->where('invoice_number', 'LIKE', "%{$invNumMatch[0]}%")
                      ->orWhere('invoice_number', 'LIKE', "%{$numPart}%")
                      ->orWhere('id', (int) $numPart);
                })->first();
        } elseif (preg_match('/(?:no\.?|nombor|number|\#)\s*(\d{1,6})\b/i', $msg, $digitMatch)) {
            $invoice = Invoice::with(['jobCard.vehicle', 'jobCard.appointment.user', 'payments'])
                ->where(function($q) use ($digitMatch) {
                    $q->where('invoice_number', 'LIKE', "%{$digitMatch[1]}%")
                      ->orWhere('id', (int) $digitMatch[1]);
                })->first();
        }

        // If not in message, check session last viewed invoice
        if (!$invoice && session()->has('chatbot_last_invoice_id')) {
            $lastId = session('chatbot_last_invoice_id');
            $invoice = Invoice::with(['jobCard.vehicle', 'jobCard.appointment.user', 'payments'])->find($lastId);
        }

        // If still not found, ask user to specify
        if (!$invoice) {
            $reply = $lang === 'ms'
                ? "ℹ️ **Sila Nyatakan Nombor Invois**\n\nSila berikan nombor invois yang ingin ditukar status kepada **PAID** (contoh: *'Tukar invois INV-2026-00044 kepada paid'*), atau buat carian invois terlebih dahulu (*'Cari invois'*) sebelum membuat pertukaran status."
                : "ℹ️ **Please Specify the Invoice Number**\n\nPlease mention the invoice number you want to mark as **PAID** (e.g. *'Mark invoice INV-2026-00044 as paid'*), or search for the invoice first (*'Search invoice'*).";
            return response()->json(['reply' => $reply, 'lang' => $lang]);
        }

        $jumpUrl = "{$linkPrefix}{$invoice->id}#payment-section";

        // Check if already paid
        if ($invoice->status === 'paid' || $invoice->balance <= 0) {
            $reply = $lang === 'ms'
                ? "ℹ️ **Invois Sudah Lunas (Already Paid)**\n\nInvois **`{$invoice->invoice_number}`** (Pelanggan: **{$invoice->customer_name}**) telah pun bertaraf 🟢 **LUNAS / PAID** dengan baki tertunggak **RM 0.00**.\n\n👉 [Lihat Rekod Bayaran & Invois]({$jumpUrl})"
                : "ℹ️ **Invoice Already Paid**\n\nInvoice **`{$invoice->invoice_number}`** (Customer: **{$invoice->customer_name}**) is already marked as 🟢 **PAID** with a remaining balance of **RM 0.00**.\n\n👉 [View Payment Record & Invoice]({$jumpUrl})";
            return response()->json(['reply' => $reply, 'invoice_url' => $jumpUrl, 'lang' => $lang]);
        }

        // Store pending confirmation in session
        session([
            'pending_invoice_payment' => [
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount'         => $invoice->balance,
            ],
            'chatbot_last_invoice_id'     => $invoice->id,
            'chatbot_last_invoice_number' => $invoice->invoice_number,
        ]);

        $balFormatted = number_format($invoice->balance, 2);
        $totFormatted = number_format($invoice->total, 2);
        $plate = $invoice->jobCard->vehicle->plate_number ?? '—';
        $carDesc = trim(($invoice->jobCard->vehicle->brand ?? '') . ' ' . ($invoice->jobCard->vehicle->model ?? ''));
        $carStr = $plate !== '—' ? " ({$plate}" . ($carDesc ? " - {$carDesc}" : "") . ")" : "";
        $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d M Y') : '—';

        if ($lang === 'ms') {
            $reply = "⚠️ **Pengesahan Diperlukan (Confirmation Required)**\n\n"
                . "Anda memohon untuk menukar status pembayaran invois berikut kepada 🟢 **LUNAS / PAID**:\n\n"
                . "• **No. Invois**: **`{$invoice->invoice_number}`**\n"
                . "• **Pelanggan**: **{$invoice->customer_name}**{$carStr}\n"
                . "• **Jumlah Keseluruhan**: **RM {$totFormatted}**\n"
                . "• **Baki Tertunggak Semasa**: 🔴 **RM {$balFormatted}**\n"
                . "• **Tarikh Matang**: {$dueDate}\n\n"
                . "Demi mematuhi rekod perakaunan dan jejak audit rasmi bengkel:\n\n"
                . "👉 **Pilihan 1: Buka & Rekod Bayaran Terperinci (Disyorkan)**\n"
                . "[🔗 Klik Di Sini Untuk Lompat ke Bahagian Bayaran Invois]({$jumpUrl})\n"
                . "_(Di sini anda boleh memilih kaedah bayaran: Tunai / Kad Kredit / Online FPX / Bank Transfer beserta No. Rujukan transaksi)_\n\n"
                . "👉 **Pilihan 2: Sahkan Lunas Terus Melalui Chatbot (Tunai)**\n"
                . "Klik butang **Sahkan Lunas (Paid) Sekarang** di bawah untuk merekodkan bayaran penuh RM {$balFormatted} secara automatik.";
        } else {
            $reply = "⚠️ **Confirmation Required**\n\n"
                . "You have requested to mark the following invoice as 🟢 **PAID**:\n\n"
                . "• **Invoice No**: **`{$invoice->invoice_number}`**\n"
                . "• **Customer**: **{$invoice->customer_name}**{$carStr}\n"
                . "• **Total Amount**: **RM {$totFormatted}**\n"
                . "• **Current Outstanding Balance**: 🔴 **RM {$balFormatted}**\n"
                . "• **Due Date**: {$dueDate}\n\n"
                . "For official accounting and audit compliance:\n\n"
                . "👉 **Option 1: Jump to Invoice Payment Section (Recommended)**\n"
                . "[🔗 Click Here to Jump to Invoice Payment Section]({$jumpUrl})\n"
                . "_(Record specific payment methods: Cash / Card / Online FPX / Bank Transfer with reference number)_\n\n"
                . "👉 **Option 2: Confirm Directly via Chatbot (Cash)**\n"
                . "Click **Confirm Mark as Paid Now** below to record full settlement of RM {$balFormatted}.";
        }

        return response()->json([
            'reply'       => $reply,
            'action'      => 'confirm_invoice_payment',
            'invoice_url' => $jumpUrl,
            'invoice_id'  => $invoice->id,
            'lang'        => $lang,
        ]);
    }

    private function executeInvoicePayment(array $pending, $user, string $lang) {
        if (!$user || !in_array($user->role, ['admin', 'staff'])) {
            return response()->json([
                'reply' => $lang === 'ms'
                    ? "⚠️ Anda tidak mempunyai kebenaran untuk merekod bayaran invois."
                    : "⚠️ You do not have permission to record invoice payments.",
                'lang'  => $lang,
            ]);
        }

        $invoice = Invoice::with(['payments', 'jobCard.vehicle', 'jobCard.appointment.user'])->find($pending['invoice_id']);
        if (!$invoice) {
            session()->forget('pending_invoice_payment');
            return response()->json([
                'reply' => $lang === 'ms' ? "⚠️ Invois tidak dijumpai." : "⚠️ Invoice not found.",
                'lang'  => $lang,
            ]);
        }

        $amountToPay = $invoice->balance > 0 ? $invoice->balance : (float) $pending['amount'];

        if ($amountToPay > 0) {
            $refNo = 'CHATBOT-REC-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
            $invoice->payments()->create([
                'amount'       => $amountToPay,
                'method'       => 'cash',
                'reference_no' => $refNo,
                'notes'        => 'Pembayaran penuh disahkan melalui Pembantu AI Chatbot',
                'recorded_by'  => $user->id,
                'paid_at'      => now(),
            ]);

            $invoice->recalculate();
            $invoice->refresh();

            // Send invoice receipt email if customer user has email
            $this->sendInvoiceReceiptEmail($invoice);

            \App\Models\ActivityLog::record(
                'invoice.payment',
                "Recorded RM " . number_format($amountToPay, 2) . " full payment (Marked as PAID via Chatbot) for invoice {$invoice->invoice_number}",
                $invoice
            );
        }

        session()->forget('pending_invoice_payment');

        $linkPrefix = match($user->role) {
            'admin'       => '/admin/invoices/',
            'staff'       => '/staff/invoices/',
            'coordinator' => '/coordinator/invoices/',
            default       => '/admin/invoices/',
        };
        $jumpUrl = "{$linkPrefix}{$invoice->id}#payment-section";

        $reply = $lang === 'ms'
            ? "✅ **Status Invois Berjaya Ditukar Kepada LUNAS / PAID!**\n\n"
            . "• **No. Invois**: **`{$invoice->invoice_number}`**\n"
            . "• **Pelanggan**: **{$invoice->customer_name}**\n"
            . "• **Bayaran Direkodkan**: **RM " . number_format($amountToPay, 2) . "** (Tunai / Chatbot Verified)\n"
            . "• **Status Terkini**: 🟢 **LUNAS / PAID**\n"
            . "• **Baki Tertunggak**: **RM 0.00**\n\n"
            . "👉 [🔗 Buka Invois & Lihat Resit Rasmi]({$jumpUrl})"
            : "✅ **Invoice Successfully Marked as PAID!**\n\n"
            . "• **Invoice No**: **`{$invoice->invoice_number}`**\n"
            . "• **Customer**: **{$invoice->customer_name}**\n"
            . "• **Payment Recorded**: **RM " . number_format($amountToPay, 2) . "** (Cash / Chatbot Verified)\n"
            . "• **Current Status**: 🟢 **PAID**\n"
            . "• **Remaining Balance**: **RM 0.00**\n\n"
            . "👉 [🔗 Open Invoice & View Official Receipt]({$jumpUrl})";

        return response()->json([
            'reply'       => $reply,
            'invoice_url' => $jumpUrl,
            'lang'        => $lang,
        ]);
    }

    private function sendInvoiceReceiptEmail(Invoice $invoice): void {
        $invoice->load('jobCard.appointment.user');
        $email = $invoice->jobCard->appointment->user->email ?? null;
        if (!$email) return;

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\InvoiceMail($invoice));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Chatbot invoice receipt email failed: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Vehicle Plate & Appointment Lookup Handlers
    // ----------------------------------------------------------------

    private function isPlateOrAppointmentQuery(string $msg, ?array $userContext = null): bool {
        // If an ordinary customer has an active booking intake in progress, don't interrupt their symptom intake
        if (session()->has('chatbot_intake') && empty($userContext['is_internal_staff'])) {
            return false;
        }

        $parsed = $this->extractPlateOrAppointment($msg);
        return !is_null($parsed);
    }

    private function extractPlateOrAppointment(string $msg): ?array {
        $clean = trim($msg);

        // Negative check: skip general status questions without plate/id (e.g. "status", "apa status", "check status")
        if (preg_match('/^(status|apa status|check status|semak status|lihat status|rekod|bantuan|help)$/i', $clean)) {
            return null;
        }

        // 1. Appointment ID pattern (e.g. "apt-45", "apt 45", "temujanji #45", "appointment #45", "appointment 45", "#45")
        if (preg_match('/(?:temujanji|appointment|apt|booking)\s*[:\#\-]?\s*(\d{1,6})\b/i', $clean, $m)) {
            return ['type' => 'appointment_id', 'value' => (int)$m[1]];
        }
        if (preg_match('/^#(\d{1,6})$/', $clean, $m)) {
            return ['type' => 'appointment_id', 'value' => (int)$m[1]];
        }

        // 2. Explicit Plate query with keywords (e.g. "temujanji JQP 1122", "status plat JQP 1122", "semak JQP 1122", "kereta JQP 1122", "cari JQP 1122", "info JQP 1122", "detail JQP 1122")
        if (preg_match('/(?:temujanji|appointment|status|semak|check|kereta|car|plat|plate|no\.?\s*plat|detail|butiran|info|cari|search)\s*(?:untuk|for|kenderaan|kereta)?\s*[:\#\-]?\s*([A-Za-z]{1,3}\s*\d{1,4}(?:\s*[A-Za-z])?)/i', $clean, $m)) {
            $formatted = strtoupper(preg_replace('/\s+/', ' ', trim($m[1])));
            return ['type' => 'plate', 'value' => $formatted];
        }

        // 3. Standalone Plate format (e.g. "JQP 1122", "JQP1122", "KB 1234 A", "SAB 6677", "W 1234 A")
        // Malaysian registration plates: 1 to 3 letters followed by 1 to 4 digits, optional 1 trailing letter
        if (preg_match('/^([A-Za-z]{1,3})\s*(\d{1,4})\s*([A-Za-z]?)$/i', $clean, $m)) {
            $alpha = strtoupper($m[1]);
            $num   = $m[2];
            $trail = strtoupper($m[3]);

            // Exclude common non-plate words of 1-3 letters unless verified in DB
            $nonPlateWords = ['NAK', 'DAN', 'KAT', 'APA', 'DAH', 'ADA', 'THE', 'AND', 'FOR', 'NOT', 'YES', 'CAN', 'DAY', 'CAR', 'OUT', 'NEW', 'OLD', 'SET', 'JAM', 'MIN', 'AIR', 'OIL', 'BEG', 'LOR', 'DUA', 'TGA', 'SAT', 'HAI'];
            if (!in_array($alpha, $nonPlateWords)) {
                $formattedPlate = $alpha . ' ' . $num . ($trail ? ' ' . $trail : '');
                return ['type' => 'plate', 'value' => $formattedPlate];
            }
        }

        // 4. Any message containing a plate that exists in the database
        // Exclude common non-plate words (e.g. JAM, MIN, AIR, OIL) so time expressions like 'jam 10:30' are not confused
        if (preg_match_all('/(?<!\w)(?!(?:at|in|on|by|to|for|is|am|pm|an|no|po|km|rm|jam|hari|sesi|bulan|tahun|dan|dari)\b)([A-Za-z]{1,3})\s*(\d{1,4})(?:\s*([A-Za-z]))?(?!\w)/i', $clean, $allMatches, PREG_SET_ORDER)) {
            $nonPlateWords = ['NAK', 'DAN', 'KAT', 'APA', 'DAH', 'ADA', 'THE', 'AND', 'FOR', 'NOT', 'YES', 'CAN', 'DAY', 'CAR', 'OUT', 'NEW', 'OLD', 'SET', 'JAM', 'MIN', 'AIR', 'OIL', 'BEG', 'LOR', 'DUA', 'TGA', 'SAT', 'HAI'];
            foreach ($allMatches as $m) {
                $alpha = strtoupper($m[1]);
                if (in_array($alpha, $nonPlateWords)) {
                    continue;
                }
                $candidate = $alpha . ' ' . $m[2] . (!empty($m[3]) ? ' ' . strtoupper($m[3]) : '');
                $candidateNoSpace = str_replace(' ', '', $candidate);
                $vehicle = Vehicle::where('plate_number', $candidate)
                    ->orWhereRaw("REPLACE(plate_number, ' ', '') = ?", [$candidateNoSpace])
                    ->first();
                if ($vehicle) {
                    return ['type' => 'plate', 'value' => $vehicle->plate_number];
                }
            }
        }

        return null;
    }

    private function handlePlateOrAppointmentQuery(string $msg, $user, string $lang, ?array $userContext = null): string {
        $userContext = $userContext ?: $this->getUserContext($user);
        $isInternal = !empty($userContext['is_internal_staff']);
        $role = $user ? $user->role : 'guest';

        $parsed = $this->extractPlateOrAppointment($msg);
        if (!$parsed) {
            return $lang === 'ms'
                ? "ℹ️ Sila berikan nombor plat kenderaan (contoh: *'JQP 1122'*) atau ID temujanji (contoh: *'temujanji #45'*) yang ingin disemak."
                : "ℹ️ Please specify a vehicle plate number (e.g. *'JQP 1122'*) or appointment ID (e.g. *'appointment #45'*).";
        }

        $appointment = null;
        $vehicle = null;

        if ($parsed['type'] === 'appointment_id') {
            $appointment = Appointment::with([
                'vehicle.owner.company',
                'jobCard.staff',
                'jobCard.invoice',
                'user.company'
            ])->find($parsed['value']);

            if (!$appointment) {
                return $lang === 'ms'
                    ? "🔍 **Temujanji #{$parsed['value']} Tidak Dijumpai**\n\nTiada rekod temujanji dengan nombor rujukan ini dalam sistem bengkel. Sila semak semula nombor ID atau hubungi kaunter servis kami."
                    : "🔍 **Appointment #{$parsed['value']} Not Found**\n\nNo appointment record matching this reference ID was found. Please verify the ID or contact workshop reception.";
            }

            $vehicle = $appointment->vehicle;
        } else {
            // Searched by Plate
            $plate = $parsed['value'];
            $plateNoSpace = str_replace(' ', '', $plate);

            $vehicle = Vehicle::with([
                'owner.company',
                'appointments' => fn($q) => $q->with(['user.company', 'jobCard.staff', 'jobCard.invoice'])->orderByDesc('date')->orderByDesc('time'),
                'jobCards' => fn($q) => $q->with(['staff', 'invoice'])->latest(),
            ])->where(function($q) use ($plate, $plateNoSpace) {
                $q->where('plate_number', $plate)
                  ->orWhereRaw("REPLACE(plate_number, ' ', '') = ?", [$plateNoSpace]);
            })->first();

            if (!$vehicle) {
                // Vehicle not found in DB
                if ($isInternal) {
                    $regLink = match($role) {
                        'admin'       => '/admin/vehicles/create',
                        'coordinator' => '/coordinator/appointments/walkin',
                        'staff'       => '/staff/appointments/walkin',
                        default       => '/admin/vehicles/create',
                    };
                    $walkinLink = match($role) {
                        'admin'       => '/admin/appointments/walkin',
                        'coordinator' => '/coordinator/appointments/walkin',
                        'staff'       => '/staff/appointments/walkin',
                        default       => '/admin/appointments/walkin',
                    };

                    return $lang === 'ms'
                        ? "🔍 **Carian Kenderaan: `{$plate}` Tidak Dijumpai**\n\n"
                        . "Kenderaan dengan nombor plat **`{$plate}`** belum didaftarkan di dalam pangkalan data bengkel kami.\n\n"
                        . "Sebagai kakitangan bertugas, anda boleh mendaftarkan kenderaan ini atau membuka temujanji walk-in di sini:\n\n"
                        . "👉 [🚗 Daftar Kenderaan Baru]({$regLink})\n"
                        . "👉 [📝 Daftar Temujanji Walk-in Kaunter]({$walkinLink})"
                        : "🔍 **Vehicle Search: `{$plate}` Not Found**\n\n"
                        . "No vehicle with plate number **`{$plate}`** was found in the workshop database.\n\n"
                        . "Workshop personnel can register this vehicle or create a walk-in intake here:\n\n"
                        . "👉 [🚗 Register New Vehicle]({$regLink})\n"
                        . "👉 [📝 Register Walk-in Appointment]({$walkinLink})";
                } else {
                    return $lang === 'ms'
                        ? "🔍 **Kenderaan Tidak Dijumpai**\n\nKenderaan dengan nombor plat **`{$plate}`** tidak didaftarkan di bawah akaun anda. Jika ini kenderaan baharu, sila hubungi kaunter servis kami di 07-787 8728 atau buat tempahan temujanji untuk pendaftaran automatik."
                        : "🔍 **Vehicle Not Found**\n\nNo vehicle with plate **`{$plate}`** was found under your account. If this is a new vehicle, please contact reception at 07-787 8728 or book an appointment for automatic registration.";
                }
            }

            // Customer security check
            if (!$isInternal && $user) {
                $allowedIds = $this->scopeUserIds($user);
                if (!in_array($vehicle->user_id, $allowedIds)) {
                    return $lang === 'ms'
                        ? "🔒 **Akses Terhad**: Kenderaan **`{$vehicle->plate_number}`** didaftarkan di bawah pemilik lain. Anda hanya dibenarkan melihat butiran kenderaan di bawah akaun anda."
                        : "🔒 **Access Restricted**: Vehicle **`{$vehicle->plate_number}`** is registered under a different owner. You can only view vehicles registered to your account.";
                }
            }

            // Identify relevant appointment: today's first, then upcoming, then latest
            $appointment = $vehicle->appointments->first(fn($a) => Carbon::parse($a->date)->isToday())
                ?: $vehicle->appointments->first(fn($a) => Carbon::parse($a->date)->isFuture())
                ?: $vehicle->appointments->first();
        }

        // Extract associated JobCard and Invoice
        $jobCard = $appointment?->jobCard ?: $vehicle->jobCards->first();
        $invoice = $jobCard?->invoice;

        // Determine link URLs based on user role
        $apptUrl = null;
        $jobCardUrl = null;
        $vehicleUrl = null;
        $invUrl = null;

        if ($appointment) {
            $apptUrl = match($role) {
                'admin'       => "/admin/appointments/{$appointment->id}",
                'coordinator' => "/coordinator/appointments/{$appointment->id}",
                'corporate'   => "/client/appointments/{$appointment->id}",
                'individual'  => "/customer/appointments/{$appointment->id}",
                default       => null,
            };
        }

        if ($jobCard) {
            $jobCardUrl = match($role) {
                'admin'       => "/admin/job-cards/{$jobCard->id}",
                'coordinator' => "/coordinator/job-cards/{$jobCard->id}",
                'staff'       => "/staff/job-cards/{$jobCard->id}",
                default       => null,
            };
        }

        if ($vehicle) {
            $vehicleUrl = match($role) {
                'admin'       => "/admin/vehicles/{$vehicle->id}",
                'corporate'   => "/client/vehicles/{$vehicle->id}",
                'individual'  => "/customer/vehicles/{$vehicle->id}",
                default       => null,
            };
        }

        if ($invoice) {
            $invUrl = match($role) {
                'admin'       => "/admin/invoices/{$invoice->id}",
                'coordinator' => "/coordinator/invoices/{$invoice->id}",
                'staff'       => "/staff/invoices/{$invoice->id}",
                'corporate'   => "/client/invoices/{$invoice->id}",
                'individual'  => "/customer/invoices/{$invoice->id}",
                default       => "/admin/invoices/{$invoice->id}",
            };
        }

        // Vehicle info lines
        $vBrandModel = trim("{$vehicle->brand} {$vehicle->model}");
        $vYear = $vehicle->year ? " ({$vehicle->year})" : "";
        $vMileage = $vehicle->mileage ? number_format($vehicle->mileage) . " km" : "—";
        $vOwner = $vehicle->owner_display_name;
        $vContact = $vehicle->owner->contact_no ?? $vehicle->owner->email ?? ($appointment?->customer_contact ?? '—');
        $vType = $vehicle->owner_type_label;

        // Build Response in Malay or English
        if ($lang === 'ms') {
            $reply = "🚗 **Butiran Lengkap Kenderaan & Temujanji (`{$vehicle->plate_number}`)**\n\n"
                   . "• **Nombor Plat**: **`{$vehicle->plate_number}`**\n"
                   . "• **Model Kenderaan**: **{$vBrandModel}**{$vYear} &bull; Perbatuan: **{$vMileage}**\n"
                   . "• **Pemilik / Pelanggan**: **{$vOwner}** ({$vType})\n"
                   . "• **No. Telefon**: **{$vContact}**\n\n";

            if ($appointment) {
                $aptDate = Carbon::parse($appointment->date)->format('d M Y');
                $aptTime = Carbon::parse($appointment->time)->format('g:i A');
                $aptBadge = match($appointment->status) {
                    'confirmed' => '🟢 Disahkan (Confirmed)',
                    'completed' => '🔵 Selesai (Completed)',
                    'cancelled' => '🔴 Dibatalkan (Cancelled)',
                    default     => '🟡 Menunggu Pengesahan (Pending)',
                };
                $notes = $appointment->notes ? $appointment->notes : '—';

                $reply .= "📅 **Maklumat Temujanji (#{$appointment->id})**:\n"
                       . "• **Tarikh & Masa**: **{$aptDate} &bull; {$aptTime}**\n"
                       . "• **Jenis Servis**: **{$appointment->service_type}**\n"
                       . "• **Status**: {$aptBadge}\n"
                       . "• **Catatan**: {$notes}\n\n";
            } else {
                $reply .= "📅 **Maklumat Temujanji**:\n"
                       . "• _Tiada temujanji aktif berjadual pada masa ini._\n\n";
            }

            if ($jobCard) {
                $stageBadge = match($jobCard->current_stage) {
                    'received'      => '🟡 Kenderaan Diterima (Received)',
                    'inspection'    => '🔍 Sedang Diperiksa (Inspection)',
                    'diagnosis'     => '🧪 Diagnosis & Sebut Harga (Diagnosis)',
                    'waiting_parts' => '⏳ Menunggu Alat Ganti (Waiting Parts)',
                    'repairing'     => '⚙️ Sedang Dibaiki (Repairing)',
                    'completed'     => '✅ Siap Dibaiki (Completed)',
                    default         => ucfirst($jobCard->current_stage),
                };

                $mechanicName = $jobCard->staff
                    ? "👨‍🔧 **{$jobCard->staff->name}** (" . ($jobCard->staff->staff_role ?? 'Teknisi Bengkel') . ")"
                    : "⚠️ _Belum ditugaskan (Unassigned)_";

                $symptomsStr = '—';
                if (!empty($jobCard->diagnosis)) {
                    $symptomsStr = $jobCard->diagnosis;
                } elseif (!empty($jobCard->symptoms)) {
                    $symptomsStr = is_array($jobCard->symptoms) ? implode(', ', $jobCard->symptoms) : $jobCard->symptoms;
                }

                $costFormatted = number_format($jobCard->total_cost, 2);

                $reply .= "🛠️ **Status Lantai Bengkel & Job Card (#{$jobCard->id})**:\n"
                       . "• **Peringkat (Stage)**: {$stageBadge}\n"
                       . "• **Mekanik Bertugas**: {$mechanicName}\n"
                       . "• **Aduan / Simptom**: {$symptomsStr}\n"
                       . "• **Anggaran / Jumlah Kos**: **RM {$costFormatted}**\n\n";
            }

            if ($invoice) {
                $invStatus = ($invoice->status === 'paid' || $invoice->balance <= 0)
                    ? "🟢 **LUNAS / RM 0.00**"
                    : "🔴 **Baki Tertunggak: RM " . number_format($invoice->balance, 2) . "**";

                $reply .= "💳 **Maklumat Invois**:\n"
                       . "• **No. Invois**: **`{$invoice->invoice_number}`** [{$invStatus}]\n\n";
            }

            $reply .= "👉 **Pautan Pantas (Jumplinks)**:\n";
            $links = [];
            if ($apptUrl) $links[] = "[📅 Urus Temujanji]({$apptUrl})";
            if ($jobCardUrl) $links[] = "[🛠️ Buka Job Card Lantai]({$jobCardUrl})";
            if ($vehicleUrl) $links[] = "[🚗 Profil Kenderaan]({$vehicleUrl})";
            if ($invUrl) $links[] = "[💳 Buka Invois]({$invUrl})";

            $reply .= count($links) ? implode(" &bull; ", $links) : "—";
        } else {
            // English version
            $reply = "🚗 **Vehicle & Appointment Inspection Summary (`{$vehicle->plate_number}`)**\n\n"
                   . "• **Plate Number**: **`{$vehicle->plate_number}`**\n"
                   . "• **Vehicle Specs**: **{$vBrandModel}**{$vYear} &bull; Mileage: **{$vMileage}**\n"
                   . "• **Owner / Client**: **{$vOwner}** ({$vType})\n"
                   . "• **Contact Number**: **{$vContact}**\n\n";

            if ($appointment) {
                $aptDate = Carbon::parse($appointment->date)->format('d M Y');
                $aptTime = Carbon::parse($appointment->time)->format('g:i A');
                $aptBadge = match($appointment->status) {
                    'confirmed' => '🟢 Confirmed',
                    'completed' => '🔵 Completed',
                    'cancelled' => '🔴 Cancelled',
                    default     => '🟡 Pending',
                };
                $notes = $appointment->notes ? $appointment->notes : '—';

                $reply .= "📅 **Appointment Details (#{$appointment->id})**:\n"
                       . "• **Date & Time**: **{$aptDate} &bull; {$aptTime}**\n"
                       . "• **Service**: **{$appointment->service_type}**\n"
                       . "• **Status**: {$aptBadge}\n"
                       . "• **Intake Notes**: {$notes}\n\n";
            } else {
                $reply .= "📅 **Appointment Details**:\n"
                       . "• _No active scheduled appointment at this time._\n\n";
            }

            if ($jobCard) {
                $stageBadge = match($jobCard->current_stage) {
                    'received'      => '🟡 Vehicle Received',
                    'inspection'    => '🔍 Inspection in Progress',
                    'diagnosis'     => '🧪 Diagnosis & Quote',
                    'waiting_parts' => '⏳ Waiting Parts',
                    'repairing'     => '⚙️ Repairing in Bay',
                    'completed'     => '✅ Completed',
                    default         => ucfirst($jobCard->current_stage),
                };

                $mechanicName = $jobCard->staff
                    ? "👨‍🔧 **{$jobCard->staff->name}** (" . ($jobCard->staff->staff_role ?? 'Technician') . ")"
                    : "⚠️ _Unassigned_";

                $symptomsStr = '—';
                if (!empty($jobCard->diagnosis)) {
                    $symptomsStr = $jobCard->diagnosis;
                } elseif (!empty($jobCard->symptoms)) {
                    $symptomsStr = is_array($jobCard->symptoms) ? implode(', ', $jobCard->symptoms) : $jobCard->symptoms;
                }

                $costFormatted = number_format($jobCard->total_cost, 2);

                $reply .= "🛠️ **Workshop Bay & Job Card (#{$jobCard->id})**:\n"
                       . "• **Bay Stage**: {$stageBadge}\n"
                       . "• **Assigned Technician**: {$mechanicName}\n"
                       . "• **Symptoms / Diagnosis**: {$symptomsStr}\n"
                       . "• **Estimated / Total Cost**: **RM {$costFormatted}**\n\n";
            }

            if ($invoice) {
                $invStatus = ($invoice->status === 'paid' || $invoice->balance <= 0)
                    ? "🟢 **PAID / RM 0.00**"
                    : "🔴 **Balance Due: RM " . number_format($invoice->balance, 2) . "**";

                $reply .= "💳 **Billing & Invoice**:\n"
                       . "• **Invoice**: **`{$invoice->invoice_number}`** [{$invStatus}]\n\n";
            }

            $reply .= "👉 **Quick Action Links**:\n";
            $links = [];
            if ($apptUrl) $links[] = "[📅 Manage Appointment]({$apptUrl})";
            if ($jobCardUrl) $links[] = "[🛠️ Open Floor Job Card]({$jobCardUrl})";
            if ($vehicleUrl) $links[] = "[🚗 Vehicle Profile]({$vehicleUrl})";
            if ($invUrl) $links[] = "[💳 Open Invoice]({$invUrl})";

            $reply .= count($links) ? implode(" &bull; ", $links) : "—";
        }

        return $reply;
    }

    private function isInventoryQuery(string $msg): bool {
        $lower = strtolower($msg);
        return (bool) (preg_match('/(stok|stock).*(rendah|low|kurang|habis|tinggal)/i', $lower)
            || preg_match('/(low\s*(?:in\s*)?stock|check\s*stock|baki\s*stok|status\s*stok)/i', $lower)
            || preg_match('/(parts?|spare\s*parts?|items?)\s*(?:are\s*)?(?:low|running\s*low|out\s*of\s*stock)/i', $lower)
            || preg_match('/(bila|when|kena|perlu)?\s*(agaknya)?\s*(last|terakhir|latest)?\s*(saya|kita)?\s*(kena|perlu)?\s*(topup|top-up|restock|order\s*stok|purchase\s*order|\bpo\b)/i', $lower)
            || preg_match('/(barang|spare\s*part).*(habis|kurang|rendah)/i', $lower)
            || preg_match('/(ada|semak|check|cari)\s*stok/i', $lower)
            || preg_match('/stok\s*(ada|tinggal|baki|berapa|apa|alat\s*ganti)/i', $lower)
            || preg_match('/(spare\s*part|alat\s*ganti)\s*(stock|stok|ada|tinggal)/i', $lower));
    }

    private function handleInventoryQuery(string $msg, $user, string $lang): string {
        $lower = strtolower($msg);

        // Check if query is about Purchase Orders or Restock analysis
        if (str_contains($lower, 'topup') || str_contains($lower, 'top-up') || str_contains($lower, 'restock') || str_contains($lower, 'purchase order') || preg_match('/\bpo\b/i', $lower) || (str_contains($lower, 'order') && !str_contains($lower, 'appointment'))) {
            $latestPo = \App\Models\PurchaseOrder::with(['supplier', 'sparePart'])->latest()->first();
            $lowPartsCount = SparePart::whereColumn('stock', '<=', 'min_stock')->count();

            $reply = $lang === 'ms' ? "📦 **Status Pesanan Pembelian (PO) & Baki Stok**:\n\n" : "📦 **PO Restock & Inventory Status**:\n\n";

            if ($latestPo) {
                $daysAgo = Carbon::parse($latestPo->created_at)->diffForHumans();
                $reply .= "• **Latest PO**: `{$latestPo->po_number}` (" . ucfirst($latestPo->status) . ")\n"
                        . "• **Supplier**: " . ($latestPo->supplier->name ?? 'Workshop Partner Supplier') . "\n"
                        . "• **Order Date**: " . $latestPo->created_at->format('d M Y') . " ({$daysAgo})\n"
                        . "• **Total Amount**: RM " . number_format($latestPo->total_cost, 2) . "\n";

                if ($latestPo->sparePart) {
                    $reply .= "• **Item Ordered**: {$latestPo->sparePart->name} ({$latestPo->quantity} units)\n";
                }
            } else {
                $reply .= $lang === 'ms' ? "Tiada rekod pesanan pembelian (PO) direkodkan lagi.\n" : "No purchase order records recorded yet.\n";
            }

            $reply .= "\n🔍 " . ($lang === 'ms' ? "**Analisis Tambah Stok**:\n" : "**Replenishment Analysis**:\n");
            if ($lowPartsCount > 0) {
                $reply .= $lang === 'ms'
                    ? "⚠️ **Notis Bengkel**: Terdapat **{$lowPartsCount} alat ganti** di bawah paras keselamatan minimum! Disyorkan membuat PO tambah stok di tab *Purchase Orders*."
                    : "⚠️ **Workshop Notice**: There are currently **{$lowPartsCount} spare part(s)** below safety minimum threshold! We recommend issuing a new replenishment order in the *Purchase Orders* tab.";
            } else {
                $reply .= $lang === 'ms'
                    ? "✅ Semua alat ganti kini berada pada paras stok yang sihat (di atas paras minimum)."
                    : "✅ All spare parts currently maintain healthy stock levels (above safety minimums).";
            }

            return $reply;
        }

        // Check if query is looking for a specific spare part keyword (e.g. oil, minyak, brek, brake, spark, plug, filter, battery, etc.)
        $cleanedTerm = trim(preg_replace('/(ada|semak|check|cari|lihat|tengok|status|baki|stok|stock|spare\s*parts?|alat\s*ganti|tak|tinggal|berapa|banyak)/i', '', $msg));
        $cleanedTerm = trim(preg_replace('/[^\w\s\-]/u', '', $cleanedTerm));

        if (strlen($cleanedTerm) >= 3 && !in_array(strtolower($cleanedTerm), ['rendah', 'low', 'habis', 'kurang'])) {
            $matchingParts = SparePart::where('name', 'LIKE', "%{$cleanedTerm}%")
                ->orWhere('part_number', 'LIKE', "%{$cleanedTerm}%")
                ->get();

            if ($matchingParts->isNotEmpty()) {
                $invLink = ($user && in_array($user->role, ['staff', 'coordinator'])) ? '/staff/inventory' : '/admin/spare-parts';
                $reply = $lang === 'ms'
                    ? "🔩 **Carian Stok Alat Ganti Stor ('{$cleanedTerm}')**:\n\n"
                    : "🔩 **Spare Part Store Stock Search ('{$cleanedTerm}')**:\n\n";

                foreach ($matchingParts->take(5) as $p) {
                    $status = $p->stock <= 0
                        ? ($lang === 'ms' ? '🔴 HABIS STOK' : '🔴 OUT OF STOCK')
                        : ($p->stock <= $p->min_stock
                            ? ($lang === 'ms' ? "⚠️ RENDAH (Baki {$p->stock})" : "⚠️ LOW (Blnc: {$p->stock})")
                            : ($lang === 'ms' ? "🟢 ADA STOK (Baki {$p->stock})" : "🟢 IN STOCK ({$p->stock} units)"));

                    $reply .= "• **{$p->name}** [`{$p->part_number}`]\n"
                            . "  Status: **{$status}** (Min: {$p->min_stock}) &bull; Harga: RM " . number_format($p->unit_price, 2) . "\n";
                }

                $reply .= "\n👉 [Buka Pengurusan Stor Alat Ganti]({$invLink})";
                return $reply;
            }
        }

        // Default: Low stock parts report
        $lowParts = SparePart::whereColumn('stock', '<=', 'min_stock')->get();
        if ($lowParts->isEmpty()) {
            return $lang === 'ms'
                ? "✅ **Semua Stok Sihat!**\nTiada alat ganti yang berada di bawah had minimum keselamatan buat masa ini."
                : "✅ **All Stock Healthy!**\nNo spare parts are currently below their safety minimum threshold.";
        }

        $reply = $lang === 'ms'
            ? "⚠️ **Laporan Alat Ganti Stok Rendah (" . $lowParts->count() . " item)**:\n\n"
            : "⚠️ **Low Stock Report (" . $lowParts->count() . " item(s))**:\n\n";

        foreach ($lowParts->take(6) as $p) {
            $status = $p->stock <= 0
                ? ($lang === 'ms' ? '❌ HABIS STOK' : '❌ OUT OF STOCK')
                : ($lang === 'ms' ? "⚠️ Baki {$p->stock} (Min: {$p->min_stock})" : "⚠️ {$p->stock} remaining (Min: {$p->min_stock})");

            $reply .= "• **{$p->name}** [`{$p->part_number}`]\n"
                    . "  Status: {$status} &bull; Kos Seunit: RM " . number_format($p->unit_price, 2) . "\n";
        }
        if ($lowParts->count() > 6) {
            $reply .= "\n_+" . ($lowParts->count() - 6) . " " . ($lang === 'ms' ? "lagi alat ganti di bawah had minimum._\n" : "more item(s) below minimum threshold._\n");
        }

        $invLink = ($user && in_array($user->role, ['staff', 'coordinator'])) ? '/staff/inventory' : '/admin/spare-parts';
        $reply .= "\n💡 " . ($lang === 'ms'
            ? "**Tindakan Disyorkan**: Buka tab [Stor Alat Ganti]({$invLink}) atau hantar pesanan PO kepada pembekal."
            : "**Recommended Action**: Open [Spare Parts Store]({$invLink}) or dispatch a PO replenishment order.");
        return $reply;
    }

    private function isServiceEstimatorQuery(string $msg): bool {
        $lower = strtolower($msg);
        return (bool) (preg_match('/(bila|when)\s*(patut|kena|perlu|should|can|boleh)\s*(saya|i|kita)?\s*(servis|service|tukar\s*minyak|tukar\s*tayar|tukar\s*brek)/i', $lower)
            || preg_match('/(last|terakhir|lepas)\s*(servis|service|tukar)/i', $lower)
            || preg_match('/(anggaran|estimate|jadual|interval|predict|ramalan)\s*(servis|service)/i', $lower)
            || preg_match('/(semak|check)\s*(servis|jadual|minyak|oil|tayar)/i', $lower)
            || preg_match('/(oil|minyak\s*hitam|tayar|brek)\s*(tu|saya|tu\s*sekian|dah\s*sampai)/i', $lower));
    }

    private function handleServiceEstimatorQuery(string $msg, $user, string $lang): string {
        $lower = strtolower($msg);

        $mentionedKm = null;
        if (preg_match('/(\d{1,3}(?:,\d{3})+|\d{4,6})\s*(km|kilometer)?/i', $msg, $kmMatch)) {
            $mentionedKm = (int) str_replace(',', '', $kmMatch[1]);
        }

        $component = 'General Service';
        if (str_contains($lower, 'minyak') || str_contains($lower, 'oil')) {
            $component = 'Engine Oil & Filter Service';
        } elseif (str_contains($lower, 'tayar') || str_contains($lower, 'tyre') || str_contains($lower, 'tire')) {
            $component = 'Tyres & Wheel Alignment';
        } elseif (str_contains($lower, 'brek') || str_contains($lower, 'brake')) {
            $component = 'Braking System (Pads & Fluid)';
        }

        $vehicleInfo = '';
        if ($user) {
            $ids = $this->scopeUserIds($user);
            $vehicle = Vehicle::whereIn('user_id', $ids)->first();
            if ($vehicle) {
                $lastService = \App\Models\ServiceHistory::where('vehicle_id', $vehicle->id)->latest('service_date')->first();
                $lastDateStr = $lastService ? Carbon::parse($lastService->service_date)->format('d M Y') : ($lang === 'ms' ? 'rekod bengkel' : 'workshop history');
                $vehicleInfo = "🚗 **Kenderaan Berdaftar ({$vehicle->plate_number})**:\n"
                             . "• Odometer Semasa: **" . number_format($vehicle->mileage) . " km**\n"
                             . "• Servis Terakhir: **{$lastDateStr}**\n\n";
            }
        }

        if ($lang === 'ms') {
            $advice = "💡 **Garis Panduan Selang Masa Servis Piawai**:\n"
                    . "• Minyak Enjin Semi-Synthetic: Setiap **5,000 – 7,000 km** atau 3-4 bulan\n"
                    . "• Minyak Enjin Fully Synthetic: Setiap **10,000 km** atau 6 bulan\n"
                    . "• Pad & Cecair Brek: Setiap **20,000 km** atau 1 tahun\n"
                    . "• Servis Utama (Major): Setiap **40,000 km** atau 2 tahun\n";

            return "🔍 **Anggaran Selang Masa Servis ({$component})**\n\n"
                 . $vehicleInfo
                 . $advice . "\n"
                 . "📅 **Ingin Tempah Slot Servis?**\n"
                 . "Hanya balas: *'Saya nak booking servis kereta esok jam 10 pagi'* dan saya akan susun slot anda terus!";
        }

        $advice = "💡 **Standard Service Interval Guidelines**:\n"
                . "• Semi-Synthetic Engine Oil: Every **5,000 – 7,000 km** or 3-4 months\n"
                . "• Fully Synthetic Engine Oil: Every **10,000 km** or 6 months\n"
                . "• Brake Pads & Fluid: Every **20,000 km** or 1 year\n"
                . "• Major Periodic Service: Every **40,000 km** or 2 years\n";

        return "🔍 **Service Interval & Diagnostic Estimate ({$component})**\n\n"
             . $vehicleInfo
             . $advice . "\n"
             . "📅 **Ready to Book a Service Slot?**\n"
             . "Simply reply: *'Book an oil change appointment tomorrow at 10 AM'* and I will arrange your slot immediately!";
    }

    private function extractDate(string $text): Carbon {
        $lower = strtolower($text);

        if (str_contains($lower, 'esok') || str_contains($lower, 'tomorrow')) {
            return Carbon::tomorrow();
        }
        if (str_contains($lower, 'lusa')) {
            return Carbon::today()->addDays(2);
        }
        if (str_contains($lower, 'isnin') || str_contains($lower, 'monday')) {
            return Carbon::now()->next(Carbon::MONDAY);
        }
        if (str_contains($lower, 'selasa') || str_contains($lower, 'tuesday')) {
            return Carbon::now()->next(Carbon::TUESDAY);
        }
        if (str_contains($lower, 'rabu') || str_contains($lower, 'wednesday')) {
            return Carbon::now()->next(Carbon::WEDNESDAY);
        }
        if (str_contains($lower, 'khamis') || str_contains($lower, 'thursday')) {
            return Carbon::now()->next(Carbon::THURSDAY);
        }
        if (str_contains($lower, 'jumaat') || str_contains($lower, 'friday')) {
            return Carbon::now()->next(Carbon::FRIDAY);
        }
        if (str_contains($lower, 'sabtu') || str_contains($lower, 'saturday')) {
            return Carbon::now()->next(Carbon::SATURDAY);
        }

        // Pattern matching for DD/MM/YYYY or YYYY-MM-DD
        if (preg_match('/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $text, $m)) {
            return Carbon::createFromDate($m[1], $m[2], $m[3]);
        }
        if (preg_match('/(\d{1,2})[-\/](\d{1,2})(?:[-\/](\d{2,4}))?/', $text, $m)) {
            $year = !empty($m[3]) ? (strlen($m[3]) == 2 ? 2000 + $m[3] : $m[3]) : date('Y');
            return Carbon::createFromDate($year, $m[2], $m[1]);
        }

        return Carbon::tomorrow();
    }

    private function extractTime(string $text): string {
        $lower = strtolower($text);

        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm|pagi|petang|tengahari|malam)/i', $lower, $m)) {
            $hour = (int) $m[1];
            $min  = !empty($m[2]) ? (int) $m[2] : 0;
            $period = strtolower($m[3]);

            if (in_array($period, ['pm', 'petang', 'malam']) && $hour < 12) {
                $hour += 12;
            } elseif (in_array($period, ['am', 'pagi']) && $hour === 12) {
                $hour = 0;
            }
            return sprintf('%02d:%02d:00', $hour, $min);
        }

        if (preg_match('/(?:jam|pukul)\s*(\d{1,2})/i', $lower, $m)) {
            $hour = (int) $m[1];
            if ($hour <= 6) $hour += 12; // treat "jam 2" as 2:00 PM
            return sprintf('%02d:00:00', $hour);
        }

        return '10:00:00';
    }

    private function formatDateLabel(Carbon $date, string $lang): string {
        if ($lang === 'ms') {
            $daysMs = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];
            $monthsMs = [1=>'Jan', 2=>'Feb', 3=>'Mac', 4=>'Apr', 5=>'Mei', 6=>'Jun', 7=>'Jul', 8=>'Ogo', 9=>'Sep', 10=>'Okt', 11=>'Nov', 12=>'Dis'];
            return $daysMs[$date->dayOfWeek] . ', ' . $date->day . ' ' . $monthsMs[$date->month] . ' ' . $date->year;
        }
        return $date->format('l, d M Y');
    }

    // ----------------------------------------------------------------
    // Google Gemini AI Engine Integration (Strict Multi-Turn Language)
    // ----------------------------------------------------------------

    private function tryGemini(string $message, $user, string $lang, ?array $userContext = null): ?string {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-flash-lite-latest');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $vehiclesList = [];
        if ($user) {
            $ids = $this->scopeUserIds($user);
            $vehicles = Vehicle::whereIn('user_id', $ids)->get(['plate_number', 'brand', 'model', 'mileage']);
            $vehiclesList = $vehicles->map(fn($v) => "{$v->plate_number} ({$v->brand} {$v->model}, " . number_format($v->mileage) . " km)")->toArray();
        }

        $pricingList = [];
        try {
            $jobTypes = JobType::all(['name', 'base_price', 'category']);
            $pricingList = $jobTypes->map(fn($j) => "• {$j->name} ({$j->category}): from RM " . number_format($j->base_price, 2))->toArray();
        } catch (\Throwable $e) {}

        $langInstruction = $lang === 'ms'
            ? "CRITICAL LANGUAGE REQUIREMENT: The user is speaking in Bahasa Melayu (Malay). You MUST answer 100% in natural, polite, respectful, and courteous Bahasa Melayu throughout your entire response. Under NO circumstances should you revert to English, except for standard Malaysian automotive component terms (e.g., brake pad, rotor, absorber, alternator, engine oil)."
            : "CRITICAL LANGUAGE REQUIREMENT: The user is speaking in English. Reply politely and professionally in English.";

        $ctx = $userContext ?: $this->getUserContext($user);
        $companyLine = !empty($ctx['company']) ? "User Company / Fleet Account: {$ctx['company']}.\n" : "";

        $systemPrompt = "You are the expert automotive service copilot and operations assistant for Teraju Setia Enterprise, a certified commercial vehicle repair and corporate fleet maintenance workshop located in Kluang, Johor, Malaysia.
Current Date: " . now()->format('l, d M Y') . ".
{$langInstruction}

CRITICAL CONVERSATIONAL & GREETING RULES:
1. DO NOT repeat greetings, welcomes, or introductions ('Welcome to Teraju Setia Enterprise', 'Hello!', 'Salam!') if the conversation has already started. Answer directly, naturally, and professionally.
2. DO NOT provide English translation brackets like '(Translation: ...)' when speaking in Malay or vice-versa. Respond purely and naturally in the requested language ({$lang}).
3. PERSISTENCE & MEMORY: Always maintain conversational memory. If the user previously mentioned car symptoms (e.g. car feels weird, strange noise) or agreed to a recommendation (e.g. 'sure lets do that'), seamlessly carry forward that context and help them proceed with the booking or diagnostic inspection.

USER ROLE & IDENTITY AWARENESS:
• User Name: {$ctx['name']}
• Role: {$ctx['role']} ({$ctx['title']})
{$companyLine}• Authorization & Scope: {$ctx['description']}
• Registered Vehicles: " . (count($vehiclesList) ? implode(', ', $vehiclesList) : 'None registered') . "

Workshop Services & Base Labour Rates:
" . (count($pricingList) ? implode("\n", $pricingList) : 'Available upon workshop inspection') . "
Workshop Operating Hours: Sunday - Thursday 8:30 AM - 5:30 PM, Friday 8:30 AM - 12:30 PM & 2:30 PM - 5:30 PM (Friday prayer break: 12:30 PM - 2:30 PM), Saturday CLOSED.
Workshop Address: No. 1, Jalan Malinja 2, Taman Perindustrian Matahari, 86000 Kluang, Johor Darul Ta'zim. Phone: 07-787 8728.

ROLE-SPECIFIC ASSISTANCE GUIDELINES:
1. For STAFF / MECHANICS:
   - Act as their senior workshop engineer and technical assistant.
   - Assist them with active Job Cards ('had job left?'), technical repair questions, torque specs, engine timing, wiring diagrams, and checking spare parts store stock.
2. For ADMIN & COORDINATOR:
   - Act as their workshop operations copilot.
   - Answer queries on today's appointments schedule, bay capacity utilization, mechanic job cards, revenue, and parts replenishment POs.
3. For CORPORATE FLEET MANAGERS:
   - Address their commercial company fleet ({$ctx['company']}).
   - Assist with commercial trucks, vans, and pickups; periodic maintenance schedules; and corporate billing invoices.
4. For INDIVIDUAL CUSTOMERS & GUESTS:
   - Be helpful and courteous without repetitive welcoming. Explain mechanical symptoms in clear layman terms.
   - For vehicle booking, encourage them to click the [📅 Book Service Slot / Tempah Slot] button or state their vehicle details.

CRITICAL APPOINTMENT BOOKING CONSTRAINTS (NO HALLUCINATIONS):
1. You do NOT have database write access and CANNOT create, book, modify, or insert appointments into the database.
2. Therefore, you MUST NEVER tell the user that their appointment has been booked, confirmed, scheduled, or finalized (e.g. NEVER output phrases like \'temujanji anda telah ditetapkan\', \'slot anda telah ditempah\', \'temujanji telah disahkan\', \'your appointment has been booked\', or \'your appointment is scheduled\').
3. If a user wants to book, tell them to click the [📅 Book Service Slot / Tempah Slot] button or provide their vehicle make, model and plate number so our automated booking engine can register their slot directly into the workshop schedule.

Tone: Professional, helpful, concise, and structured. Avoid walls of text.";

        // Build multi-turn conversational contents
        $contents = [];
        $history = session('chatbot_history', []);
        $pastTurns = array_slice($history, -6);
        foreach ($pastTurns as $h) {
            if (!empty($h['text']) && ($h['role'] !== 'user' || $h['text'] !== $message)) {
                $contents[] = [
                    'role'  => $h['role'] === 'user' ? 'user' : 'model',
                    'parts' => [['text' => $h['text']]],
                ];
            }
        }
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $message]],
        ];

        try {
            $response = Http::timeout(12)->post($endpoint, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature'     => 0.3,
                    'maxOutputTokens' => 450,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!empty($reply)) {
                    $reply = trim($reply);
                    $h = session('chatbot_history', []);
                    $h[] = ['role' => 'model', 'text' => $reply];
                    if (count($h) > 10) $h = array_slice($h, -10);
                    session(['chatbot_history' => $h]);
                    return $reply;
                }
            } else {
                Log::warning("Gemini API call failed [HTTP {$response->status()}]: {$response->body()}");

                if ($response->status() === 404 && $model !== 'gemini-flash-latest') {
                    $fbEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}";
                    $fbResp = Http::timeout(12)->post($fbEndpoint, [
                        'system_instruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature'     => 0.3,
                            'maxOutputTokens' => 450,
                        ]
                    ]);
                    if ($fbResp->successful()) {
                        $data = $fbResp->json();
                        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        if (!empty($reply)) {
                            $reply = trim($reply);
                            $h = session('chatbot_history', []);
                            $h[] = ['role' => 'model', 'text' => $reply];
                            if (count($h) > 10) $h = array_slice($h, -10);
                            session(['chatbot_history' => $h]);
                            return $reply;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini API call exception: ' . $e->getMessage());
        }

        return null;
    }

    public function geminiStatus() {
        return response()->json(self::checkConnection());
    }

    public static function checkConnection(?string $keyOverride = null): array {
        $apiKey = $keyOverride ?: config('services.gemini.api_key');
        if (empty($apiKey)) {
            return [
                'connected' => false,
                'status'    => 'missing_key',
                'error'     => 'GEMINI_API_KEY is not configured in your .env file.',
                'hint'      => 'Get a free API key at https://aistudio.google.com/app/apikey and add GEMINI_API_KEY=your_key in .env',
            ];
        }

        $model = config('services.gemini.model', 'gemini-flash-lite-latest');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(12)->post($endpoint, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => 'Hello! Test connection. Please reply with exactly: "TERAJU_GEMINI_ONLINE"']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.1,
                    'maxOutputTokens' => 30,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                return [
                    'connected' => true,
                    'status'    => 'connected',
                    'model'     => $model,
                    'reply'     => trim($reply),
                ];
            }

            if ($response->status() === 404 && $model !== 'gemini-flash-latest') {
                $fbEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}";
                $fbResp = Http::timeout(12)->post($fbEndpoint, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => 'Hello! Test connection. Please reply with exactly: "TERAJU_GEMINI_ONLINE"']
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.1,
                        'maxOutputTokens' => 30,
                    ]
                ]);

                if ($fbResp->successful()) {
                    $data = $fbResp->json();
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    return [
                        'connected' => true,
                        'status'    => 'connected',
                        'model'     => 'gemini-flash-latest',
                        'reply'     => trim($reply),
                        'note'      => 'Connected via gemini-flash-latest fallback.',
                    ];
                }
            }

            $errBody = $response->json();
            $msg = $errBody['error']['message'] ?? $response->body();
            return [
                'connected' => false,
                'status'    => 'api_error',
                'http_code' => $response->status(),
                'error'     => $msg,
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'status'    => 'network_error',
                'error'     => $e->getMessage(),
            ];
        }
    }
}
