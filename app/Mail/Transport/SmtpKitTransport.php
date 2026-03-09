<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Stringable;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class SmtpKitTransport implements Stringable, TransportInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $apiUrl = 'https://smtpkit.com/api/v1/send-email',
        protected bool $verifySsl = true
    ) {}

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $envelope = $envelope ?? Envelope::create($message);

        if (! $message instanceof Email) {
            throw new \InvalidArgumentException('SmtpKit transport only supports Symfony\Component\Mime\Email instances.');
        }

        $from = $this->firstAddress($message->getFrom());
        if (! $from) {
            $sender = $envelope->getSender();
            $from = $sender ? $sender->getAddress() : config('mail.from.address');
        }

        $to = $this->firstAddress($message->getTo());
        if (! $to) {
            $recipients = $envelope->getRecipients();
            $to = $recipients[0] ? $recipients[0]->getAddress() : null;
        }

        if (! $to) {
            throw new \InvalidArgumentException('SmtpKit API requires at least one recipient.');
        }

        $payload = [
            'to' => $to,
            'from' => $from,
            'subject' => $message->getSubject() ?? '',
            'html' => $message->getHtmlBody(),
            'text' => $message->getTextBody(),
        ];

        $replyTo = $this->firstAddress($message->getReplyTo());
        if ($replyTo) {
            $payload['replyTo'] = $replyTo;
        }

        // API requires at least one of html or text
        if (empty($payload['html']) && empty($payload['text'])) {
            $payload['text'] = '(No content)';
        }
        if (empty($payload['html'])) {
            unset($payload['html']);
        }
        if (empty($payload['text'])) {
            unset($payload['text']);
        }

        $http = Http::withToken($this->apiKey)->timeout(15);
        if (! $this->verifySsl) {
            $http = $http->withOptions(['verify' => false]);
        }
        $response = $http->post($this->apiUrl, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'SmtpKit API error ('.$response->status().'): '.$response->body()
            );
        }

        return new SentMessage($message, $envelope);
    }

    protected function firstAddress(array $addresses): ?string
    {
        if (empty($addresses)) {
            return null;
        }
        $first = $addresses[0];

        return $first instanceof \Symfony\Component\Mime\Address
            ? $first->getAddress()
            : (string) $first;
    }

    public function __toString(): string
    {
        return 'smtpkit';
    }
}
