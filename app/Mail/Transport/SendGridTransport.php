<?php

namespace App\Mail\Transport;

use SendGrid;
use SendGrid\Mail\Mail as SendGridMail;
use Stringable;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class SendGridTransport implements Stringable, TransportInterface
{
    public function __construct(
        protected string $apiKey,
        protected bool $verifySsl = true
    ) {}

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $envelope = $envelope ?? Envelope::create($message);

        if (! $message instanceof Email) {
            throw new \InvalidArgumentException('SendGrid transport only supports Symfony\Component\Mime\Email instances.');
        }

        $sgMail = new SendGridMail();

        $from = $this->firstAddress($message->getFrom());
        $fromName = $this->firstAddressName($message->getFrom());
        if (! $from) {
            $sender = $envelope->getSender();
            $from = $sender ? $sender->getAddress() : config('mail.from.address');
            $fromName = $sender ? $sender->getName() : config('mail.from.name');
        }
        $sgMail->setFrom($from, $fromName ?? '');

        $toList = $message->getTo();
        if (empty($toList)) {
            foreach ($envelope->getRecipients() as $recipient) {
                $sgMail->addTo($recipient->getAddress(), $recipient->getName());
            }
        } else {
            foreach ($toList as $addr) {
                $sgMail->addTo(
                    $addr instanceof Address ? $addr->getAddress() : (string) $addr,
                    $addr instanceof Address ? $addr->getName() : ''
                );
            }
        }

        $sgMail->setSubject($message->getSubject() ?? '');

        $html = $message->getHtmlBody();
        $text = $message->getTextBody();
        if ($text) {
            $sgMail->addContent('text/plain', $text);
        }
        if ($html) {
            $sgMail->addContent('text/html', $html);
        }
        if (! $text && ! $html) {
            $sgMail->addContent('text/plain', '(No content)');
        }

        $replyTo = $this->firstAddress($message->getReplyTo());
        if ($replyTo) {
            $sgMail->setReplyTo($replyTo);
        }

        $options = [];
        if (! $this->verifySsl) {
            $options['verify_ssl'] = false;
        }
        $sendgrid = new SendGrid($this->apiKey, $options);
        $response = $sendgrid->send($sgMail);

        if ($response->statusCode() >= 400) {
            throw new \RuntimeException(
                'SendGrid API error ('.$response->statusCode().'): '.$response->body()
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

        return $first instanceof Address ? $first->getAddress() : (string) $first;
    }

    protected function firstAddressName(array $addresses): ?string
    {
        if (empty($addresses)) {
            return null;
        }
        $first = $addresses[0];

        return $first instanceof Address ? $first->getName() : null;
    }

    public function __toString(): string
    {
        return 'sendgrid';
    }
}
