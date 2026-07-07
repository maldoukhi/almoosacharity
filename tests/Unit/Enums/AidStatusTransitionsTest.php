<?php

use App\Enums\AidStatus;

/**
 * The full transition matrix documented on AidStatus::allowedTransitions().
 * Every (from, to) pair not explicitly listed as allowed must be denied,
 * including all draft -> approved/rejected/... "skip the workflow" jumps.
 */
dataset('allowed transitions', [
    'draft -> submitted' => [AidStatus::Draft, AidStatus::Submitted, true],
    'submitted -> under_review' => [AidStatus::Submitted, AidStatus::UnderReview, true],
    'submitted -> cancelled' => [AidStatus::Submitted, AidStatus::Cancelled, true],
    'under_review -> approved' => [AidStatus::UnderReview, AidStatus::Approved, true],
    'under_review -> rejected' => [AidStatus::UnderReview, AidStatus::Rejected, true],
    'under_review -> draft (returned)' => [AidStatus::UnderReview, AidStatus::Draft, true],
    'under_review -> cancelled' => [AidStatus::UnderReview, AidStatus::Cancelled, true],
    'approved -> in_disbursement' => [AidStatus::Approved, AidStatus::InDisbursement, true],
    'in_disbursement -> delivered' => [AidStatus::InDisbursement, AidStatus::Delivered, true],
]);

dataset('forbidden transitions', [
    'draft -> approved (skips workflow)' => [AidStatus::Draft, AidStatus::Approved, false],
    'draft -> rejected (skips workflow)' => [AidStatus::Draft, AidStatus::Rejected, false],
    'draft -> under_review (skips submission)' => [AidStatus::Draft, AidStatus::UnderReview, false],
    'draft -> delivered' => [AidStatus::Draft, AidStatus::Delivered, false],
    'draft -> cancelled directly' => [AidStatus::Draft, AidStatus::Cancelled, false],
    'submitted -> approved (skips review)' => [AidStatus::Submitted, AidStatus::Approved, false],
    'submitted -> rejected (skips review)' => [AidStatus::Submitted, AidStatus::Rejected, false],
    'submitted -> draft' => [AidStatus::Submitted, AidStatus::Draft, false],
    'under_review -> in_disbursement' => [AidStatus::UnderReview, AidStatus::InDisbursement, false],
    'under_review -> delivered' => [AidStatus::UnderReview, AidStatus::Delivered, false],
    'approved -> delivered (skips disbursement)' => [AidStatus::Approved, AidStatus::Delivered, false],
    'approved -> rejected' => [AidStatus::Approved, AidStatus::Rejected, false],
    'approved -> draft' => [AidStatus::Approved, AidStatus::Draft, false],
    'approved -> cancelled' => [AidStatus::Approved, AidStatus::Cancelled, false],
    'in_disbursement -> approved' => [AidStatus::InDisbursement, AidStatus::Approved, false],
    'in_disbursement -> cancelled' => [AidStatus::InDisbursement, AidStatus::Cancelled, false],
    'delivered -> anything (draft)' => [AidStatus::Delivered, AidStatus::Draft, false],
    'delivered -> anything (approved)' => [AidStatus::Delivered, AidStatus::Approved, false],
    'rejected -> anything (draft)' => [AidStatus::Rejected, AidStatus::Draft, false],
    'rejected -> anything (under_review)' => [AidStatus::Rejected, AidStatus::UnderReview, false],
    'cancelled -> anything (draft)' => [AidStatus::Cancelled, AidStatus::Draft, false],
    'cancelled -> anything (under_review)' => [AidStatus::Cancelled, AidStatus::UnderReview, false],
]);

it('allows every transition documented in the state machine', function (AidStatus $from, AidStatus $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with('allowed transitions');

it('forbids every transition not documented in the state machine', function (AidStatus $from, AidStatus $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with('forbidden transitions');

it('marks only rejected and cancelled as truly final', function () {
    expect(AidStatus::Rejected->isFinal())->toBeTrue();
    expect(AidStatus::Cancelled->isFinal())->toBeTrue();

    expect(AidStatus::Draft->isFinal())->toBeFalse();
    expect(AidStatus::Submitted->isFinal())->toBeFalse();
    expect(AidStatus::UnderReview->isFinal())->toBeFalse();
    expect(AidStatus::Approved->isFinal())->toBeFalse();
    expect(AidStatus::InDisbursement->isFinal())->toBeFalse();
    // Delivered intentionally is not final: phase 6 delivery confirmation
    // still needs to act on it.
    expect(AidStatus::Delivered->isFinal())->toBeFalse();
});

it('marks only draft as editable', function () {
    foreach (AidStatus::cases() as $status) {
        expect($status->isEditable())->toBe($status === AidStatus::Draft);
    }
});
