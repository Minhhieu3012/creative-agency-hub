<?php
namespace App\Middleware;

use Core\JwtHandler;

class AuthMiddleware {
    private static function ensureJwtEnvDefaults(): void {
        if (empty($_ENV['JWT_SECRET'])) {
            $_ENV['JWT_SECRET'] = 'creative_agency_hub_local_secret_key';
        }

        if (empty($_ENV['JWT_EXPIRATION'])) {
            $_ENV['JWT_EXPIRATION'] = 86400;
        }
    }

    private static function getAuthorizationHeader(): string {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!empty($_SERVER['Authorization'])) {
            return $_SERVER['Authorization'];
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (!empty($headers['Authorization'])) {
                return $headers['Authorization'];
            }

            if (!empty($headers['authorization'])) {
                return $headers['authorization'];
            }
        }

        return '';
    }

    public static function check(): array {
        self::ensureJwtEnvDefaults();

        $authHeader = self::getAuthorizationHeader();

        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            $jwt = new JwtHandler();
            $decoded = $jwt->decode($token);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);

        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized: Bạn chưa đăng nhập hoặc token đã hết hạn.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}