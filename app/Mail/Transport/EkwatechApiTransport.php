<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class EkwatechApiTransport extends AbstractTransport
{
    public function __construct(private readonly string $endpoint)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $recipients = array_map(
            fn ($address) => $address->getAddress(),
            $email->getTo(),
        );

        $response = Http::asMultipart()->post($this->endpoint, [
            'recipients' => $recipients,
            'subject' => (string) $email->getSubject(),
            // Text, not HTML: the Ekwatech sendmail API sends this as-is, no
            // HTML rendering on their end.
            'body' => $email->getTextBody() ?? $email->getHtmlBody() ?? '',
        ]);

        if ($response->failed()) {
            throw new TransportException("Échec de l'envoi via l'API Ekwatech (HTTP {$response->status()}) : {$response->body()}");
        }
    }

    public function __toString(): string
    {
        return 'ekwatech-api';
    }
}
