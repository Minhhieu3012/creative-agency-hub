<?php
namespace App\Middleware;

use Core\JwtHandler;
use Throwable;

class AuthMiddleware {
    private static function json(array $payload, int $statusCode = 401): void {
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function getAuthorizationHeader(): string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return trim((string)(
            $headers['Authorization']
            ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? ''
        ));
    }

    private static function extractBearerToken(string $authHeader): string {
        if ($authHeader === '') {
            return '';
        }

        if (stripos($authHeader, 'Bearer ') === 0) {
            return trim(substr($authHeader, 7));
        }

        return trim($authHeader);
    }

    private static function normalizePayload($payload): ?array {
        if (!$payload) {
            return null;
        }

        if (is_object($payload)) {
            $payload = (array)$payload;
        }

        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['data']) && (is_array($payload['data']) || is_object($payload['data']))) {
            $payload = (array)$payload['data'];
        }

        if (empty($payload['id'])) {
            return null;
        }

        return [
            'id' => (int)$payload['id'],
            'email' => $payload['email'] ?? null,
            'role' => strtolower((string)($payload['role'] ?? '')),
            'full_name' => $payload['full_name'] ?? ($payload['name'] ?? null),
        ];
    }

    private static function syncSession(array $authUser): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = (int)$authUser['id'];
        $_SESSION['user_email'] = $authUser['email'] ?? null;
        $_SESSION['user_role'] = strtolower((string)($authUser['role'] ?? ''));
        $_SESSION['full_name'] = $authUser['full_name'] ?? null;
    }

    private static function getUserFromSession(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => (int)$_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? null,
            'role' => strtolower((string)($_SESSION['user_role'] ?? '')),
            'full_name' => $_SESSION['full_name'] ?? null,
        ];
    }

    private static function getToken(): string {
        $authHeader = self::getAuthorizationHeader();

        if ($authHeader !== '') {
            $token = self::extractBearerToken($authHeader);

            if ($token !== '') {
                return $token;
            }
        }

        if (!empty($_COOKIE['cah_token'])) {
            return trim((string)$_COOKIE['cah_token']);
        }

        return '';
    }

    public static function check(): array {
        $token = self::getToken();

        if ($token !== '') {
            try {
                $jwt = new JwtHandler();
                $decoded = $jwt->decode($token);
                $authUser = self::normalizePayload($decoded);

                if ($authUser && !empty($authUser['id'])) {
                    self::syncSession($authUser);
                    return $authUser;
                }
            } catch (Throwable $e) {
                // Nếu token lỗi thì thử fallback session bên dưới.
            }
        }

        $sessionUser = self::getUserFromSession();

        if ($sessionUser && !empty($sessionUser['id'])) {
            return $sessionUser;
        }

        self::json([
            'status' => 'error',
            'message' => 'Unauthorized: Phiên làm việc hết hạn. Vui lòng đăng nhập lại.'
        ], 401);
    }

    public static function requireAuth(): array {
        return self::check();
    }
}