<?php

namespace AffiChat\Laravel\Webhook;

class AffiChatWebhook {
    /**
     * Verify HMAC-SHA256 signature from X-AffiChat-Signature header.
     */
    public static function verifySignature(string $rawPayload, string $signature, string $secret): bool {
        if (empty($rawPayload) || empty($signature) || empty($secret)) {
            return false;
        }

        $computed = hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals($computed, trim($signature));
    }

    /**
     * Decode and validate incoming AffiChat webhook payload.
     *
     * @return array<string, mixed>
     */
    public static function parsePayload(string $rawPayload): array {
        $decoded = json_decode($rawPayload, true);
        if (!is_array($decoded)) {
            return [];
        }
        return $decoded;
    }
}
