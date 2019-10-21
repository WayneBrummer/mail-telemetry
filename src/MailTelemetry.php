<?php

namespace Qit\MailTracker;

use Illuminate\Support\Str;
use Qit\MailTracker\Events\EmailEvent;
use Qit\MailTracker\Models\Email;
use Qit\MailTracker\Models\EmailTelemetry;

class MailTracker implements \Swift_Events_SendListener
{
    protected $hash;

    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();
        $this->createTrackers($message);
        $this->purgeOldRecords();
    }

    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        if (config('mail.driver') === 'ses') {
            $message = $evt->getMessage();
            $this->updateSesMessageId($message);
        }
    }

    public static function hash_url($url)
    {
        // Replace "/" with "$"
        return \str_replace('/', '$', \base64_encode($url));
    }

    protected function updateSesMessageId($message)
    {
        // Get the Email object
        $headers    = $message->getHeaders();

        $hash       = optional($headers->get('X-Mailer-Hash'))->getFieldBody();

        // Get info about the message-id from SES
        if ($email = Email::where('hash', $hash)->first()) {
            $email->message_id = $headers->get('X-SES-Message-ID')->getFieldBody();
            $email->save();
        }
    }

    protected function addTrackers($html, $hash)
    {
        // if (config('mail-tracker.pixel')) {
        //     $html = $this->injectTrackingPixel($html, $hash);
        // }

        if (config('mail-tracker.links')) {
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
        $html       = \preg_replace_callback("/(<a[^>]*href=['\"])([^'\"]*)/", [$this, 'inject_link_callback'], $html);

        return $html;
    }

    protected function inject_link_callback($matches)
    {
        if (empty($matches[2])) {
            $url = app()->make('url')->to('/');
        } else {
            $url = \str_replace('&amp;', '&', $matches[2]);
        }

        return $matches[1] . route('link_route', ['hash' => $this->hash, 'url' => \urlencode($url)]);
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
                    'content'    => config('mail-tracker.log-content', true) ? (\strlen($original_content) > 65535 ? \substr($original_content, 0, 65532) . '...' : $original_content) : null,
                    'opens'      => 0,
                    'clicks'     => 0,
                    'message_id' => $message->getId(),
                    'meta'       => [],
                ]);

                event(new EmailEvent($tracker));
            }
        }
    }

    /**
     * Purge old records in the database.
     */
    protected function purgeOldRecords()
    {
        if (config('mail-tracker.expire-days') > 0) {
            $emails = Email::where('created_at', '<', \Carbon\Carbon::now()
                ->subDays(config('mail-tracker.expire-days')))
                ->select('id')
                ->get();

            EmailTelemetry::whereIn('sent_email_id', $emails->pluck('id'))->delete();
            Email::whereIn('id', $emails->pluck('id'))->delete();
        }
    }
}