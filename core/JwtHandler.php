<?php
namespace Core;

class JwtHandler {
    private $secret;

    public function __construct() {
        $secret = $_ENV['JWT_SECRET'] ?? '';
        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET chưa được cấu hình trong file .env');
        }
        $this->secret = $secret;
    }

    public function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + (int)$_ENV['JWT_EXPIRATION'];

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($header, $payload, $signature) = $parts;

        $validSignature = $this->base64UrlEncode(
            hash_hmac('sha256', $header . "." . $payload, $this->secret, true)
        );
        if (!hash_equals($validSignature, $signature)) return false;

        $payloadData = json_decode(base64_decode($this->base64UrlDecode($payload)), true);

        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }

        return $payloadData;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data) {
        return str_replace(['-', '_'], ['+', '/'], $data);
    }
}