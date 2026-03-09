<?php

namespace App\Mail\Transport;

use Mailgun\Mailgun;
use Stringable;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class MailgunTransport implements Stringable, TransportInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $domain,
        protected string $endpoint = 'https://api.mailgun.net'
    ) {}

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $envelope = $envelope ?? Envelope::create($message);

        if (! $message instanceof Email) {
            throw new \InvalidArgumentException('Mailgun transport only supports Symfony\Component\Mime\Email instances.');
        }

        $from = $this->formatAddress($message->getFrom());
        if (! $from) {
            $sender = $envelope->getSender();
            $from = $sender
                ? $this->formatAddressString($sender->getAddress(), $sender->getName())
                : config('mail.from.name').' <'.config('mail.from.address').'>';
        }

        $toList = $message->getTo();
        if (empty($toList)) {
            $toParts = [];
            foreach ($envelope->getRecipients() as $recipient) {
                $toParts[] = $this->formatAddressString($recipient->getAddress(), $recipient->getName());
            }
            $to = implode(', ', $toParts);
        } else {
            $toParts = [];
            foreach ($toList as $addr) {
                $toParts[] = $addr instanceof Address
                    ? $this->formatAddressString($addr->getAddress(), $addr->getName())
                    : (string) $addr;
            }
            $to = implode(', ', $toParts);
        }

        if (empty($to)) {
            throw new \InvalidArgumentException('Mailgun requires at least one recipient.');
        }

        $params = [
            'from' => $from,
            'to' => $to,
            'subject' => $message->getSubject() ?? '',
        ];

        $text = $message->getTextBody();
        $html = $message->getHtmlBody();
        if ($text) {
            $params['text'] = $text;
        }
        if ($html) {
            $params['html'] = $html;
        }
        if (! $text && ! $html) {
            $params['text'] = '(No content)';
        }

        $replyTo = $this->firstAddress($message->getReplyTo());
        if ($replyTo) {
            $params['h:Reply-To'] = $replyTo;
        }

        $mg = Mailgun::create($this->apiKey, $this->endpoint);
        $mg->messages()->send($this->domain, $params);

        return new SentMessage($message, $envelope);
    }

    protected function formatAddress(array $addresses): ?string
    {
        if (empty($addresses)) {
            return null;
        }
        $first = $addresses[0];

        return $first instanceof Address
            ? $this->formatAddressString($first->getAddress(), $first->getName())
            : (string) $first;
    }

    protected function formatAddressString(string $email, string $name = ''): string
    {
        $name = trim($name);
        if ($name === '') {
            return $email;
        }

        return $name.' <'.$email.'>';
    }

    protected function firstAddress(array $addresses): ?string
    {
        if (empty($addresses)) {
            return null;
        }
        $first = $addresses[0];

        return $first instanceof Address ? $first->getAddress() : (string) $first;
    }

    public function __toString(): string
    {
        return 'mailgun';
    }
}
