<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Controllers;

use Finance\Domains\Auth\Actions\LoginAction;
use Finance\Domains\Auth\Actions\LogoutAction;
use Finance\Domains\Auth\Actions\RefreshTokensAction;
use Finance\Domains\Auth\Exceptions\InvalidCredentialsException;
use Finance\Domains\Auth\Exceptions\InvalidRefreshTokenException;
use Finance\Domains\Auth\Models\UserModel;
use Finance\Domains\Auth\Requests\LoginRequest;
use Finance\Domains\Auth\Requests\RefreshTokenRequest;
use Finance\Domains\Auth\Resources\AuthenticatedSessionResource;
use Finance\Domains\Auth\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AuthController
{
    /**
     * @throws InvalidCredentialsException
     */
    public function login(LoginRequest $request, LoginAction $login): JsonResponse
    {
        $session = $login->execute($request->email(), $request->password());

        return new JsonResponse(AuthenticatedSessionResource::toArray($session));
    }

    /**
     * @throws InvalidRefreshTokenException
     */
    public function refresh(RefreshTokenRequest $request, RefreshTokensAction $refresh): JsonResponse
    {
        $session = $refresh->execute($request->refreshToken());

        return new JsonResponse(AuthenticatedSessionResource::toArray($session));
    }

    public function logout(RefreshTokenRequest $request, LogoutAction $logout): JsonResponse
    {
        $logout->execute($request->refreshToken());

        return new JsonResponse(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof UserModel) {
            return new JsonResponse(['message' => 'Требуется вход'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(UserResource::toArray($user));
    }
}
