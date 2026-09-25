<?php

namespace AffiChat\Laravel\Messages;

class AffiChatMessage {
    protected string $type = 'text';
    protected ?string $to = null;
    protected ?string $sessionId = null;
    protected string $text = '';
    protected array $payload = [];

    public function __construct(string $text = '') {
        $this->text = $text;
    }

    public static function create(string $text = ''): self {
        return new self($text);
    }

    public function text(string $text): self {
        $this->type = 'text';
        $this->text = $text;
        return $this;
    }

    public function to(string $to): self {
        $this->to = $to;
        return $this;
    }

    public function sessionId(string $sessionId): self {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function image(string $imageUrl, string $caption = ''): self {
        $this->type = 'image';
        $this->payload = [
            'imageUrl' => $imageUrl,
            'caption'  => $caption,
        ];
        return $this;
    }

    public function document(string $documentUrl, string $filename = ''): self {
        $this->type = 'document';
        $this->payload = [
            'documentUrl' => $documentUrl,
            'filename'    => $filename,
        ];
        return $this;
    }

    public function video(string $videoUrl, string $caption = ''): self {
        $this->type = 'video';
        $this->payload = [
            'videoUrl' => $videoUrl,
            'caption'  => $caption,
        ];
        return $this;
    }

    public function location(float $latitude, float $longitude, string $name = '', string $address = ''): self {
        $this->type = 'location';
        $this->payload = [
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'name'      => $name,
            'address'   => $address,
        ];
        return $this;
    }

    public function contact(string $name, string $phone): self {
        $this->type = 'contact';
        $this->payload = [
            'name'  => $name,
            'phone' => $phone,
        ];
        return $this;
    }

    public function sticker(string $stickerUrl): self {
        $this->type = 'sticker';
        $this->payload = [
            'stickerUrl' => $stickerUrl,
        ];
        return $this;
    }

    public function poll(string $question, array $options, bool $multiple = false): self {
        $this->type = 'poll';
        $this->payload = [
            'question'        => $question,
            'options'         => array_values($options),
            'multipleAnswers' => $multiple,
        ];
        return $this;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getTo(): ?string {
        return $this->to;
    }

    public function getSessionId(): ?string {
        return $this->sessionId;
    }

    public function getPayload(): array {
        return $this->payload;
    }

    public function toArray(): array {
        return array_merge([
            'type'      => $this->type,
            'to'        => $this->to,
            'sessionId' => $this->sessionId,
            'text'      => $this->text,
        ], $this->payload);
    }
}
