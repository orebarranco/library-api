<?php

declare(strict_types=1);
use App\Exceptions\ApiExceptionHandler;
use App\Exceptions\Auth\AccountSuspendedException;
use App\Exceptions\Auth\EmailNotVerifiedException;
use App\Exceptions\Auth\InsufficientPermissionsException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Catalog\BookHasActiveLoansException;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Exceptions\Reservation\DuplicateReservationException;
use App\Exceptions\Reservation\NoCopiesAvailableException;
use App\Exceptions\Reservation\OverdueLoansException;
use App\Exceptions\Reservation\ReservationLimitExceededException;
use App\Exceptions\Reservation\ReservationNotCancellableException;
use App\Exceptions\Reservation\ReservationNotPendingException;
use App\Exceptions\Reservation\UnpaidFinesException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

// --- Feature tests via HTTP ---
it('returns 404 with JSON:API error format for unknown routes', function (): void {
    $this->getJson('/api/v1/nonexistent-route')
        ->assertNotFound()
        ->assertJsonStructure(['errors' => [['status', 'code', 'title', 'detail']], 'meta'])
        ->assertJsonPath('errors.0.code', 'NOT_FOUND');
});
it('returns 401 with JSON:API error format when unauthenticated', function (): void {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('errors.0.code', 'UNAUTHENTICATED');
});
it('returns 422 with JSON:API field-level errors on validation failure', function (): void {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => [['status', 'code', 'title', 'detail', 'source' => ['pointer']]]]);
});
// --- Unit tests for ApiExceptionHandler ---
beforeEach(function (): void {
    $this->handler = new ApiExceptionHandler();
});
it('handles ValidationException with field errors', function (): void {
    $validator = validator(['email' => ''], ['email' => 'required|email']);
    $e = new ValidationException($validator);

    $response = $this->handler->render($e);
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('VALIDATION_ERROR')
        ->and($data['errors'][0]['source']['pointer'])->toBe('/data/attributes/email');
});
it('handles DuplicateIsbnException as 422', function (): void {
    $response = $this->handler->render(new DuplicateIsbnException('9780132350884'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('DUPLICATE_ISBN');
});

it('handles BookHasActiveLoansException as 409', function (): void {
    $response = $this->handler->render(new BookHasActiveLoansException('Clean Code'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_CONFLICT)
        ->and($data['errors'][0]['code'])->toBe('BOOK_HAS_ACTIVE_LOANS');
});

it('handles NoCopiesAvailableException as 422', function (): void {
    $response = $this->handler->render(new NoCopiesAvailableException('book-123'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('NO_COPIES_AVAILABLE');
});

it('handles DuplicateReservationException as 422', function (): void {
    $response = $this->handler->render(new DuplicateReservationException('book-123'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('DUPLICATE_RESERVATION');
});

it('handles ReservationLimitExceededException as 422', function (): void {
    $response = $this->handler->render(new ReservationLimitExceededException('user-123'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('RESERVATION_LIMIT');
});

it('handles UnpaidFinesException as 422', function (): void {
    $response = $this->handler->render(new UnpaidFinesException('user-123'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('UNPAID_FINES');
});

it('handles OverdueLoansException as 422', function (): void {
    $response = $this->handler->render(new OverdueLoansException('user-123'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('OVERDUE_LOANS');
});

it('handles ReservationNotPendingException as 422', function (): void {
    $response = $this->handler->render(new ReservationNotPendingException('reservation-123'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('RESERVATION_NOT_PENDING');
});

it('handles ReservationNotCancellableException as 422', function (): void {
    $response = $this->handler->render(new ReservationNotCancellableException('reservation-123'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($data['errors'][0]['code'])->toBe('RESERVATION_NOT_CANCELLABLE');
});

it('handles AccountSuspendedException as 403', function (): void {
    $response = $this->handler->render(new AccountSuspendedException());
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_FORBIDDEN)
        ->and($data['errors'][0]['code'])->toBe('ACCOUNT_SUSPENDED')
        ->and($data['errors'][0]['detail'])->toBe('Your account has been suspended. Please contact support.');
});

it('handles InvalidCredentialsException as 401', function (): void {
    $response = $this->handler->render(new InvalidCredentialsException());
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and($data['errors'][0]['code'])->toBe('INVALID_CREDENTIALS')
        ->and($data['errors'][0]['detail'])->toBe('The provided credentials are incorrect.');
});
it('handles EmailNotVerifiedException as 403', function (): void {
    $response = $this->handler->render(new EmailNotVerifiedException());
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_FORBIDDEN)
        ->and($data['errors'][0]['code'])->toBe('EMAIL_NOT_VERIFIED')
        ->and($data['errors'][0]['detail'])->toBe('You must verify your email address before accessing this resource.');
});

it('handles InvalidSignatureException as 403', function (): void {
    $response = $this->handler->render(new InvalidSignatureException());
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_FORBIDDEN)
        ->and($data['errors'][0]['code'])->toBe('INVALID_SIGNATURE')
        ->and($data['errors'][0]['detail'])->toBe('The verification link is invalid or has expired.');
});

it('handles AuthenticationException', function (): void {
    $response = $this->handler->render(new AuthenticationException());

    expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and($response->getData(true)['errors'][0]['code'])->toBe('UNAUTHENTICATED');
});
it('handles ModelNotFoundException as 404', function (): void {
    $response = $this->handler->render(new ModelNotFoundException());

    expect($response->status())->toBe(Response::HTTP_NOT_FOUND)
        ->and($response->getData(true)['errors'][0]['code'])->toBe('NOT_FOUND');
});
it('handles NotFoundHttpException as 404', function (): void {
    $response = $this->handler->render(new NotFoundHttpException());

    expect($response->status())->toBe(Response::HTTP_NOT_FOUND)
        ->and($response->getData(true)['errors'][0]['code'])->toBe('NOT_FOUND');
});
it('handles AuthorizationException as 403', function (): void {
    $response = $this->handler->render(new AuthorizationException());
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_FORBIDDEN)
        ->and($data['errors'][0]['code'])->toBe('UNAUTHORIZED')
        ->and($data['errors'][0]['title'])->toBe('Unauthorized.');
});
it('handles InsufficientPermissionsException as 403', function (): void {
    $response = $this->handler->render(new InsufficientPermissionsException());
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_FORBIDDEN)
        ->and($data['errors'][0]['code'])->toBe('INSUFFICIENT_PERMISSIONS');
});
it('handles TooManyRequestsHttpException as 429', function (): void {
    $response = $this->handler->render(new TooManyRequestsHttpException());

    expect($response->status())->toBe(Response::HTTP_TOO_MANY_REQUESTS)
        ->and($response->getData(true)['errors'][0]['code'])->toBe('TOO_MANY_REQUESTS');
});
it('handles HttpException with its status code and message', function (): void {
    $e = new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'Service down');

    $response = $this->handler->render($e);
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_SERVICE_UNAVAILABLE)
        ->and($data['errors'][0]['code'])->toBe('HTTP_ERROR')
        ->and($data['errors'][0]['title'])->toBe('Service down');
});
it('handles HttpException with empty message uses fallback', function (): void {
    $e = new HttpException(Response::HTTP_SERVICE_UNAVAILABLE);

    $response = $this->handler->render($e);

    expect($response->getData(true)['errors'][0]['title'])->toBe('HTTP Error.');
});
it('handles generic Throwable as 500 with hidden message in production', function (): void {
    config(['app.debug' => false]);

    $response = $this->handler->render(new RuntimeException('Sensitive info'));
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR)
        ->and($data['errors'][0]['code'])->toBe('SERVER_ERROR')
        ->and($data['errors'][0]['detail'])->toBe('An unexpected error occurred.');
});
it('exposes exception message in debug mode for generic errors', function (): void {
    config(['app.debug' => true]);

    $response = $this->handler->render(new RuntimeException('Sensitive info'));

    expect($response->getData(true)['errors'][0]['detail'])->toBe('Sensitive info');
});
