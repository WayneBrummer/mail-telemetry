<?php

namespace Pace\MailTelemetry;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Pace\MailTelemetry\Events\EmailEvent;
use Pace\MailTelemetry\Models\Email;

class Telemetry implements \Swift_Events_SendListener
{
    protected $hash;

    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();

        $this->createTrackers($message);
    }

    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();

        if (config('mail.driver') === 'ses') {
            $this->updateSesMessageId($message);
        }
        $this->updateNotificationId($message);
    }

    public static function decryptUrl($url)
    {
        // Replace "$" with "$"
        return Crypt::decryptString(\str_replace('/', '$', $url));
    }

    public static function cryptUrl($url)
    {
        // Replace "/" with "$"
        return \str_replace('/', '$', Crypt::encryptString($url));
    }

    protected function updateSesMessageId($message)
    {
        // Get the Email object
        $headers    = $message->getHeaders();
        $hash       = optional($headers->get('X-Mailer-Hash'))->getFieldBody();

        // Get info about the message-id from SES
        if ($email = Email::where('hash', $hash)->first()) {
            $email->message_id = $headers->get('X-SES-Message-ID')->getFieldBody() ?? null;
            $email->save();
        }
    }

    protected function updateNotificationId($message)
    {
        // Get the Email object
        $headers    = $message->getHeaders();
        $hash       = optional($headers->get('X-Mailer-Hash'))->getFieldBody();

        // Get notification ID From header.
        if ($email = Email::where('hash', $hash)->first()) {
            $email->notification_id = optional($headers->get('X-Email-Notification-ID'))
                ->getFieldBody();
            $email->save();
        }
    }

    protected function addTrackers($html, $hash)
    {
        if (config('mail-telemetry.pixel')) {
            $html = $this->injectTrackingPixel($html, $hash);
        }

        if (config('mail-telemetry.links')) {
            $html = $this->injectTrackingLink($html, $hash);
        }

        return $html;
    }

    protected function injectTrackingPixel($html, $hash)
    {
        $pixel     = '<img border=0 width=1 alt="" height=1 src="' . route('pixel_route', [$hash]) . '" />';

        $linebreak = Str::random(32);

        $html      = \str_replace("\n", $linebreak, $html);

        if (\preg_match('/^(.*<body[^>]*>)(.*)$/', $html, $matches)) {
            $html = $matches[1] . $matches[2] . $pixel;
        } else {
            $html = $html . $pixel;
        }

        $html = \str_replace($linebreak, "\n", $html);

        return $html;
    }

    protected function injectTrackingLink($html, $hash)
    {
        $this->hash = $hash;
        $html       = \preg_replace_callback(
            "/(<a[^>]*href=['\"])([^'\"]*)/",
            [$this, 'inject_link_callback'],
            $html
        );

        return $html;
    }

    protected function inject_link_callback($matches)
    {
        if (empty($matches[2])) {
            $url = app()->make('url')->to('/');
        } else {
            $url = \str_replace('&amp;', '&', $matches[2]);
        }

        return $matches[1] . route(
            'link_route',
            ['hash' => $this->hash, 'url' => $this->cryptUrl($url)]
        );
    }

    protected function createTrackers($message)
    {
        foreach ($message->getTo() as $to_email => $to_name) {
            foreach ($message->getFrom() as $from_email => $from_name) {
                $headers = $message->getHeaders();

                if ($headers->get('X-No-Track')) {
                    // Don't send with this header
                    $headers->remove('X-No-Track');
                    // Don't track this email
                    continue;
                }

                do {
                    $hash = Str::random(32);
                    $used = Email::where('hash', $hash)->count();
                } while ($used > 0);

                $headers->addTextHeader('X-Mailer-Hash', $hash);
                $subject          = $message->getSubject();
                $original_content = $message->getBody();

                $acceptedContentTypes = [
                    'text/html', 'multipart/alternative', 'multipart/mixed',
                ];

                if (\in_array($message->getContentType(), $acceptedContentTypes, true) && $original_content) {
                    $message->setBody($this->addTrackers($original_content, $hash));
                }

                foreach ($message->getChildren() as $part) {
                    if (\strpos($part->getContentType(), 'text/html') === 0) {
                        $part->setBody($this->addTrackers($original_content, $hash));
                    }
                }

                $tracker = Email::create([
                    'hash'       => $hash,
                    'headers'    => $headers->toString(),
                    'sender'     => $from_name . ' <' . $from_email . '>',
                    'recipient'  => $to_name . ' <' . $to_email . '>',
                    'subject'    => $subject,
                    'content'    => config('mail-telemetry.log-content', true) ?
                    (\strlen($original_content) > 65535 ? \substr($original_content, 0, 65532)
                    . '...' : $original_content) : null,
                    'opens'      => 0,
                    'clicks'     => 0,
                    'message_id' => $message->getId(),
                    'meta'       => [],
                ]);

                event(new EmailEvent($tracker));
            }
        }
    }
}
