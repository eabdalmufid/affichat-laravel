# AffiChat Laravel Notification Channel

Official WhatsApp Notification Channel and PHP Client for Laravel using the **[AffiChat Gateway](https://chat.affidev.com)**.

Connects directly and securely to **`https://chat.affidev.com`** without requiring any manual server domain configuration.

---

## 🚀 Fitur Utama

- **Laravel Package Auto-Discovery**: Otomatis terdaftar di Laravel 9, 10, 11, dan 12 tanpa perlu mengubah `config/app.php`.
- **Integrasi Bawaan Laravel Notification**: Cukup return `AffiChatMessage` di method `toAffiChat($notifiable)`.
- **Fluent Message Builder**: Mendukung pesan teks, gambar, dokumen/PDF, video, lokasi GPS, vCard kontak, stiker, dan polling.
- **Resolusi Penerima Otomatis**: Mendukung `routeNotificationForAffiChat()`, `routeNotificationForWhatsApp()`, atau property `$user->phone_number` / `$user->phone`.
- **Normalisasi Nomor Telepon**: Format lokal (`0812...`, `+62...`, spasi, strip) otomatis disanitasi menjadi format standar `628...`.
- **Facade Cepat (`AffiChat`)**: Kirim pesan langsung dari Controller atau Queue Job tanpa membuat class Notifikasi.

---

## 📦 Instalasi

Tambahkan paket ke proyek Laravel Anda via Composer:

```bash
composer require affidev/affichat
```

*(Opsional)* Publikasikan file konfigurasi `config/affichat.php`:

```bash
php artisan vendor:publish --tag=affichat-config
```

---

## ⚙️ Konfigurasi Environment (`.env`)

Buka file `.env` proyek Laravel Anda dan tambahkan kredensial AffiChat:

```env
AFFICHAT_API_KEY=your_api_key_from_dashboard
AFFICHAT_SESSION_ID=default
AFFICHAT_TIMEOUT=15
```

> **Catatan:** Seluruh pengiriman otomatis mengarah ke `https://chat.affidev.com`. Anda tidak perlu dan tidak bisa mengubah domain server gateway.

---

## 📖 Cara Penggunaan

### 1. Menyiapkan Model Notifiable (misal: `User.php`)

Tambahkan method `routeNotificationForAffiChat` pada model Anda (atau pastikan model memiliki kolom `phone_number` / `phone`):

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use Notifiable;

    /**
     * Tentukan nomor WhatsApp tujuan untuk channel AffiChat.
     */
    public function routeNotificationForAffiChat($notification): ?string {
        return $this->phone_number; // e.g. "081234567890"
    }
}
```

---

### 2. Membuat Class Notifikasi

Buat class notifikasi baru menggunakan Artisan:

```bash
php artisan make:notification OrderStatusNotification
```

Edit file `app/Notifications/OrderStatusNotification.php`:

```php
namespace App\Notifications;

use AffiChat\Laravel\Messages\AffiChatMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification {
    protected $order;

    public function __construct($order) {
        $this->order = $order;
    }

    public function via($notifiable): array {
        return ['affichat'];
    }

    public function toAffiChat($notifiable): AffiChatMessage {
        return AffiChatMessage::create("Halo {$notifiable->name}, pesanan #{$this->order->id} telah berhasil diproses!")
            ->sessionId('default');
    }
}
```

Kirim notifikasi seperti biasa:

```php
$user->notify(new OrderStatusNotification($order));
```

---

### 3. Ragam Tipe Pesan (`AffiChatMessage`)

#### A. Pesan Gambar dengan Caption
```php
public function toAffiChat($notifiable): AffiChatMessage {
    return AffiChatMessage::create()
        ->image('https://tokosaya.com/promo.jpg', 'Katalog Promo Mingguan');
}
```

#### B. Mengirim Invoice Dokumen / PDF
```php
public function toAffiChat($notifiable): AffiChatMessage {
    return AffiChatMessage::create()
        ->document('https://tokosaya.com/invoice-1024.pdf', 'Invoice-1024.pdf');
}
```

#### C. Mengirim Lokasi Toko / Cabang
```php
public function toAffiChat($notifiable): AffiChatMessage {
    return AffiChatMessage::create()
        ->location(-6.200000, 106.816666, 'AffiChat Headquarter', 'Jakarta Selatan');
}
```

#### D. Mengirim Polling Interaktif
```php
public function toAffiChat($notifiable): AffiChatMessage {
    return AffiChatMessage::create()
        ->poll('Apakah pesanan Anda telah sampai?', ['Sudah, Sangat Puas', 'Sudah, Ada Kendala', 'Belum'], false);
}
```

---

### 4. Mengirim Pesan Instan via Facade `AffiChat`

Jika Anda ingin mengirim pesan langsung di dalam Controller atau Event Listener tanpa membuat class Notification:

```php
use AffiChat\Laravel\Facades\AffiChat;

// Kirim teks
AffiChat::sendText('081234567890', 'Kode OTP Anda adalah: 582910');

// Kirim gambar
AffiChat::sendImage('081234567890', 'https://example.com/banner.png', 'Promo Spesial');

// Cek status API Key
$status = AffiChat::checkApiKey();
```

---

### 5. Menangani Webhook Masuk (Signature Verification)

AffiChat Gateway mengirimkan notifikasi event masuk (`messages.upsert`, `session.status`) dengan tanda tangan kriptografi HMAC-SHA256 pada header `X-AffiChat-Signature`:

```php
use Illuminate\Http\Request;
use AffiChat\Laravel\Webhook\AffiChatWebhook;

Route::post('/webhook/affichat', function (Request $request) {
    $signature = $request->header('X-AffiChat-Signature');
    $secret = config('affichat.webhook_secret'); // atau env('AFFICHAT_WEBHOOK_SECRET')

    // Verifikasi tanda tangan kriptografi
    if (!AffiChatWebhook::verifySignature($request->getContent(), $signature, $secret)) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    $payload = AffiChatWebhook::parsePayload($request->getContent());
    $event = $payload['event'] ?? '';

    if ($event === 'messages.upsert') {
        $message = $payload['data'];
        // Proses pesan masuk...
    }

    return response()->json(['status' => 'success']);
});
```

---

## 🧪 Pengujian Mandiri (CLI Test)

Modul ini dilengkapi dengan runner pengujian mandiri tanpa memerlukan instalasi Laravel penuh:

```powershell
php integrations/affichat-laravel/tests/test-channel.php
```

---

## 📄 Lisensi

MIT License.
