<?php

namespace AffiChat\Laravel\Channels;

use AffiChat\Laravel\Clients\AffiChatClient;
use AffiChat\Laravel\Exceptions\CouldNotSendNotification;
use AffiChat\Laravel\Messages\AffiChatMessage;
use Illuminate\Notifications\Notification;

class AffiChatChannel {
    protected AffiChatClient $client;

    public function __construct(AffiChatClient $client) {
        $this->client = $client;
    }

    public function send(mixed $notifiable, Notification $notification): ?array {
        $message = $notification->toAffiChat($notifiable);

        if (is_string($message)) {
            $message = AffiChatMessage::create($message);
        }

        if (!$message instanceof AffiChatMessage) {
            return null;
        }

        $to = $message->getTo() ?: $this->resolveRecipient($notifiable, $notification);
        if (empty($to)) {
            throw CouldNotSendNotification::missingRecipient();
        }

        $sessionId = $message->getSessionId();
        $payload   = $message->getPayload();

        return match ($message->getType()) {
            'text'     => $this->client->sendText($to, $message->getText(), $sessionId),
            'image'    => $this->client->sendImage($to, $payload['imageUrl'], $payload['caption'] ?? '', $sessionId),
            'document' => $this->client->sendDocument($to, $payload['documentUrl'], $payload['filename'] ?? '', $sessionId),
            'video'    => $this->client->sendVideo($to, $payload['videoUrl'], $payload['caption'] ?? '', $sessionId),
            'location' => $this->client->sendLocation($to, $payload['latitude'], $payload['longitude'], $payload['name'] ?? '', $payload['address'] ?? '', $sessionId),
            'contact'  => $this->client->sendContact($to, $payload['name'], $payload['phone'], $sessionId),
            'sticker'  => $this->client->sendSticker($to, $payload['stickerUrl'], $sessionId),
            'poll'     => $this->client->sendPoll($to, $payload['question'], $payload['options'], $payload['multipleAnswers'] ?? false, $sessionId),
            default    => $this->client->sendText($to, $message->getText(), $sessionId),
        };
    }

    // Resolves recipient phone following standard Laravel notification routing conventions.
    protected function resolveRecipient(mixed $notifiable, Notification $notification): ?string {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $routed = $notifiable->routeNotificationFor('affichat', $notification);
            if ($routed) {
                return $routed;
            }
            $routedWa = $notifiable->routeNotificationFor('whatsapp', $notification);
            if ($routedWa) {
                return $routedWa;
            }
        }

        if (isset($notifiable->phone_number)) {
            return (string)$notifiable->phone_number;
        }

        if (isset($notifiable->phone)) {
            return (string)$notifiable->phone;
        }

        return null;
    }
}
