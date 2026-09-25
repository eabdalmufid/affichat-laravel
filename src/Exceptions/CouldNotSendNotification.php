<?php

namespace AffiChat\Laravel\Exceptions;

use Exception;

class CouldNotSendNotification extends Exception {
    public static function serviceRespondedWithAnError(int $statusCode, string $responseBody): self {
        return new self("AffiChat Gateway responded with code {$statusCode}: {$responseBody}");
    }

    public static function couldNotCommunicateWithAffiChat(\Throwable $exception): self {
        return new self("Could not communicate with AffiChat Gateway: {$exception->getMessage()}", 0, $exception);
    }

    public static function missingRecipient(): self {
        return new self('Notification recipient does not have a phone number or routeNotificationFor(\'affichat\').');
    }

    public static function invalidPhoneNumber(string $phone): self {
        return new self("Phone number '{$phone}' is invalid for WhatsApp dispatch.");
    }
}
