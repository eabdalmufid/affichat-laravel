# AffiChat Laravel Notification Channel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/affidev/affichat.svg?color=00A884)](https://packagist.org/packages/affidev/affichat)
[![Total Downloads](https://img.shields.io/packagist/dt/affidev/affichat.svg)](https://packagist.org/packages/affidev/affichat)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Channel notifikasi WhatsApp dan klien PHP resmi untuk framework Laravel menggunakan [AffiChat Gateway](https://chat.affidev.com).

Paket ini menghubungkan aplikasi Laravel langsung ke server gateway AffiChat tanpa memerlukan konfigurasi domain manual tambahan.

---

## Fitur Utama

- **Laravel Package Auto-Discovery**: Otomatis terdaftar pada Laravel 9, 10, 11, dan 12 tanpa registrasi manual di `config/app.php`.
- **Integrasi Notification Channel**: Mengembalikan objek `AffiChatMessage` pada method `toAffiChat($notifiable)`.
- **Fluent Message Builder**: Mendukung pesan teks, gambar dengan caption, dokumen/PDF, video, lokasi GPS, vCard kontak, stiker, dan polling interaktif.
- **Resolusi Penerima Otomatis**: Mendukung method `routeNotificationForAffiChat()`, `routeNotificationForWhatsApp()`, atau atribut `$user->phone_number` / `$user->phone`.
- **Sanitasi Nomor Otomatis**: Format lokal (`08...`, `+62...`, karakter spasi, dan tanda hubung) otomatis dikonversi ke format standar E.164 (`628...`).
- **Facade Klien (`AffiChat`)**: Kirim pesan langsung dari Controller, Service, atau Queue Job tanpa membuat class Notification terpisah.
- **Verifikasi Webhook HMAC-SHA256**: Helper terpadu untuk memvalidasi keaslian payload event webhook masuk.

---

## Instalasi

Tambahkan paket ke proyek Laravel melalui Composer:

```bash
composer require affidev/affichat
```

*(Opsional)* Publikasikan file konfigurasi `config/affichat.php`:

```bash
php artisan vendor:publish --tag=affichat-config
```

---

## Konfigurasi Environment

Tambahkan variabel berikut ke file `.env` proyek Laravel Anda:

```env
AFFICHAT_API_KEY=your_api_key_here
AFFICHAT_SESSION_ID=default
AFFICHAT_TIMEOUT=15
AFFICHAT_WEBHOOK_SECRET=your_webhook_secret_here
```

---

## Cara Penggunaan

### 1. Menyiapkan Model Notifiable

Tambahkan routing nomor tujuan pada model yang mengimplementasikan trait `Notifiable` (misalnya `User.php`):

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForAffiChat($notification): ?string
    {
        return $this->phone_number;
    }
}
```

---

### 2. Membuat Class Notifikasi

Generate class notifikasi baru menggunakan Artisan:

```bash
php artisan make:notification OrderStatusNotification
```

Buka dan sesuaikan isi file `app/Notifications/OrderStatusNotification.php`:

```php
namespace App\Notifications;

use AffiChat\Laravel\Messages\AffiChatMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        return ['affichat'];
    }

    public function toAffiChat($notifiable): AffiChatMessage
    {
        return AffiChatMessage::create("Halo {$notifiable->name}, pesanan #{$this->order->id} telah berhasil diproses.")
            ->sessionId('default');
    }
}
```

Kirim notifikasi melalui instance model:

```php
$user->notify(new OrderStatusNotification($order));
```

---

### 3. Ragam Tipe Pesan (`AffiChatMessage`)

#### Gambar dengan Caption
```php
public function toAffiChat($notifiable): AffiChatMessage
{
    return AffiChatMessage::create()
        ->image('https://tokosaya.com/promo.jpg', 'Katalog Promo Mingguan');
}
```

#### Dokumen atau Faktur PDF
```php
public function toAffiChat($notifiable): AffiChatMessage
{
    return AffiChatMessage::create()
        ->document('https://tokosaya.com/invoice-1024.pdf', 'Invoice-1024.pdf');
}
```

#### Lokasi GPS
```php
public function toAffiChat($notifiable): AffiChatMessage
{
    return AffiChatMessage::create()
        ->location(-6.200000, 106.816666, 'AffiChat Headquarter', 'Jakarta Selatan');
}
```

#### Polling Interaktif
```php
public function toAffiChat($notifiable): AffiChatMessage
{
    return AffiChatMessage::create()
        ->poll('Apakah pesanan Anda telah sampai?', ['Sudah, Sangat Puas', 'Sudah, Ada Kendala', 'Belum'], false);
}
```

---

### 4. Mengirim Pesan Langsung via Facade `AffiChat`

Kirim pesan langsung tanpa membuat class Notification:

```php
use AffiChat\Laravel\Facades\AffiChat;

AffiChat::sendText('081234567890', 'Kode verifikasi Anda adalah 582910.');

AffiChat::sendImage('081234567890', 'https://tokosaya.com/banner.png', 'Promo Spesial');

$keyStatus = AffiChat::checkApiKey();
```

---

### 5. Memproses Webhook Masuk (HMAC Signature Verification)

Server AffiChat menyertakan tanda tangan HMAC-SHA256 pada header `X-AffiChat-Signature` untuk setiap event masuk:

```php
use Illuminate\Http\Request;
use AffiChat\Laravel\Webhook\AffiChatWebhook;

Route::post('/webhook/affichat', function (Request $request) {
    $signature = $request->header('X-AffiChat-Signature');
    $secret    = config('affichat.webhook_secret');

    // Body mentah wajib digunakan untuk memvalidasi digest HMAC-SHA256
    if (!AffiChatWebhook::verifySignature($request->getContent(), $signature, $secret)) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    $payload = AffiChatWebhook::parsePayload($request->getContent());
    $event   = $payload['event'] ?? '';

    if ($event === 'messages.upsert') {
        $message = $payload['data'];
    }

    return response()->json(['status' => 'success']);
});
```

---

## Pengujian Mandiri

Paket ini menyediakan skrip pengujian mandiri tanpa memerlukan instalasi penuh aplikasi Laravel:

```bash
php integrations/affichat-laravel/tests/test-channel.php
```

---

## Lisensi

MIT License.
