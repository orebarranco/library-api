<?php

declare(strict_types=1);

use App\Exceptions\Loan\BookHasReservationsException;
use App\Exceptions\Loan\LoanAlreadyReturnedException;
use App\Exceptions\Loan\LoanOverdueException;
use App\Exceptions\Loan\RenewalLimitReachedException;
use App\Exceptions\Loan\RenewalTooLateException;
use App\Exceptions\Loan\ReservationNotApprovedException;

it('exposes the reservation id that is not approved', function (): void {
    $exception = new ReservationNotApprovedException('reservation-123');

    expect($exception->reservationId)->toBe('reservation-123');
    expect($exception->getMessage())->toContain('reservation-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('exposes the loan id that reached the renewal limit', function (): void {
    $exception = new RenewalLimitReachedException('loan-123');

    expect($exception->loanId)->toBe('loan-123');
    expect($exception->getMessage())->toContain('loan-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('exposes the overdue loan id', function (): void {
    $exception = new LoanOverdueException('loan-456');

    expect($exception->loanId)->toBe('loan-456');
    expect($exception->getMessage())->toContain('loan-456');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('exposes the book id that has active reservations', function (): void {
    $exception = new BookHasReservationsException('book-789');

    expect($exception->bookId)->toBe('book-789');
    expect($exception->getMessage())->toContain('book-789');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('exposes the loan id that is too late to renew', function (): void {
    $exception = new RenewalTooLateException('loan-789');

    expect($exception->loanId)->toBe('loan-789');
    expect($exception->getMessage())->toContain('loan-789');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('exposes the loan id that was already returned', function (): void {
    $exception = new LoanAlreadyReturnedException('loan-000');

    expect($exception->loanId)->toBe('loan-000');
    expect($exception->getMessage())->toContain('loan-000');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
