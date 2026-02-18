<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\InvalidTokenException;
use App\Domain\Auth\JwtTokenServiceInterface;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Rs256JwtTokenService implements JwtTokenServiceInterface
{
    private string $publicKeyPath;
    private string $issuer;
    private string $audience;

    public function __construct()
    {
        $this->publicKeyPath = config('jwt.keys.public');
        $this->issuer        = config('jwt.issuer');
        $this->audience      = config('jwt.audience');
    }

    public function validateToken(string $token): array
    {
        try {
            $publicKey = file_get_contents($this->publicKeyPath);

            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));
            $claims  = (array) $decoded;

            if (($claims['iss'] ?? '') !== $this->issuer) {
                throw new InvalidTokenException('Invalid issuer');
            }

            if (($claims['aud'] ?? '') !== $this->audience) {
                throw new InvalidTokenException('Invalid audience');
            }

            return $claims;
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (ExpiredException $e) {
            throw new InvalidTokenException('Token expired', 0, $e);
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Token validation failed: '.$e->getMessage(), 0, $e);
        }
    }
}
