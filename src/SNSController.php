<?php

namespace Pace\MailTelemetry;

use Aws\Sns\Message as SNSMessage;
use Aws\Sns\MessageValidator as SNSMessageValidator;
use Event;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Pace\MailTelemetry\Events\MessageBouncedPermanentlyEvent;
use Pace\MailTelemetry\Model\Email;

class SNSController extends Controller
{
    public function callback(Request $request)
    {
        if (config('app.env') !== 'production' && $request->message) {
            // phpunit cannot mock static methods so without making a facade
            // for SNSMessage we have to pass the json data in $request->message
            $message = new SNSMessage(\json_decode($request->message, true));
        } else {
            $message   = SNSMessage::fromRawPostData();
            $validator = new SNSMessageValidator();
            $validator->validate($message);
        }
        // If we have a topic defined, make sure this is that topic
        if (config('mail-tracker.sns-topic') && $message->offsetGet('TopicArn') !== config('mail-tracker.sns-topic')) {
            return 'invalid topic ARN';
        }

        switch ($message->offsetGet('Type')) {
            case 'SubscriptionConfirmation':
                return $this->confirm_subscription($message);
            case 'Notification':
                return $this->process_notification($message);
        }
    }

    public function process_bounce($message)
    {
        $sent_email = Email::where('message_id', $message->mail->messageId)->first();
        if ($sent_email) {
            $meta          = collect($sent_email->meta);
            $current_codes = [];
            if ($meta->has('failures')) {
                $current_codes = $meta->get('failures');
            }
            foreach ($message->bounce->bouncedRecipients as $failure_details) {
                $current_codes[] = $failure_details;
            }
            $meta->put('failures', $current_codes);
            $meta->put('success', false);
            $sent_email->meta = $meta;
            $sent_email->save();
        }
    }

    public function process_complaint($message)
    {
        $message_id = $message->mail->messageId;
        $sent_email = Email::where('message_id', $message_id)->first();
        if ($sent_email) {
            $meta = collect($sent_email->meta);
            $meta->put('complaint', true);
            $meta->put('success', false);
            $meta->put('complaint_time', $message->complaint->timestamp);
            if (!empty($message->complaint->complaintFeedbackType)) {
                $meta->put('complaint_type', $message->complaint->complaintFeedbackType);
            }
            $sent_email->meta = $meta;
            $sent_email->save();
        }
    }

    protected function confirm_subscription($message)
    {
        $client = new Guzzle();
        $client->get($message->offsetGet('SubscribeURL'));

        return 'subscription confirmed';
    }

    protected function process_notification($message)
    {
        $message = \json_decode($message->offsetGet('Message'));
        switch ($message->notificationType) {
            case 'Delivery':
                $this->process_delivery($message);

                break;
            case 'Bounce':
                $this->process_bounce($message);
                if ($message->bounce->bounceType === 'Permanent') {
                    foreach ($message->bounce->bouncedRecipients as $recipient) {
                        Event::dispatch(new MessageBouncedPermanentlyEvent($recipient->emailAddress));
                    }
                }

                break;
            case 'Complaint':
                $this->process_complaint($message);
                foreach ($message->complaint->complainedRecipients as $recipient) {
                    Event::dispatch(new MessageBouncedPermanentlyEvent($recipient->emailAddress));
                }

                break;
        }

        return 'notification processed';
    }

    protected function process_delivery($message)
    {
        $sent_email = Email::where('message_id', $message->mail->messageId)->first();
        if ($sent_email) {
            $meta = collect($sent_email->meta);
            $meta->put('smtpResponse', $message->delivery->smtpResponse);
            $meta->put('success', true);
            $meta->put('delivered_at', $message->delivery->timestamp);
            $sent_email->meta = $meta;
            $sent_email->save();
        }
    }
}
