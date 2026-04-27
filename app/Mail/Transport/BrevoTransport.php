<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Stringable;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\RawMessage;

class BrevoTransport implements Stringable, TransportInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $apiUrl = 'https://api.brevo.com/v3/smtp/email',
        protected bool $verifySsl = true
    ) {}

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $envelope = $envelope ?? Envelope::create($message);

        if (! $message instanceof Email) {
            throw new \InvalidArgumentException('Brevo transport only supports Symfony\Component\Mime\Email instances.');
        }

        $fromAddress = $this->firstAddress($message->getFrom());
        $fromName = $this->firstAddressName($message->getFrom());
        if (! $fromAddress) {
            $sender = $envelope->getSender();
            $fromAddress = $sender ? $sender->getAddress() : config('mail.from.address');
            $fromName = $sender ? $sender->getName() : config('mail.from.name');
        }

        $toRecipients = $this->buildRecipients($message->getTo());
        if (empty($toRecipients)) {
            foreach ($envelope->getRecipients() as $recipient) {
                $toRecipients[] = $this->formatRecipient($recipient->getAddress(), $recipient->getName());
            }
        }

        if (empty($toRecipients)) {
            throw new \InvalidArgumentException('Brevo API requires at least one recipient.');
        }

        $payload = [
            'sender' => $this->formatRecipient($fromAddress, $fromName),
            'to' => $toRecipients,
            'subject' => $message->getSubject() ?? '',
        ];

        $html = $message->getHtmlBody();
        $text = $message->getTextBody();
        if ($html) {
            $payload['htmlContent'] = $html;
        }
        if ($text) {
            $payload['textContent'] = $text;
        }
        if (! $html && ! $text) {
            $payload['textContent'] = '(No content)';
        }

        $replyTo = $this->firstAddressObject($message->getReplyTo());
        if ($replyTo) {
            $payload['replyTo'] = $this->formatRecipient($replyTo->getAddress(), $replyTo->getName());
        }

        $ccRecipients = $this->buildRecipients($message->getCc());
        if (! empty($ccRecipients)) {
            $payload['cc'] = $ccRecipients;
        }

        $bccRecipients = $this->buildRecipients($message->getBcc());
        if (! empty($bccRecipients)) {
            $payload['bcc'] = $bccRecipients;
        }

        $attachments = $this->buildAttachments($message);
        if (! empty($attachments)) {
            $payload['attachment'] = $attachments;
        }

        $http = Http::withHeaders([
            'api-key' => $this->apiKey,
            'accept' => 'application/json',
        ])->timeout(20);

        if (! $this->verifySsl) {
            $http = $http->withOptions(['verify' => false]);
        }

        $response = $http->post($this->apiUrl, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Brevo API error ('.$response->status().'): '.$response->body()
            );
        }

        return new SentMessage($message, $envelope);
    }

    /**
     * @param  array<int, Address|string>  $addresses
     * @return array<int, array{email:string,name?:string}>
     */
    protected function buildRecipients(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $address) {
            if ($address instanceof Address) {
                $result[] = $this->formatRecipient($address->getAddress(), $address->getName());
                continue;
            }
            $result[] = $this->formatRecipient((string) $address, '');
        }

        return $result;
    }

    /**
     * @return array{email:string,name?:string}
     */
    protected function formatRecipient(string $email, ?string $name = null): array
    {
        $recipient = ['email' => $email];
        $name = trim((string) $name);
        if ($name !== '') {
            $recipient['name'] = $name;
        }

        return $recipient;
    }

    /**
     * @return array<int, array{name:string,content:string}>
     */
    protected function buildAttachments(Email $message): array
    {
        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            if (! $attachment instanceof DataPart) {
                continue;
            }

            $body = $attachment->getBody();
            if (is_resource($body)) {
                $body = stream_get_contents($body) ?: '';
            }

            if (! is_string($body)) {
                continue;
            }

            $attachments[] = [
                'name' => $attachment->getFilename() ?: 'attachment',
                'content' => base64_encode($body),
            ];
        }

        return $attachments;
    }

    /**
     * @param  array<int, Address|string>  $addresses
     */
    protected function firstAddress(array $addresses): ?string
    {
        if (empty($addresses)) {
            return null;
        }

        $first = $addresses[0];

        return $first instanceof Address ? $first->getAddress() : (string) $first;
    }

    /**
     * @param  array<int, Address|string>  $addresses
     */
    protected function firstAddressName(array $addresses): ?string
    {
        if (empty($addresses)) {
            return null;
        }

        $first = $addresses[0];

        return $first instanceof Address ? $first->getName() : null;
    }

    /**
     * @param  array<int, Address|string>  $addresses
     */
    protected function firstAddressObject(array $addresses): ?Address
    {
        if (empty($addresses)) {
            return null;
        }

        $first = $addresses[0];
        if ($first instanceof Address) {
            return $first;
        }

        $address = trim((string) $first);
        if ($address === '') {
            return null;
        }

        return new Address($address);
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
