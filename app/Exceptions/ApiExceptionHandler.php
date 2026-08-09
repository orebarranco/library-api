<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Auth\AccountSuspendedException;
use App\Exceptions\Auth\EmailNotVerifiedException;
use App\Exceptions\Auth\InsufficientPermissionsException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidPasswordResetTokenException;
use App\Exceptions\Catalog\BookCopyHasActiveLoanException;
use App\Exceptions\Catalog\BookHasActiveLoansException;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Exceptions\Loan\BookHasReservationsException;
use App\Exceptions\Loan\LoanAlreadyReturnedException;
use App\Exceptions\Loan\LoanOverdueException;
use App\Exceptions\Loan\RenewalLimitReachedException;
use App\Exceptions\Loan\RenewalTooLateException;
use App\Exceptions\Loan\ReservationNotApprovedException;
use App\Exceptions\Reservation\DuplicateReservationException;
use App\Exceptions\Reservation\NoCopiesAvailableException;
use App\Exceptions\Reservation\OverdueLoansException;
use App\Exceptions\Reservation\ReservationLimitExceededException;
use App\Exceptions\Reservation\ReservationNotCancellableException;
use App\Exceptions\Reservation\ReservationNotPendingException;
use App\Exceptions\Reservation\UnpaidFinesException;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

final class ApiExceptionHandler
{
    use ApiResponse;

    public function render(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => $this->handleValidation($e),
            $e instanceof DuplicateIsbnException => $this->handleDuplicateIsbn($e),
            $e instanceof BookHasActiveLoansException => $this->handleBookHasActiveLoans($e),
            $e instanceof BookCopyHasActiveLoanException => $this->handleBookCopyHasActiveLoan($e),
            $e instanceof NoCopiesAvailableException => $this->handleNoCopiesAvailable($e),
            $e instanceof DuplicateReservationException => $this->handleDuplicateReservation($e),
            $e instanceof ReservationLimitExceededException => $this->handleReservationLimitExceeded($e),
            $e instanceof UnpaidFinesException => $this->handleUnpaidFines($e),
            $e instanceof OverdueLoansException => $this->handleOverdueLoans($e),
            $e instanceof ReservationNotPendingException => $this->handleReservationNotPending($e),
            $e instanceof ReservationNotCancellableException => $this->handleReservationNotCancellable($e),
            $e instanceof ReservationNotApprovedException => $this->handleReservationNotApproved($e),
            $e instanceof RenewalLimitReachedException => $this->handleRenewalLimitReached($e),
            $e instanceof LoanOverdueException => $this->handleLoanOverdue($e),
            $e instanceof BookHasReservationsException => $this->handleBookHasReservations($e),
            $e instanceof RenewalTooLateException => $this->handleRenewalTooLate($e),
            $e instanceof LoanAlreadyReturnedException => $this->handleLoanAlreadyReturned($e),
            $e instanceof AccountSuspendedException => $this->handleAccountSuspended(),
            $e instanceof InvalidCredentialsException => $this->handleInvalidCredentials($e),
            $e instanceof InvalidPasswordResetTokenException => $this->handleInvalidPasswordResetToken($e),
            $e instanceof EmailNotVerifiedException => $this->handleEmailNotVerified(),
            $e instanceof AuthenticationException => $this->handleAuthentication(),
            $e instanceof InsufficientPermissionsException => $this->handleInsufficientPermissions(),
            $e instanceof AuthorizationException => $this->handleAuthorization(),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => $this->handleNotFound(),
            $e instanceof TooManyRequestsHttpException => $this->handleThrottle(),
            $e instanceof InvalidSignatureException => $this->handleInvalidSignature(),
            $e instanceof AccessDeniedHttpException && $e->getPrevious() instanceof InsufficientPermissionsException => $this->handleInsufficientPermissions(),
            $e instanceof AccessDeniedHttpException && $e->getPrevious() instanceof AuthorizationException => $this->handleAuthorization(),
            $e instanceof HttpException => $this->handleHttp($e),
            default => $this->handleGeneric($e),
        };
    }

    private function handleDuplicateIsbn(DuplicateIsbnException $e): JsonResponse
    {
        return $this->error(
            message: 'Duplicate ISBN.',
            code: 'DUPLICATE_ISBN',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleBookHasActiveLoans(BookHasActiveLoansException $e): JsonResponse
    {
        return $this->error(
            message: 'Book has active loans.',
            code: 'BOOK_HAS_ACTIVE_LOANS',
            detail: $e->getMessage(),
            status: Response::HTTP_CONFLICT,
        );
    }

    private function handleBookCopyHasActiveLoan(BookCopyHasActiveLoanException $e): JsonResponse
    {
        return $this->error(
            message: 'Book copy has an active loan.',
            code: 'COPY_HAS_ACTIVE_LOAN',
            detail: $e->getMessage(),
            status: Response::HTTP_CONFLICT,
        );
    }

    private function handleNoCopiesAvailable(NoCopiesAvailableException $e): JsonResponse
    {
        return $this->error(
            message: 'No copies available.',
            code: 'NO_COPIES_AVAILABLE',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleDuplicateReservation(DuplicateReservationException $e): JsonResponse
    {
        return $this->error(
            message: 'Duplicate reservation.',
            code: 'DUPLICATE_RESERVATION',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleReservationLimitExceeded(ReservationLimitExceededException $e): JsonResponse
    {
        return $this->error(
            message: 'Reservation limit exceeded.',
            code: 'RESERVATION_LIMIT',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleUnpaidFines(UnpaidFinesException $e): JsonResponse
    {
        return $this->error(
            message: 'Unpaid fines.',
            code: 'UNPAID_FINES',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleOverdueLoans(OverdueLoansException $e): JsonResponse
    {
        return $this->error(
            message: 'Overdue loans.',
            code: 'OVERDUE_LOANS',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleReservationNotPending(ReservationNotPendingException $e): JsonResponse
    {
        return $this->error(
            message: 'Reservation is not pending.',
            code: 'RESERVATION_NOT_PENDING',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleReservationNotCancellable(ReservationNotCancellableException $e): JsonResponse
    {
        return $this->error(
            message: 'Reservation is not cancellable.',
            code: 'RESERVATION_NOT_CANCELLABLE',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleReservationNotApproved(ReservationNotApprovedException $e): JsonResponse
    {
        return $this->error(
            message: 'Reservation is not approved.',
            code: 'RESERVATION_NOT_APPROVED',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleRenewalLimitReached(RenewalLimitReachedException $e): JsonResponse
    {
        return $this->error(
            message: 'Renewal limit reached.',
            code: 'RENEWAL_LIMIT_REACHED',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleLoanOverdue(LoanOverdueException $e): JsonResponse
    {
        return $this->error(
            message: 'Loan is overdue.',
            code: 'LOAN_OVERDUE',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleBookHasReservations(BookHasReservationsException $e): JsonResponse
    {
        return $this->error(
            message: 'Book has active reservations.',
            code: 'BOOK_HAS_RESERVATIONS',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleRenewalTooLate(RenewalTooLateException $e): JsonResponse
    {
        return $this->error(
            message: 'Renewal is too late.',
            code: 'RENEWAL_TOO_LATE',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleLoanAlreadyReturned(LoanAlreadyReturnedException $e): JsonResponse
    {
        return $this->error(
            message: 'Loan has already been returned.',
            code: 'LOAN_ALREADY_RETURNED',
            detail: $e->getMessage(),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleValidation(ValidationException $e): JsonResponse
    {
        return $this->validationError($e->errors());
    }

    private function handleAccountSuspended(): JsonResponse
    {
        return $this->error(
            message: 'Account suspended.',
            code: 'ACCOUNT_SUSPENDED',
            detail: 'Your account has been suspended. Please contact support.',
            status: Response::HTTP_FORBIDDEN,
        );
    }

    private function handleInvalidCredentials(InvalidCredentialsException $e): JsonResponse
    {
        return $this->error(
            message: $e->getMessage(),
            code: 'INVALID_CREDENTIALS',
            detail: 'The provided credentials are incorrect.',
            status: Response::HTTP_UNAUTHORIZED,
        );
    }

    private function handleInvalidPasswordResetToken(InvalidPasswordResetTokenException $e): JsonResponse
    {
        return $this->error(
            message: $e->getMessage(),
            code: 'INVALID_PASSWORD_RESET_TOKEN',
            detail: 'This password reset token is invalid or has expired.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function handleEmailNotVerified(): JsonResponse
    {
        return $this->error(
            message: 'Email not verified.',
            code: 'EMAIL_NOT_VERIFIED',
            detail: 'You must verify your email address before accessing this resource.',
            status: Response::HTTP_FORBIDDEN,
        );
    }

    private function handleAuthentication(): JsonResponse
    {
        return $this->error(
            message: 'Unauthenticated.',
            code: 'UNAUTHENTICATED',
            detail: 'Authentication is required to access this resource.',
            status: Response::HTTP_UNAUTHORIZED,
        );
    }

    private function handleInsufficientPermissions(): JsonResponse
    {
        return $this->error(
            message: 'Forbidden.',
            code: 'INSUFFICIENT_PERMISSIONS',
            detail: 'You do not have the required role to access this resource.',
            status: Response::HTTP_FORBIDDEN,
        );
    }

    private function handleAuthorization(): JsonResponse
    {
        return $this->error(
            message: 'Unauthorized.',
            code: 'UNAUTHORIZED',
            detail: 'You do not have permission to perform this action.',
            status: Response::HTTP_FORBIDDEN,
        );
    }

    private function handleNotFound(): JsonResponse
    {
        return $this->error(
            message: 'Not Found.',
            code: 'NOT_FOUND',
            detail: 'The requested resource was not found.',
            status: Response::HTTP_NOT_FOUND,
        );
    }

    private function handleThrottle(): JsonResponse
    {
        return $this->error(
            message: 'Too Many Requests.',
            code: 'TOO_MANY_REQUESTS',
            detail: 'You have exceeded the request rate limit.',
            status: Response::HTTP_TOO_MANY_REQUESTS,
        );
    }

    private function handleInvalidSignature(): JsonResponse
    {
        return $this->error(
            message: 'Invalid signature.',
            code: 'INVALID_SIGNATURE',
            detail: 'The verification link is invalid or has expired.',
            status: Response::HTTP_FORBIDDEN,
        );
    }

    private function handleHttp(HttpException $e): JsonResponse
    {
        return $this->error(
            message: $e->getMessage() ?: 'HTTP Error.',
            code: 'HTTP_ERROR',
            detail: $e->getMessage() ?: 'An HTTP error occurred.',
            status: $e->getStatusCode(),
        );
    }

    private function handleGeneric(Throwable $e): JsonResponse
    {
        return $this->error(
            message: 'Server Error.',
            code: 'SERVER_ERROR',
            detail: config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
