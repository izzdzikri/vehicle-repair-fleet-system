<?php
namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\JobType;
use App\Models\ChatSession;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function reply(Request $request) {
        $request->validate(['message' => 'required|string|max:500']);

        $userMessage = trim($request->message);
        $user        = auth()->user();
        $lower       = strtolower($userMessage);
        $response    = $this->buildResponse($lower, $user);

        return response()->json(['reply' => $response]);
    }

    private function buildResponse(string $msg, $user): string {
        // ---- Greetings ----
        if (preg_match('/^(hi|hello|hey|salam|assalam|selamat|good morning|good afternoon|good evening)/', $msg)) {
            $name = $user ? "Hi {$user->name}!" : "Hi there!";
            return "{$name} Welcome to Teraju Setia Enterprise. How can I help you today?\n\n"
                 . "You can ask me about:\n"
                 . "• Service prices and labour charges\n"
                 . "• Spare part prices\n"
                 . ($user ? "• Your appointments and vehicles\n" : "")
                 . "• Our services\n"
                 . "• Workshop hours and location";
        }

        // ---- Workshop info ----
        if (str_contains($msg, 'hour') || str_contains($msg, 'open') || str_contains($msg, 'masa') || str_contains($msg, 'operation')) {
            return "🕐 Our workshop hours:\n\nMonday – Friday: 8:00 AM – 6:00 PM\nSaturday: 8:00 AM – 2:00 PM\nSunday & Public Holiday: Closed\n\nFor urgent fleet matters, contact us at 07-5551234.";
        }

        if (str_contains($msg, 'location') || str_contains($msg, 'address') || str_contains($msg, 'where') || str_contains($msg, 'mana')) {
            return "📍 Teraju Setia Enterprise\nNo 14, Jalan Perindustrian 7,\nKawasan Perindustrian Senai,\n81400 Senai, Johor.\n\nWe are located near Senai Industrial Area, easy access from the main highway.";
        }

        // ---- Services list ----
        if (str_contains($msg, 'service') || str_contains($msg, 'what do you') || str_contains($msg, 'offer') || str_contains($msg, 'servis')) {
            $jobTypes = JobType::orderBy('category')->get();
            if ($jobTypes->isEmpty()) {
                return "We offer a full range of vehicle maintenance and repair services. Please contact us for details.";
            }
            $grouped = $jobTypes->groupBy('category');
            $reply   = "🔧 Our Services:\n\n";
            foreach ($grouped as $cat => $types) {
                $reply .= "**{$cat}**\n";
                foreach ($types as $t) {
                    $mins   = $t->estimated_minutes;
                    $hours  = $mins >= 60 ? floor($mins / 60) . 'h ' . ($mins % 60 ? ($mins % 60) . 'min' : '') : "{$mins} min";
                    $reply .= "• {$t->name} (~{$hours})\n";
                }
                $reply .= "\n";
            }
            $reply .= "For pricing, ask me about specific services or parts.";
            return $reply;
        }

        // ---- Labour price queries (live from job_types table) ----
        $jobTypes = \App\Models\JobType::orderBy('category')->get();

        foreach ($jobTypes as $jt) {
            $keywords = array_filter([
                strtolower($jt->name),
                strtolower($jt->category),
            ]);
            foreach ($keywords as $keyword) {
                // match any 4+ char word from the job type name against the message
                foreach (explode(' ', $keyword) as $word) {
                    if (strlen($word) >= 4 && str_contains($msg, $word)) {
                        $price = $jt->base_price > 0
                            ? 'RM ' . number_format($jt->base_price, 2)
                            : 'Contact us for quote';
                        $hours = $jt->estimated_minutes >= 60
                            ? floor($jt->estimated_minutes / 60) . 'h '
                            . ($jt->estimated_minutes % 60 ? ($jt->estimated_minutes % 60) . 'min' : '')
                            : $jt->estimated_minutes . ' min';
                        return "🔧 **{$jt->name}**\n"
                            . "Category: {$jt->category}\n"
                            . "Labour charge: **{$price}**\n"
                            . "Estimated duration: ~{$hours}\n\n"
                            . "Note: Labour charge is separate from parts. Parts used will be billed based on actual stock used.\n\n"
                            . "Want to book? Use the Appointments section in your dashboard.";
                    }
                }
            }
        }

        // ---- Part price queries (live from spare_parts table) ----
        if (str_contains($msg, 'part') || str_contains($msg, 'spare') || str_contains($msg, 'price')
        || str_contains($msg, 'harga') || str_contains($msg, 'cost') || str_contains($msg, 'how much')
        || str_contains($msg, 'stock') || str_contains($msg, 'available') || str_contains($msg, 'ada')) {

            // Try to match a specific part by keyword
            $parts = SparePart::orderBy('category')->get();
            foreach ($parts as $part) {
                $searchable = strtolower($part->name . ' ' . $part->brand . ' ' . $part->part_number . ' ' . $part->category);
                foreach (explode(' ', $searchable) as $word) {
                    if (strlen($word) >= 4 && str_contains($msg, $word)) {
                        if ($part->stock <= 0) {
                            $stockNote = "❌ Out of stock";
                        } elseif ($part->stock <= $part->min_stock) {
                            $stockNote = "⚠ Low stock — only {$part->stock} unit(s) left";
                        } else {
                            $stockNote = "✅ In stock — {$part->stock} units available";
                        }

                        return "🔩 **{$part->name}**\n"
                            . ($part->brand      ? "Brand: {$part->brand}\n"           : "")
                            . "Part No: {$part->part_number}\n"
                            . "Category: {$part->category}\n"
                            . "Unit Price: **RM " . number_format($part->unit_price, 2) . "**\n"
                            . "Availability: {$stockNote}\n\n"
                            . "This price reflects current stock. Final billing is based on parts used in your job card.";
                    }
                }
            }

            // No specific match — show full live inventory list grouped by category
            $allParts = SparePart::orderBy('category')->orderBy('name')->get();
            $grouped  = $allParts->groupBy('category');
            $reply    = "🔩 Current Spare Parts & Prices:\n\n";
            foreach ($grouped as $cat => $items) {
                $reply .= "**{$cat}**\n";
                foreach ($items as $p) {
                    if ($p->stock <= 0) {
                        $avail = "❌ Out of stock";
                    } elseif ($p->stock <= $p->min_stock) {
                        $avail = "⚠ Low ({$p->stock} left)";
                    } else {
                        $avail = "✅ {$p->stock} in stock";
                    }
                    $reply .= "• {$p->name}";
                    if ($p->brand) $reply .= " ({$p->brand})";
                    $reply .= " — RM " . number_format($p->unit_price, 2) . " · {$avail}\n";
                }
                $reply .= "\n";
            }
            $reply .= "Ask me the name of any part for full details.";
            return $reply;
        }

        // ---- Appointment queries (logged-in users only) ----
        if (str_contains($msg, 'appointment') || str_contains($msg, 'booking') || str_contains($msg, 'book') || str_contains($msg, 'tempahan')) {
            if (!$user) {
                return "To view or make appointments, please log in or register first.\n\nAs a guest you can ask me about our services and part prices.";
            }
            $appointments = Appointment::where('user_id', $user->id)
                ->with('vehicle')
                ->whereIn('status', ['pending', 'confirmed'])
                ->latest()
                ->take(3)
                ->get();

            if ($appointments->isEmpty()) {
                return "You have no upcoming appointments.\n\nYou can book one through the Appointments section in your dashboard.";
            }

            $reply = "📅 Your upcoming appointments:\n\n";
            foreach ($appointments as $apt) {
                $reply .= "• {$apt->service_type}\n";
                $reply .= "  Vehicle: " . ($apt->vehicle->plate_number ?? '—') . "\n";
                $reply .= "  Date: " . \Carbon\Carbon::parse($apt->date)->format('d M Y') . " at {$apt->time}\n";
                $reply .= "  Status: " . ucfirst($apt->status) . "\n\n";
            }
            return $reply;
        }

        // ---- Vehicle queries (logged-in users) ----
        if (str_contains($msg, 'vehicle') || str_contains($msg, 'car') || str_contains($msg, 'kereta') || str_contains($msg, 'my vehicle')) {
            if (!$user) {
                return "To view your vehicle details, please log in first.";
            }
            $vehicles = Vehicle::where('user_id', $user->id)->get();
            if ($vehicles->isEmpty()) {
                return "You have no registered vehicles yet. You can add one through the Vehicles section.";
            }
            $reply = "🚗 Your registered vehicles:\n\n";
            foreach ($vehicles as $v) {
                $reply .= "• {$v->plate_number} — {$v->brand} {$v->model} ({$v->year})\n";
                $reply .= "  Mileage: " . number_format($v->mileage) . " km\n\n";
            }
            return $reply;
        }

        // ---- Thank you ----
        if (str_contains($msg, 'thank') || str_contains($msg, 'terima kasih') || str_contains($msg, 'tq')) {
            return "You're welcome! 😊 Is there anything else I can help you with?";
        }

        // ---- Bye ----
        if (str_contains($msg, 'bye') || str_contains($msg, 'goodbye') || str_contains($msg, 'selamat tinggal')) {
            return "Goodbye! Drive safe. Feel free to chat anytime. 👋";
        }

        // ---- Fallback ----
        return "I'm not sure about that. Here's what I can help with:\n\n"
             . "• **Service prices** — ask 'How much is an oil change?'\n"
             . "• **Part prices** — ask 'Price of brake pad' or 'How much is engine oil?'\n"
             . "• **Our services** — ask 'What services do you offer?'\n"
             . "• **Workshop hours** — ask 'What are your opening hours?'\n"
             . ($user ? "• **Your appointments** — ask 'Show my appointments'\n"
                      . "• **Your vehicles** — ask 'Show my vehicles'\n" : "")
             . "\nFor complex enquiries, please call us at **07-5551234**.";
    }
}