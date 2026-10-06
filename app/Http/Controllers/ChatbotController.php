<?php
namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\JobType;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Rule-based assistant with light NLU:
 *   normalise -> synonyms (EN/BM) -> tokens -> weighted intent scoring
 *   + fuzzy entity matching against live job_types / spare_parts
 *   + session memory for follow-ups.
 * Still not a language model: it only understands the intents and
 * catalogue entities below, and logs everything else for review.
 */
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
        $request->validate(['message' => 'required|string|max:500']);

        $message  = trim($request->message);
        $user     = auth()->user();
        $response = $this->buildResponse($message, $user);

        return response()->json(['reply' => $response]);
    }

    // ----------------------------------------------------------------
    // Core
    // ----------------------------------------------------------------

    private function buildResponse(string $raw, $user): string {
        $norm   = $this->normalise($raw);
        $tokens = $this->tokens($norm);

        if (empty($tokens)) {
            return $this->fallback($user);
        }

        // 1. Exact part number / barcode (e.g. "CO-5W30-4L")
        if ($part = $this->partByNumber($raw)) {
            $this->remember('part', $part->id);
            return $this->partReply($part);
        }

        $intents = $this->scoreIntents($tokens);
        $top     = $intents ? array_key_first($intents) : null;
        $topScore = $top ? $intents[$top] : 0;

        // 2. Short social intents
        if (in_array($top, ['greeting', 'thanks', 'bye'], true) && count($tokens) <= 5 && $topScore >= 2) {
            return $this->socialReply($top, $user);
        }

        // 3. Account/workshop intents win over catalogue lookups
        if (in_array($top, ['hours', 'location', 'appointments', 'vehicles', 'invoice'], true) && $topScore >= 2) {
            return match ($top) {
                'hours'        => $this->hoursReply(),
                'location'     => $this->locationReply(),
                'appointments' => $this->appointmentsReply($user),
                'vehicles'     => $this->vehiclesReply($user),
                'invoice'      => $this->invoiceReply($user),
            };
        }

        // 4. Catalogue entity (job type or spare part)
        $entity = $this->findEntity($tokens, $intents);
        if ($entity) {
            $this->remember($entity['type'], $entity['model']->id);
            return $this->entityReply($entity);
        }

        // 5. Generic catalogue questions
        if ($top === 'services' && $topScore >= 2) {
            return $this->servicesReply();
        }
        if (in_array($top, ['price', 'stock'], true)) {
            // Follow-up using the last thing discussed ("and the stock?")
            if ($ctx = $this->recall()) {
                return $ctx['type'] === 'part'
                    ? $this->partReply($ctx['model'])
                    : $this->jobTypeReply($ctx['model']);
            }
            return $this->hasWord($tokens, ['service','labour','servis']) || $this->mentionsService($norm)
                ? $this->servicesReply(true)
                : $this->partsListReply();
        }
        if (in_array($top, ['greeting', 'thanks', 'bye'], true)) {
            return $this->socialReply($top, $user);
        }

        // 6. Nothing understood: log it so coverage can be measured
        Log::info('chatbot.unmatched', ['q' => $raw, 'user' => $user->id ?? null]);
        return $this->fallback($user);
    }

    // ----------------------------------------------------------------
    // NLU helpers
    // ----------------------------------------------------------------

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
        if ($len < 5) return false;               // short words must match exactly
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

    /** @return array<string,int> intents sorted by score desc (score > 0 only) */
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

    /** Cosine-style overlap between query tokens and an entity's tokens. */
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

    /**
     * Best-matching job type or spare part for the query, or null.
     * Cue words ("stock", "part" vs "service", "labour") bias the choice.
     */
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
        foreach (SparePart::all() as $p) {
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

    // ----------------------------------------------------------------
    // Session memory
    // ----------------------------------------------------------------

    private function remember(string $type, int $id): void {
        session(['chatbot_last' => ['type' => $type === 'job' ? 'job' : 'part', 'id' => $id]]);
    }

    private function recall(): ?array {
        $last = session('chatbot_last');
        if (!$last) return null;
        $model = $last['type'] === 'part' ? SparePart::find($last['id']) : JobType::find($last['id']);
        return $model ? ['type' => $last['type'], 'model' => $model] : null;
    }

    // ----------------------------------------------------------------
    // Replies
    // ----------------------------------------------------------------

    private function entityReply(array $entity): string {
        $reply = $entity['type'] === 'part'
            ? $this->partReply($entity['model'])
            : $this->jobTypeReply($entity['model']);

        if (!empty($entity['related'])) {
            $names = array_map(fn($c) => $c['model']->name, $entity['related']);
            $reply .= "\n\nDid you mean: " . implode(', ', $names) . '?';
        }
        return $reply;
    }

    private function jobTypeReply(JobType $jt): string {
        $price = $jt->base_price > 0 ? 'RM ' . number_format($jt->base_price, 2) : 'Contact us for a quote';
        $reply = "🔧 {$jt->name}\n"
               . "Category: {$jt->category}\n"
               . "Labour charge: {$price}\n"
               . "Estimated duration: ~{$jt->estimated_time}\n";

        if ($jt->isIntervalTracked()) {
            $reply .= "Recommended interval: {$jt->interval_label}\n";
        }

        return $reply . "\nLabour is billed separately from parts. Parts are charged by actual usage on your job card.\n"
             . "To book, use the Appointments section in your dashboard.";
    }

    private function partReply(SparePart $part): string {
        if ($part->stock <= 0)                 $note = '❌ Out of stock';
        elseif ($part->stock <= $part->min_stock) $note = "⚠ Low stock — only {$part->stock} unit(s) left";
        else                                   $note = "✅ In stock — {$part->stock} units available";

        return "🔩 {$part->name}\n"
             . ($part->brand ? "Brand: {$part->brand}\n" : '')
             . "Part No: {$part->part_number}\n"
             . "Category: {$part->category}\n"
             . 'Unit Price: RM ' . number_format($part->unit_price, 2) . "\n"
             . "Availability: {$note}\n\n"
             . 'Final billing is based on the parts used in your job card.';
    }

    private function servicesReply(bool $withPrices = false): string {
        $jobTypes = JobType::orderBy('category')->orderBy('name')->get();
        if ($jobTypes->isEmpty()) {
            return 'We offer a full range of vehicle maintenance and repair services. Please contact us for details.';
        }

        $reply = "🔧 Our Services:\n\n";
        foreach ($jobTypes->groupBy('category') as $cat => $types) {
            $reply .= "{$cat}\n";
            foreach ($types as $t) {
                $reply .= "• {$t->name} (~{$t->estimated_time})";
                if ($withPrices && $t->base_price > 0) {
                    $reply .= ' — RM ' . number_format($t->base_price, 2);
                }
                $reply .= "\n";
            }
            $reply .= "\n";
        }
        return $reply . ($withPrices ? 'Labour only; parts are extra.' : "Ask me about a specific service for its price.");
    }

    private function partsListReply(): string {
        $grouped = SparePart::orderBy('category')->orderBy('name')->get()->groupBy('category');
        $reply   = "🔩 Current Spare Parts & Prices:\n\n";

        foreach ($grouped as $cat => $items) {
            $reply .= "{$cat}\n";
            foreach ($items as $p) {
                $avail = $p->stock <= 0 ? '❌ Out of stock'
                       : ($p->stock <= $p->min_stock ? "⚠ Low ({$p->stock} left)" : "✅ {$p->stock} in stock");
                $reply .= "• {$p->name}" . ($p->brand ? " ({$p->brand})" : '')
                        . ' — RM ' . number_format($p->unit_price, 2) . " · {$avail}\n";
            }
            $reply .= "\n";
        }
        return $reply . 'Ask me the name of any part for full details.';
    }

    private function socialReply(string $intent, $user): string {
        return match ($intent) {
            'greeting' => ($user ? "Hi {$user->name}!" : 'Hi there!')
                . " Welcome to Teraju Setia Enterprise. How can I help you today?\n\nYou can ask me about:\n"
                . "• Service prices and labour charges\n• Spare part prices and stock\n"
                . ($user ? "• Your appointments, vehicles and invoices\n" : '')
                . "• Workshop hours and location",
            'thanks'   => "You're welcome! 😊 Is there anything else I can help you with?",
            'bye'      => 'Goodbye! Drive safe. Feel free to chat anytime. 👋',
        };
    }

    private function hoursReply(): string {
        return "🕐 Our workshop hours:\n\nMonday – Friday: 8:00 AM – 6:00 PM\nSaturday: 8:00 AM – 2:00 PM\nSunday & Public Holiday: Closed\n\nFor urgent fleet matters, call 07-5551234.";
    }

    private function locationReply(): string {
        return "📍 Teraju Setia Enterprise\nNo 14, Jalan Perindustrian 7,\nKawasan Perindustrian Senai,\n81400 Senai, Johor.\n\nNear Senai Industrial Area, with easy access from the main highway.";
    }

    private function appointmentsReply($user): string {
        if (!$user) {
            return "To view or make appointments, please log in or register first.\n\nAs a guest you can ask me about services and part prices.";
        }

        $appointments = Appointment::whereIn('user_id', $this->scopeUserIds($user))
            ->with('vehicle')
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest()->take(3)->get();

        if ($appointments->isEmpty()) {
            return "You have no upcoming appointments.\n\nYou can book one through the Appointments section in your dashboard.";
        }

        $reply = "📅 Your upcoming appointments:\n\n";
        foreach ($appointments as $apt) {
            $reply .= "• {$apt->service_type}\n"
                    . '  Vehicle: ' . ($apt->vehicle->plate_number ?? '—') . "\n"
                    . '  Date: ' . $apt->date->format('d M Y') . " at {$apt->time}\n"
                    . '  Status: ' . ucfirst($apt->status) . "\n\n";
        }
        return $reply;
    }

    private function vehiclesReply($user): string {
        if (!$user) return 'To view your vehicle details, please log in first.';

        $vehicles = Vehicle::whereIn('user_id', $this->scopeUserIds($user))->orderBy('plate_number')->get();
        if ($vehicles->isEmpty()) {
            return 'You have no registered vehicles yet. You can add one through the Vehicles section.';
        }

        $reply = "🚗 Your registered vehicles:\n\n";
        foreach ($vehicles as $v) {
            $reply .= "• {$v->plate_number} — {$v->brand} {$v->model} ({$v->year})\n"
                    . '  Mileage: ' . number_format($v->mileage) . " km\n\n";
        }
        return $reply;
    }

    private function invoiceReply($user): string {
        if (!$user) return 'To check invoices or balances, please log in first.';

        $ids      = $this->scopeUserIds($user);
        $invoices = Invoice::whereHas('jobCard.appointment', fn($q) => $q->whereIn('user_id', $ids))
            ->whereIn('status', ['unpaid', 'partial'])
            ->latest()->take(5)->get();

        if ($invoices->isEmpty()) {
            return '✅ You have no outstanding invoices.';
        }

        $reply = "🧾 Outstanding invoices:\n\n";
        foreach ($invoices as $inv) {
            $reply .= "• {$inv->invoice_number} — balance RM " . number_format($inv->balance, 2)
                    . ' (' . ucfirst($inv->status) . ")\n";
        }
        $reply .= "\nTotal due: RM " . number_format($invoices->sum(fn($i) => $i->balance), 2);
        return $reply;
    }

    private function fallback($user): string {
        return "I'm not sure about that. Here's what I can help with:\n\n"
             . "• Service prices — try 'How much is an oil change?'\n"
             . "• Part prices and stock — try 'Price of brake pad' or scan/type a part number\n"
             . "• Our services — 'What services do you offer?'\n"
             . "• Workshop hours and location\n"
             . ($user ? "• Your appointments, vehicles and invoices\n" : '')
             . "\nFor anything else, please call 07-5551234.";
    }

    /** User ids whose records this user may see (whole company for corporate PICs). */
    private function scopeUserIds($user): array {
        if ($user->role === 'corporate' && $user->company_id) {
            return User::where('company_id', $user->company_id)->pluck('id')->all();
        }
        return [$user->id];
    }
}