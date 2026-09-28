<?php

namespace App\Listeners;

use App\Services\Messaging\GlobalEmailService;
use Illuminate\Mail\Events\MessageSending;

/**
 * Automatically applies the mandatory global document header/footer to every
 * outgoing Laravel email, including queued mail.
 */
class ApplyGlobalDocumentChromeToOutgoingMail
{
    /** @var GlobalEmailService */
    private $email;

    public function __construct(GlobalEmailService $email)
    {
        $this->email = $email;
    }

    /** @return void */
    public function handle(MessageSending $event)
    {
        try {
            $message = $event->message;
            $subject = method_exists($message, 'getSubject') ? $message->getSubject() : null;
            $context = array_merge((array) ($event->data ?? []), [
                'page_title' => $subject ?: 'Email',
                'page_no' => 1,
            ]);

            // Symfony MIME (current Laravel mail transport).
            if (method_exists($message, 'getHtmlBody') && method_exists($message, 'html')) {
                $html = (string) $message->getHtmlBody();
                if ($html !== '') {
                    $message->html($this->email->decorateHtml($html, $context));
                }
            }

            if (method_exists($message, 'getTextBody') && method_exists($message, 'text')) {
                $text = (string) $message->getTextBody();
                if ($text !== '') {
                    $message->text($this->email->decorateText($text, $context));
                }
            }

            // SwiftMailer compatibility for older installations.
            if (
                ! method_exists($message, 'getHtmlBody')
                && method_exists($message, 'getBody')
                && method_exists($message, 'setBody')
            ) {
                $body = (string) $message->getBody();
                if ($body !== '') {
                    $isHtml = method_exists($message, 'getContentType')
                        && stripos((string) $message->getContentType(), 'html') !== false;
                    $message->setBody(
                        $this->email->decorate($body, $context, $isHtml),
                        $isHtml ? 'text/html' : 'text/plain'
                    );
                }
            }
        } catch (\Throwable $exception) {
            // Email delivery must continue even when metadata is unavailable.
            report($exception);
        }
    }
}
