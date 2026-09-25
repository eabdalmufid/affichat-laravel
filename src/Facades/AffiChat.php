<?php

namespace AffiChat\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendText(string $to, string $text, ?string $sessionId = null)
 * @method static array sendImage(string $to, string $imageUrl, string $caption = '', ?string $sessionId = null)
 * @method static array sendDocument(string $to, string $documentUrl, string $filename = '', ?string $sessionId = null)
 * @method static array sendVideo(string $to, string $videoUrl, string $caption = '', ?string $sessionId = null)
 * @method static array sendLocation(string $to, float $latitude, float $longitude, string $name = '', string $address = '', ?string $sessionId = null)
 * @method static array sendContact(string $to, string $name, string $phone, ?string $sessionId = null)
 * @method static array sendSticker(string $to, string $stickerUrl, ?string $sessionId = null)
 * @method static array sendPoll(string $to, string $question, array $options, bool $multiple = false, ?string $sessionId = null)
 * @method static array checkApiKey()
 * @method static ?string normalizePhone(string $phone)
 *
 * @see \AffiChat\Laravel\Clients\AffiChatClient
 */
class AffiChat extends Facade {
    protected static function getFacadeAccessor(): string {
        return 'affichat';
    }
}
