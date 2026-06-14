<?php

namespace Tests\Unit;

use App\Enums\PsbStatus;
use App\Enums\OltType;
use App\Enums\CoverageStatus;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_psb_status_can_transition_valid_path(): void
    {
        $this->assertTrue(PsbStatus::Draft->canTransitionTo(PsbStatus::Submitted));
        $this->assertTrue(PsbStatus::Submitted->canTransitionTo(PsbStatus::CoverageOk));
        $this->assertTrue(PsbStatus::CoverageOk->canTransitionTo(PsbStatus::Assigned));
        $this->assertTrue(PsbStatus::Assigned->canTransitionTo(PsbStatus::Provisioning));
        $this->assertTrue(PsbStatus::Provisioning->canTransitionTo(PsbStatus::Photos));
        $this->assertTrue(PsbStatus::Photos->canTransitionTo(PsbStatus::Done));
    }

    public function test_psb_status_rejected_can_revert_to_provisioning(): void
    {
        // Reject flow per jawaban #6
        $this->assertTrue(PsbStatus::Rejected->canTransitionTo(PsbStatus::Provisioning));
    }

    public function test_psb_status_done_is_terminal(): void
    {
        $this->assertFalse(PsbStatus::Done->canTransitionTo(PsbStatus::Submitted));
        $this->assertFalse(PsbStatus::Done->canTransitionTo(PsbStatus::Draft));
    }

    public function test_psb_status_rejects_skip_steps(): void
    {
        // Cannot skip from draft to done
        $this->assertFalse(PsbStatus::Draft->canTransitionTo(PsbStatus::Done));
        // Cannot skip from submitted to done
        $this->assertFalse(PsbStatus::Submitted->canTransitionTo(PsbStatus::Done));
    }

    public function test_olt_type_hioso_requires_manual_checklist(): void
    {
        $this->assertTrue(OltType::Hioso->requiresManualChecklist());
        $this->assertFalse(OltType::C300->requiresManualChecklist());
    }

    public function test_coverage_status_labels(): void
    {
        $this->assertEquals('Pending', CoverageStatus::Pending->label());
        $this->assertEquals('Disetujui', CoverageStatus::Approved->label());
        $this->assertEquals('Ditolak', CoverageStatus::Rejected->label());
    }
}
