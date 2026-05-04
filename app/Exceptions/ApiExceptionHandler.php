<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Auth\EmailNotVerifiedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidPasswordResetTokenException;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
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
            $e instanceof InvalidCredentialsException => $this->handleInvalidCredentials($e),
            $e instanceof InvalidPasswordResetTokenException => $this->handleInvalidPasswordResetToken($e),
            $e instanceof EmailNotVerifiedException => $this->handleEmailNotVerified(),
            $e instanceof AuthenticationException => $this->handleAuthentication(),
            $e instanceof AuthorizationException => $this->handleAuthorization(),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => $this->handleNotFound(),
            $e instanceof TooManyRequestsHttpException => $this->handleThrottle(),
            $e instanceof InvalidSignatureException => $this->handleInvalidSignature(),
            $e instanceof HttpException => $this->handleHttp($e),
            default => $this->handleGeneric($e),
        };
    }

    private function handleValidation(ValidationException $e): JsonResponse
    {
        return $this->validationError($e->errors(), $e->getMessage());
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
