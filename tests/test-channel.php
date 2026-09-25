<?php
/**
 * Standalone test runner for Laravel Notification Channel (affidev/affichat).
 * Run via: php integrations/affichat-laravel/tests/test-channel.php
 */

// Mock Illuminate Notification base class for standalone testing
namespace Illuminate\Notifications {
    class Notification {}
}

namespace GuzzleHttp\Exception {
    class GuzzleException extends \Exception {}
}

namespace GuzzleHttp {
    interface ClientInterface {
        public function request(string $method, $uri = '', array $options = []): \Psr\Http\Message\ResponseInterface;
    }
}

namespace Psr\Http\Message {
    interface StreamInterface {
        public function getContents(): string;
    }
    interface ResponseInterface {
        public function getStatusCode(): int;
        public function getBody(): StreamInterface;
    }
}

namespace AffiChat\Laravel\Tests {

use AffiChat\Laravel\Channels\AffiChatChannel;
use AffiChat\Laravel\Clients\AffiChatClient;
use AffiChat\Laravel\Exceptions\CouldNotSendNotification;
use AffiChat\Laravel\Messages\AffiChatMessage;
use GuzzleHttp\ClientInterface;
use Illuminate\Notifications\Notification;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

// Load package source files
require_once __DIR__ . '/../src/Exceptions/CouldNotSendNotification.php';
require_once __DIR__ . '/../src/Messages/AffiChatMessage.php';
require_once __DIR__ . '/../src/Clients/AffiChatClient.php';
require_once __DIR__ . '/../src/Channels/AffiChatChannel.php';
require_once __DIR__ . '/../src/Webhook/AffiChatWebhook.php';

class MockStream implements StreamInterface {
    private string $content;
    public function __construct(string $content) { $this->content = $content; }
    public function getContents(): string { return $this->content; }
}

class MockResponse implements ResponseInterface {
    private int $status;
    private string $body;
    public function __construct(int $status, string $body) {
        $this->status = $status;
        $this->body   = $body;
    }
    public function getStatusCode(): int { return $this->status; }
    public function getBody(): StreamInterface { return new MockStream($this->body); }
}

class MockHttpClient implements ClientInterface {
    public array $history = [];
    public ?\Closure $handler = null;

    public function request(string $method, $uri = '', array $options = []): ResponseInterface {
        $this->history[] = [
            'method'  => $method,
            'uri'     => (string)$uri,
            'options' => $options,
        ];

        if ($this->handler) {
            return ($this->handler)($method, $uri, $options);
        }

        return new MockResponse(200, json_encode(['status' => 'success', 'id' => 'msg_lar_123']));
    }
}

// Mock Notifiable with routeNotificationFor
class UserWithRouting {
    public function routeNotificationFor(string $channel, $notification = null): ?string {
        if ($channel === 'affichat') {
            return '081234567890';
        }
        return null;
    }
}

// Mock Notifiable with whatsapp routing
class UserWithWhatsappRouting {
    public function routeNotificationFor(string $channel, $notification = null): ?string {
        if ($channel === 'whatsapp') {
            return '081299990000';
        }
        return null;
    }
}

// Mock Notifiable with phone_number property
class UserWithPhoneNumberProperty {
    public string $phone_number = '081311112222';
}

// Mock Notifiable with phone property
class UserWithPhoneProperty {
    public string $phone = '081433334444';
}

// Mock Notifiable with no phone
class UserWithoutPhone {}

// Sample Notification classes
class OrderPlacedNotification extends Notification {
    public function toAffiChat($notifiable): AffiChatMessage {
        return AffiChatMessage::create('Halo! Pesanan #1001 Anda telah diterima.')
            ->sessionId('session_store');
    }
}

class InvoiceDocumentNotification extends Notification {
    public function toAffiChat($notifiable): AffiChatMessage {
        return AffiChatMessage::create()
            ->document('https://domain.com/inv-1001.pdf', 'Invoice-1001.pdf')
            ->sessionId('session_billing');
    }
}

class PollNotification extends Notification {
    public function toAffiChat($notifiable): AffiChatMessage {
        return AffiChatMessage::create()
            ->poll('Pilih ekspedisi pengiriman:', ['JNE', 'SiCepat', 'J&T'], false);
    }
}

class PlainStringNotification extends Notification {
    public function toAffiChat($notifiable): string {
        return 'Pemberitahuan teks sederhana langsung dari string.';
    }
}

// Test Runner
$total = 0;
$passed = 0;
$failed = 0;

function it(string $title, bool $condition): void {
    global $total, $passed, $failed;
    $total++;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$title}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$title}\n";
    }
}

echo "=================================================================\n";
echo "   AFFICHAT LARAVEL NOTIFICATION CHANNEL TEST SUITE\n";
echo "=================================================================\n\n";

// SUITE 1: AffiChatMessage Fluent Builder
echo "Suite 1: AffiChatMessage Fluent Builder\n";
$msg = AffiChatMessage::create('Pesan teks')
    ->to('081234567890')
    ->sessionId('my_session');

it('Builds text message type', $msg->getType() === 'text');
it('Stores message text', $msg->getText() === 'Pesan teks');
it('Stores explicit recipient', $msg->getTo() === '081234567890');
it('Stores custom session ID', $msg->getSessionId() === 'my_session');

$imgMsg = AffiChatMessage::create()->image('https://site.com/pic.jpg', 'Foto Produk');
it('Builds image message type', $imgMsg->getType() === 'image');
it('Stores image URL and caption', $imgMsg->getPayload()['imageUrl'] === 'https://site.com/pic.jpg' && $imgMsg->getPayload()['caption'] === 'Foto Produk');

$docMsg = AffiChatMessage::create()->document('https://site.com/doc.pdf', 'invoice.pdf');
it('Builds document message type', $docMsg->getType() === 'document');
it('Stores document URL and filename', $docMsg->getPayload()['documentUrl'] === 'https://site.com/doc.pdf' && $docMsg->getPayload()['filename'] === 'invoice.pdf');

$pollMsg = AffiChatMessage::create()->poll('Survei Kepuasan', ['Puas', 'Cukup', 'Kurang'], false);
it('Builds poll message type', $pollMsg->getType() === 'poll');
it('Stores poll question and options', $pollMsg->getPayload()['question'] === 'Survei Kepuasan' && count($pollMsg->getPayload()['options']) === 3);

// SUITE 2: Phone Normalization
echo "\nSuite 2: Phone Normalizer in AffiChatClient\n";
it('Converts 08123456789 -> 628123456789', AffiChatClient::normalizePhone('08123456789') === '628123456789');
it('Converts +628123456789 -> 628123456789', AffiChatClient::normalizePhone('+628123456789') === '628123456789');
it('Converts 00628123456789 -> 628123456789', AffiChatClient::normalizePhone('00628123456789') === '628123456789');
it('Converts (0812) 3456-7890 -> 6281234567890', AffiChatClient::normalizePhone('(0812) 3456-7890') === '6281234567890');
it('Rejects empty phone', AffiChatClient::normalizePhone('') === null);
it('Rejects too short phone (< 9 digits)', AffiChatClient::normalizePhone('1234567') === null);

// SUITE 3: AffiChatClient Endpoint Calls & Headers
echo "\nSuite 3: AffiChatClient HTTP Dispatches & Headers\n";
$mockHttp = new MockHttpClient();
$client = new AffiChatClient('secret_api_key_456', 'default_sess', 15, $mockHttp);

$client->sendText('081234567890', 'Testing Client Text');
$lastCall = end($mockHttp->history);
it('sendText hits /api/send-text', $lastCall['uri'] === '/api/send-text');
it('sendText sends x-api-key header', $lastCall['options']['headers']['x-api-key'] === 'secret_api_key_456');
it('sendText payload has normalized phone', $lastCall['options']['json']['to'] === '6281234567890');
it('sendText payload has default sessionId', $lastCall['options']['json']['sessionId'] === 'default_sess');

$client->sendImage('081234567890', 'https://img.com/a.png', 'Caption Test', 'custom_sess');
$lastCall = end($mockHttp->history);
it('sendImage hits /api/send-image', $lastCall['uri'] === '/api/send-image');
it('sendImage overrides sessionId', $lastCall['options']['json']['sessionId'] === 'custom_sess');
it('sendImage payload contains imageUrl & caption', $lastCall['options']['json']['imageUrl'] === 'https://img.com/a.png');

$client->sendDocument('081234567890', 'https://file.com/doc.pdf', 'receipt.pdf');
$lastCall = end($mockHttp->history);
it('sendDocument hits /api/send-document', $lastCall['uri'] === '/api/send-document');
it('sendDocument payload contains filename and document_name', $lastCall['options']['json']['filename'] === 'receipt.pdf' && $lastCall['options']['json']['document_name'] === 'receipt.pdf');

$client->sendContact('081234567890', 'Budi Santoso', '085712345678');
$lastCall = end($mockHttp->history);
it('sendContact hits /api/send-contact', $lastCall['uri'] === '/api/send-contact');
it('sendContact payload contains contactName and contactNumber', $lastCall['options']['json']['contactName'] === 'Budi Santoso' && $lastCall['options']['json']['contactNumber'] === '6285712345678');

$client->sendLocation('081234567890', -6.2088, 106.8456, 'Kantor AffiChat', 'Jl. Sudirman');
$lastCall = end($mockHttp->history);
it('sendLocation hits /api/send-location', $lastCall['uri'] === '/api/send-location');
it('sendLocation payload contains coordinates and name', $lastCall['options']['json']['latitude'] === -6.2088 && $lastCall['options']['json']['name'] === 'Kantor AffiChat');

$client->sendVideo('081234567890', 'https://vid.com/promo.mp4', 'Promo Video');
$lastCall = end($mockHttp->history);
it('sendVideo hits /api/send-video', $lastCall['uri'] === '/api/send-video');
it('sendVideo payload contains videoUrl & caption', $lastCall['options']['json']['videoUrl'] === 'https://vid.com/promo.mp4');

$client->sendPoll('081234567890', 'Warna?', ['Merah', 'Biru']);
$lastCall = end($mockHttp->history);
it('sendPoll hits /api/send-poll', $lastCall['uri'] === '/api/send-poll');
it('sendPoll payload contains question & options', $lastCall['options']['json']['question'] === 'Warna?');

// SUITE 4: AffiChatChannel Recipient Resolution
echo "\nSuite 4: AffiChatChannel Recipient Resolution\n";
$channel = new AffiChatChannel($client);

// 1. routeNotificationFor('affichat')
$mockHttp->history = [];
$channel->send(new UserWithRouting(), new OrderPlacedNotification());
$call = end($mockHttp->history);
it('Resolves phone via routeNotificationFor(\'affichat\')', $call['options']['json']['to'] === '6281234567890');

// 2. routeNotificationFor('whatsapp')
$mockHttp->history = [];
$channel->send(new UserWithWhatsappRouting(), new OrderPlacedNotification());
$call = end($mockHttp->history);
it('Resolves phone via routeNotificationFor(\'whatsapp\') fallback', $call['options']['json']['to'] === '6281299990000');

// 3. $notifiable->phone_number
$mockHttp->history = [];
$channel->send(new UserWithPhoneNumberProperty(), new OrderPlacedNotification());
$call = end($mockHttp->history);
it('Resolves phone via $notifiable->phone_number property', $call['options']['json']['to'] === '6281311112222');

// 4. $notifiable->phone
$mockHttp->history = [];
$channel->send(new UserWithPhoneProperty(), new OrderPlacedNotification());
$call = end($mockHttp->history);
it('Resolves phone via $notifiable->phone property', $call['options']['json']['to'] === '6281433334444');

// 5. Missing recipient exception
$missingCaught = false;
try {
    $channel->send(new UserWithoutPhone(), new OrderPlacedNotification());
} catch (CouldNotSendNotification $e) {
    $missingCaught = true;
}
it('Throws CouldNotSendNotification when recipient phone is missing', $missingCaught);

// SUITE 5: Channel Message Type Dispatching
echo "\nSuite 5: Channel Message Type Dispatching\n";
$user = new UserWithRouting();

// Document notification
$mockHttp->history = [];
$channel->send($user, new InvoiceDocumentNotification());
$call = end($mockHttp->history);
it('Channel dispatches document notification to /api/send-document', $call['uri'] === '/api/send-document');

// Poll notification
$mockHttp->history = [];
$channel->send($user, new PollNotification());
$call = end($mockHttp->history);
it('Channel dispatches poll notification to /api/send-poll', $call['uri'] === '/api/send-poll');

// Plain string notification
$mockHttp->history = [];
$channel->send($user, new PlainStringNotification());
$call = end($mockHttp->history);
it('Channel wraps plain string in AffiChatMessage and dispatches text', $call['uri'] === '/api/send-text' && $call['options']['json']['text'] === 'Pemberitahuan teks sederhana langsung dari string.');

// SUITE 6: Webhook Signature Verification & Payload Parsing
echo "\nSuite 6: Webhook Signature Verification & Payload Parsing\n";
$secret = 'webhook_secret_laravel_123';
$payloadRaw = json_encode([
    'event' => 'messages.upsert',
    'sessionId' => 'laravel_session',
    'timestamp' => '2026-09-24T00:00:00.000Z',
    'data' => [
        'id' => 'msg_laravel_99',
        'from' => '6281234567890@s.whatsapp.net',
        'message' => ['conversation' => 'Konfirmasi pembayaran order #123'],
    ],
]);
$validSig = hash_hmac('sha256', $payloadRaw, $secret);

it('AffiChatWebhook::verifySignature accepts valid signature', \AffiChat\Laravel\Webhook\AffiChatWebhook::verifySignature($payloadRaw, $validSig, $secret));
it('AffiChatWebhook::verifySignature rejects invalid signature', !\AffiChat\Laravel\Webhook\AffiChatWebhook::verifySignature($payloadRaw, 'tampered_signature', $secret));
it('AffiChatWebhook::verifySignature rejects wrong secret', !\AffiChat\Laravel\Webhook\AffiChatWebhook::verifySignature($payloadRaw, $validSig, 'wrong_secret'));

$parsed = \AffiChat\Laravel\Webhook\AffiChatWebhook::parsePayload($payloadRaw);
it('AffiChatWebhook::parsePayload decodes event', ($parsed['event'] ?? '') === 'messages.upsert');
it('AffiChatWebhook::parsePayload decodes sessionId', ($parsed['sessionId'] ?? '') === 'laravel_session');
it('AffiChatWebhook::parsePayload decodes message id', ($parsed['data']['id'] ?? '') === 'msg_laravel_99');

// Summary
echo "\n=================================================================\n";
echo "SUMMARY: Total Asserts: {$total} | Passed: {$passed} | Failed: {$failed}\n";
echo "=================================================================\n";

exit($failed === 0 ? 0 : 1);
}
