<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ChatbotBookingTest extends TestCase
{
    use DatabaseTransactions;
    public function test_guest_can_complete_multi_turn_booking_flow_and_persist_to_database()
    {
        // Turn 1: user initiates booking
        $res1 = $this->postJson('/chatbot/reply', ['message' => 'nak book lah']);
        $res1->assertStatus(200);

        // Turn 2: specify walkin
        $res2 = $this->postJson('/chatbot/reply', ['message' => 'boleh lah saya book walkin']);
        $res2->assertStatus(200);

        // Turn 3: car model
        $res3 = $this->postJson('/chatbot/reply', ['message' => 'Serena']);
        $res3->assertStatus(200);

        // Turn 4: date
        $res4 = $this->postJson('/chatbot/reply', ['message' => 'esok']);
        $res4->assertStatus(200);

        // Turn 5: symptom
        $res5 = $this->postJson('/chatbot/reply', ['message' => 'kereta rasa senguk senguk']);
        $res5->assertStatus(200);

        // Turn 6: time
        $res6 = $this->postJson('/chatbot/reply', ['message' => '9 pagi']);
        $res6->assertStatus(200);

        // Turn 7: plate and contact details
        $res7 = $this->postJson('/chatbot/reply', ['message' => 'JQR 1234, Ahmad, 012-3456789']);
        $res7->assertStatus(200);
        $res7->assertJson([
            'action' => 'confirm_booking',
        ]);

        // Turn 8: confirm booking
        $res8 = $this->postJson('/chatbot/reply', ['message' => 'ok']);
        $res8->assertStatus(200);
        $res8->assertJson([
            'booking_confirmed' => true,
        ]);

        // Verify record in database
        $this->assertDatabaseHas('vehicles', [
            'plate_number' => 'JQR 1234',
            'brand'        => 'Nissan',
            'model'        => 'Serena',
        ]);

        $this->assertDatabaseHas('appointments', [
            'walkin_name'  => 'Ahmad',
            'is_walkin'    => true,
            'service_type' => 'Engine Diagnostics & Troubleshooting',
        ]);

        // Verify visible to admin
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $this->actingAs($admin)
                ->get('/admin/appointments')
                ->assertStatus(200)
                ->assertSee('JQR 1234')
                ->assertSee('Ahmad');
        }
    }

    public function test_guest_can_confirm_via_button_action()
    {
        // 1-shot intake
        $this->postJson('/chatbot/reply', [
            'message' => 'Saya nak servis kereta Nissan Serena JQR 5678 esok jam 9 pagi sebab senguk senguk. Nama Siti 019-8765432'
        ])->assertStatus(200)->assertJson(['action' => 'confirm_booking']);

        // Confirm via action button
        $res = $this->postJson('/chatbot/reply', [
            'action'  => 'confirm_booking',
            'message' => 'Sahkan tempahan slot'
        ]);

        $res->assertStatus(200);
        $res->assertJson(['booking_confirmed' => true]);

        $this->assertDatabaseHas('appointments', [
            'walkin_name'  => 'Siti',
            'service_type' => 'Engine Diagnostics & Troubleshooting',
        ]);
    }

    public function test_guest_can_cancel_booking_intake()
    {
        $this->postJson('/chatbot/reply', ['message' => 'nak book']);
        $res = $this->postJson('/chatbot/reply', ['message' => 'batal']);
        $res->assertStatus(200);
        $this->assertFalse(session()->has('chatbot_intake'));
        $this->assertFalse(session()->has('pending_chatbot_booking'));
    }

    public function test_consultative_troubleshooting_does_not_assume_booking_and_diagnoses_battery_not_ac()
    {
        session()->flush();

        // 1. User asks a troubleshooting question
        $res1 = $this->postJson('/chatbot/reply', [
            'message' => 'tolong kereta tak boleh start macam mana ye'
        ]);
        $res1->assertStatus(200);
        // Must NOT assume booking right away
        $this->assertFalse(session()->has('chatbot_intake'));
        $this->assertFalse(session()->has('pending_chatbot_booking'));
        $this->assertStringNotContainsString('sila berikan Nombor Plat Pendaftaran', $res1->json('reply'));

        // 2. User then proceeds with vehicle details
        $res2 = $this->postJson('/chatbot/reply', [
            'message' => 'hondahrv, jqc8888'
        ]);
        $res2->assertStatus(200);
        $res2->assertSessionHas('chatbot_intake');

        // 3. User provides contact
        $this->postJson('/chatbot/reply', ['message' => '0137556551']);
        $this->postJson('/chatbot/reply', ['message' => 'Ramli']);

        // 4. User picks slot
        $res5 = $this->postJson('/chatbot/reply', [
            'action'  => 'pick_slot',
            'date'    => '2026-10-25',
            'time'    => '09:30:00',
            'message' => 'Saya pilih tarikh 25/Oct/2026 jam 9:30 AM',
        ]);
        $res5->assertStatus(200);
        $res5->assertJson(['action' => 'confirm_booking']);

        // Verify it diagnosed as Battery & Electrical, NEVER Air Conditioning!
        $this->assertStringContainsString('Battery & Electrical Inspection', $res5->json('reply'));
        $this->assertStringNotContainsString('Air Conditioning', $res5->json('reply'));

        // 5. Confirm booking
        $res6 = $this->postJson('/chatbot/reply', ['message' => 'ok']);
        $res6->assertStatus(200);
        $res6->assertJson(['booking_confirmed' => true]);

        $this->assertDatabaseHas('appointments', [
            'walkin_name'  => 'Ramli',
            'service_type' => 'Battery & Electrical Inspection',
        ]);
    }
}
