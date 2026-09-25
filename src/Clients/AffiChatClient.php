<?php

namespace AffiChat\Laravel\Clients;

use AffiChat\Laravel\Exceptions\CouldNotSendNotification;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class AffiChatClient {
    public const BASE_URL = 'https://chat.affidev.com';

    protected string $apiKey;
    protected string $defaultSessionId;
    protected int $timeout;
    protected ClientInterface $http;

    public function __construct(
        string $apiKey = '',
        string $defaultSessionId = 'default',
        int $timeout = 15,
        ?ClientInterface $http = null
    ) {
        $this->apiKey = $apiKey;
        $this->defaultSessionId = $defaultSessionId ?: 'default';
        $this->timeout = $timeout;
        $this->http = $http ?: new GuzzleClient([
            'base_uri' => self::BASE_URL,
            'timeout'  => $this->timeout,
        ]);
    }

    public static function normalizePhone(string $phone): ?string {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) {
            return null;
        }

        if (str_starts_with($clean, '00')) {
            $clean = substr($clean, 2);
        }
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }

        if (strlen($clean) < 9 || strlen($clean) > 16) {
            return null;
        }

        return $clean;
    }

    public function sendText(string $to, string $text, ?string $sessionId = null): array {
        return $this->post('/api/send-text', [
            'sessionId' => $sessionId ?: $this->defaultSessionId,
            'to'        => $this->validateAndNormalize($to),
            'text'      => $text,
        ]);
    }

    public function sendImage(string $to, string $imageUrl, string $caption = '', ?string $sessionId = null): array {
        return $this->post('/api/send-image', [
            'sessionId' => $sessionId ?: $this->defaultSessionId,
            'to'        => $this->validateAndNormalize($to),
            'imageUrl'  => $imageUrl,
            'image_url' => $imageUrl,
            'caption'   => $caption,
        ]);
    }

    public function sendDocument(string $to, string $documentUrl, string $filename = '', ?string $sessionId = null): array {
        $resolvedFilename = $filename ?: basename(parse_url($documentUrl, PHP_URL_PATH) ?: 'document.pdf');
        return $this->post('/api/send-document', [
            'sessionId'     => $sessionId ?: $this->defaultSessionId,
            'to'            => $this->validateAndNormalize($to),
            'documentUrl'   => $documentUrl,
            'document_url'  => $documentUrl,
            'filename'      => $resolvedFilename,
            'fileName'      => $resolvedFilename,
            'document_name' => $resolvedFilename,
        ]);
    }

    public function sendVideo(string $to, string $videoUrl, string $caption = '', ?string $sessionId = null): array {
        return $this->post('/api/send-video', [
            'sessionId' => $sessionId ?: $this->defaultSessionId,
            'to'        => $this->validateAndNormalize($to),
            'videoUrl'  => $videoUrl,
            'video_url' => $videoUrl,
            'caption'   => $caption,
        ]);
    }

    public function sendLocation(string $to, float $latitude, float $longitude, string $name = '', string $address = '', ?string $sessionId = null): array {
        return $this->post('/api/send-location', [
            'sessionId' => $sessionId ?: $this->defaultSessionId,
            'to'        => $this->validateAndNormalize($to),
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'name'      => $name,
            'title'     => $name,
            'address'   => $address,
        ]);
    }

    public function sendContact(string $to, string $name, string $phone, ?string $sessionId = null): array {
        $contactPhone = $this->validateAndNormalize($phone);
        return $this->post('/api/send-contact', [
            'sessionId'     => $sessionId ?: $this->defaultSessionId,
            'to'            => $this->validateAndNormalize($to),
            'contactName'   => $name,
            'name'          => $name,
            'contactNumber' => $contactPhone,
            'phone'         => $contactPhone,
            'phoneNumber'   => $contactPhone,
        ]);
    }

    public function sendSticker(string $to, string $stickerUrl, ?string $sessionId = null): array {
        return $this->post('/api/send-sticker', [
            'sessionId'  => $sessionId ?: $this->defaultSessionId,
            'to'         => $this->validateAndNormalize($to),
            'stickerUrl' => $stickerUrl,
        ]);
    }

    public function sendPoll(string $to, string $question, array $options, bool $multiple = false, ?string $sessionId = null): array {
        return $this->post('/api/send-poll', [
            'sessionId'       => $sessionId ?: $this->defaultSessionId,
            'to'              => $this->validateAndNormalize($to),
            'question'        => $question,
            'options'         => array_values($options),
            'multipleAnswers' => $multiple,
        ]);
    }

    public function checkApiKey(): array {
        try {
            $response = $this->http->request('GET', '/api/key/check', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Accept'    => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (GuzzleException $e) {
            throw CouldNotSendNotification::couldNotCommunicateWithAffiChat($e);
        }
    }

    protected function post(string $endpoint, array $payload): array {
        try {
            $response = $this->http->request('POST', $endpoint, [
                'headers' => [
                    'x-api-key'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $payload,
            ]);

            $code = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if ($code < 200 || $code >= 300) {
                throw CouldNotSendNotification::serviceRespondedWithAnError($code, $body);
            }

            return $data ?: ['success' => true];
        } catch (CouldNotSendNotification $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw CouldNotSendNotification::couldNotCommunicateWithAffiChat($e);
        }
    }

    protected function validateAndNormalize(string $phone): string {
        $normalized = self::normalizePhone($phone);
        if (!$normalized) {
            throw CouldNotSendNotification::invalidPhoneNumber($phone);
        }
        return $normalized;
    }
}
