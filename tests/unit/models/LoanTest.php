<?php

namespace tests\unit\models;

use app\models\Loan;
use Codeception\Test\Unit;

/**
 * Unit tests for the overdue rules of BR-6.
 *
 * Every case passes an explicit reference date, so the result does not depend
 * on the day the suite happens to run.
 *
 * getLateFee() is not covered here: it loads the equipment with
 * getEquipment()->one(), which always queries, so the deposit cap cannot be
 * exercised without a row in the database. Changing that call to
 * $this->equipment would make the fee unit-testable and remove an N+1 in the
 * overdue report at the same time.
 */
class LoanTest extends Unit
{
    public function testDailyLateFeeMatchesTheSpecification(): void
    {
        $this->assertSame(500, Loan::DAILY_LATE_FEE);
    }

    public function testOpenLoanLimitMatchesTheSpecification(): void
    {
        $this->assertSame(3, Loan::MAX_OPEN_LOANS_PER_BORROWER);
    }

    public function testALoanIsOpenUntilItIsReturned(): void
    {
        $open = new Loan(['returned_at' => null]);
        $closed = new Loan(['returned_at' => '2026-08-25']);

        $this->assertTrue($open->isOpen());
        $this->assertFalse($closed->isOpen());
    }

    /**
     * BR-6: overdue means returned_at IS NULL and due_at is in the past.
     */
    public function testAnOpenLoanPastItsDueDateIsOverdue(): void
    {
        $loan = new Loan(['due_at' => '2026-08-20', 'returned_at' => null]);

        $this->assertTrue($loan->isOverdue('2026-08-25'));
    }

    public function testALoanDueTodayIsNotOverdueYet(): void
    {
        $loan = new Loan(['due_at' => '2026-08-25', 'returned_at' => null]);

        $this->assertFalse(
            $loan->isOverdue('2026-08-25'),
            'A határidő napján még nincs késés - a szabály szerint due_at < ma.'
        );
    }

    public function testALoanDueInTheFutureIsNotOverdue(): void
    {
        $loan = new Loan(['due_at' => '2026-08-30', 'returned_at' => null]);

        $this->assertFalse($loan->isOverdue('2026-08-25'));
    }

    /**
     * A returned loan is never overdue, however late it was.
     */
    public function testAReturnedLoanIsNeverOverdue(): void
    {
        $loan = new Loan([
            'due_at' => '2026-08-20',
            'returned_at' => '2026-08-24',
        ]);

        $this->assertFalse($loan->isOverdue('2026-08-25'));
    }

    /**
     * @dataProvider dueDatesAndExpectedDays
     */
    public function testOverdueDaysAreCountedFromTheDueDate(
        string $dueAt,
        string $asOf,
        int $expected
    ): void {
        $loan = new Loan(['due_at' => $dueAt, 'returned_at' => null]);

        $this->assertSame($expected, $loan->getOverdueDays($asOf));
    }

    public function dueDatesAndExpectedDays(): array
    {
        return [
            'öt nap késés' => ['2026-08-20', '2026-08-25', 5],
            'egy nap késés' => ['2026-08-24', '2026-08-25', 1],
            'a határidő napja' => ['2026-08-25', '2026-08-25', 0],
            'még nem járt le' => ['2026-08-30', '2026-08-25', 0],
            'hónapfordulón át' => ['2026-07-28', '2026-08-03', 6],
            'évfordulón át' => ['2025-12-30', '2026-01-02', 3],
        ];
    }

    /**
     * For a closed loan the count stops at the return date, not today - this is
     * what makes the fee stable once the item is back.
     */
    public function testOverdueDaysStopAtTheReturnDate(): void
    {
        $loan = new Loan([
            'due_at' => '2026-08-20',
            'returned_at' => '2026-08-23',
        ]);

        $this->assertSame(3, $loan->getOverdueDays());
    }

    public function testOverdueDaysAreZeroWithoutADueDate(): void
    {
        $loan = new Loan(['due_at' => null, 'returned_at' => null]);

        $this->assertSame(0, $loan->getOverdueDays('2026-08-25'));
    }
}
