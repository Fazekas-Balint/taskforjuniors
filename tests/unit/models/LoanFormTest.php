<?php

namespace tests\unit\models;

use app\models\LoanForm;
use Codeception\Test\Unit;

/**
 * Unit tests for the date rules of BR-3.
 *
 * The validator methods are called directly rather than through validate(),
 * so the rules that look up equipment and borrowers (BR-1, BR-2, BR-4) never
 * run and no query is sent. LoanForm is a plain Model, so nothing here needs
 * a database at all.
 *
 * Note on naming: in LoanForm the date rules live in validateSz2() and
 * validateSz5(), while the specification calls them BR-3. The method names do
 * not line up with the rule numbers - worth renaming, but that is the owner's
 * call, so these tests document the actual mapping instead.
 */
class LoanFormTest extends Unit
{
    /**
     * BR-3: due_at must be strictly after loaned_at.
     */
    public function testDueDateBeforeTheLoanDateIsRejected(): void
    {
        $form = $this->form('2026-09-10', '2026-09-05');

        $form->validateSz2('due_at', []);

        $this->assertTrue($form->hasErrors('due_at'));
    }

    public function testTheSameDayForBothDatesIsRejected(): void
    {
        $form = $this->form('2026-09-10', '2026-09-10');

        $form->validateSz2('due_at', []);

        $this->assertTrue(
            $form->hasErrors('due_at'),
            'A szabály szigorúan due_at > loaned_at, tehát a nulla napos kölcsönzés sem érvényes.'
        );
    }

    public function testASingleDayLoanIsAccepted(): void
    {
        $form = $this->form('2026-09-10', '2026-09-11');

        $form->validateSz2('due_at', []);

        $this->assertFalse($form->hasErrors('due_at'));
    }

    /**
     * BR-3: the loan may not be longer than 30 days. The boundary is tested
     * from both sides, because an off-by-one here is invisible in the UI.
     */
    public function testExactlyThirtyDaysIsAccepted(): void
    {
        $form = $this->form('2026-09-01', '2026-10-01');

        $form->validateSz2('due_at', []);

        $this->assertFalse($form->hasErrors('due_at'));
    }

    public function testThirtyOneDaysIsRejected(): void
    {
        $form = $this->form('2026-09-01', '2026-10-02');

        $form->validateSz2('due_at', []);

        $this->assertTrue($form->hasErrors('due_at'));
        $this->assertSame(
            'A kölcsönzés hossza legfeljebb 30 nap lehet.',
            $form->getFirstError('due_at')
        );
    }

    /**
     * BR-3: the loan may not start in the past.
     *
     * These use relative dates on purpose - the rule compares against today,
     * so a hard-coded date would start failing once it slips into the past.
     */
    public function testALoanDateInThePastIsRejected(): void
    {
        $form = $this->form(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('+7 days')));

        $form->validateSz5('loaned_at', []);

        $this->assertTrue($form->hasErrors('loaned_at'));
    }

    public function testALoanStartingTodayIsAccepted(): void
    {
        $form = $this->form(date('Y-m-d'), date('Y-m-d', strtotime('+7 days')));

        $form->validateSz5('loaned_at', []);

        $this->assertFalse($form->hasErrors('loaned_at'));
    }

    public function testALoanStartingInTheFutureIsAccepted(): void
    {
        $form = $this->form(date('Y-m-d', strtotime('+3 days')), date('Y-m-d', strtotime('+10 days')));

        $form->validateSz5('loaned_at', []);

        $this->assertFalse($form->hasErrors('loaned_at'));
    }

    /**
     * A date that matches the format but does not exist in the calendar.
     */
    public function testANonExistentCalendarDateIsRejected(): void
    {
        $form = $this->form('2026-02-30', '2026-03-05');

        $form->validateRealDate('loaned_at', []);

        $this->assertTrue(
            $form->hasErrors('loaned_at'),
            'Február 30. formailag helyes, naptárilag nem létezik.'
        );
    }

    public function testALeapDayIsAccepted(): void
    {
        $form = $this->form('2028-02-29', '2028-03-05');

        $form->validateRealDate('loaned_at', []);

        $this->assertFalse($form->hasErrors('loaned_at'));
    }

    /**
     * An empty date must not produce a date error - the required rule is
     * responsible for that message, and two errors on one field would be
     * confusing on the form.
     */
    public function testAnEmptyDateIsLeftToTheRequiredRule(): void
    {
        $form = $this->form('', '');

        $form->validateSz2('due_at', []);
        $form->validateSz5('loaned_at', []);
        $form->validateRealDate('loaned_at', []);

        $this->assertFalse($form->hasErrors('due_at'));
        $this->assertFalse($form->hasErrors('loaned_at'));
    }

    private function form(string $loanedAt, string $dueAt): LoanForm
    {
        return new LoanForm([
            'equipment_id' => 1,
            'borrower_id' => 1,
            'loaned_at' => $loanedAt,
            'due_at' => $dueAt,
        ]);
    }
}
