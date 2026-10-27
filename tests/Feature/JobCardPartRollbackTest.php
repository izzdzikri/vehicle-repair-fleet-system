<?php

namespace Tests\Feature;

use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\SparePart;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JobCardPartRollbackTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_add_and_remove_part_with_automatic_stock_restoration(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $jobCard = JobCard::where('current_stage', '!=', 'completed')->first();
        $this->assertNotNull($jobCard);

        $part = SparePart::where('stock', '>', 5)->first();
        $this->assertNotNull($part);

        $initialStock = $part->stock;
        $initialTotalCost = (float) $jobCard->total_cost;

        // 1. Add 2 units of part
        $qty = 2;
        $response = $this->post("/admin/job-cards/{$jobCard->id}/parts", [
            'spare_part_id' => $part->id,
            'quantity'      => $qty,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals($initialStock - $qty, $part->fresh()->stock);

        $jobCardPart = JobCardPart::where('job_card_id', $jobCard->id)
            ->where('spare_part_id', $part->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($jobCardPart);

        // 2. Remove the part and verify stock is restored and total cost recalculated
        $deleteResponse = $this->delete("/admin/job-cards/{$jobCard->id}/parts/{$jobCardPart->id}");
        $deleteResponse->assertSessionHas('success');

        // Stock restored back to initial level
        $this->assertEquals($initialStock, $part->fresh()->stock);

        // JobCardPart record removed
        $this->assertNull(JobCardPart::find($jobCardPart->id));

        // JobCard total cost updated
        $this->assertEquals($initialTotalCost, (float) $jobCard->fresh()->total_cost);
    }

    public function test_unauthorized_user_cannot_remove_part_from_job_card(): void
    {
        $individual = User::where('role', 'individual')->first();
        $this->actingAs($individual);

        $jobCardPart = JobCardPart::first();
        if ($jobCardPart) {
            $response = $this->delete("/admin/job-cards/{$jobCardPart->job_card_id}/parts/{$jobCardPart->id}");
            $response->assertStatus(403);
        }
    }
}
