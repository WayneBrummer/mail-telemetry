<?php

namespace Pace\MailTelemetry\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Pace\MailTelemetry\Events\EmailEvent;
use Pace\MailTelemetry\Exceptions\IncorrectLink;
use Pace\MailTelemetry\Models\Email;
use Pace\MailTelemetry\Models\EmailTelemetry;
use Pace\MailTelemetry\Telemetry;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class MailTelemetryController extends Controller
{
    /**
     * Protected by middleware and by permission guard.
     */
    public function index()
    {
        return QueryBuilder::for(EmailTelemetry::class)
            ->defaultSort('id')
            ->jsonPaginate(
                config('mail-telemetry.emails-per-page', [])
            );
    }

    public function getPixel($hash)
    {
        $pixel = \base64_decode(
            'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==',
            true
        );

        $response = Response::make($pixel, 200);

        $response->header('Content-type', 'image/png');
        $response->header('Content-Length', 42);
        $response->header('Cache-Control', 'private, no-cache, no-cache=Set-Cookie, proxy-revalidate');
        // $response->header('Expires', 'Wed, 11 Jan 2000 12:59:00 GMT');
        $response->header('Last-Modified', 'Wed, 15 oct 2019 12:59:00 GMT');
        $response->header('Pragma', 'no-cache');

        $tracker = Email::where('hash', $hash)->first();

        if ($tracker) {
            ++$tracker->opens;
            $tracker->save();

            Event::dispatch(new EmailEvent($tracker));
        }

        return $response;
    }

    public function getLinks($hash, $url)
    {
        $url = Telemetry::decryptUrl($url);
        if (\filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new IncorrectLink('Mail hash: ' . $hash);
        }

        return $this->linkClicked($url, $hash);
    }

    public function getClicked(Request $request)
    {
        $url  = $request->l;
        $hash = $request->h;

        return $this->linkClicked($url, $hash);
    }

    protected function linkClicked($url, $hash)
    {
        $tracker = Email::where('hash', $hash)->first();

        if ($tracker) {
            $tracker->increment('clicks');
            $emailTelemetry = EmailTelemetry::where('url', $url)->where('hash', $hash)->first();

            if ($emailTelemetry) {
                $emailTelemetry->increment('clicks');
            } else {
                $data = [
                    'sent_email_id' => $tracker->id,
                    'url'           => $url,
                    'hash'          => $tracker->hash,
                ];
                EmailTelemetry::create($data);
            }

            Event::dispatch(new EmailEvent($tracker));

            return redirect($url);
        }

        throw new IncorrectLink('Mail hash: ' . $hash);
    }
}
