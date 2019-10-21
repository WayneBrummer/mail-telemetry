<?php

namespace Pace\MailTelemetry;

use App\Http\Controllers\Controller;
use Pace\MailTelemetry\Events\EmailEvent;
use Pace\MailTelemetry\Models\Email;
use Pace\MailTelemetry\Models\EmailTelemetry;
use Spatie\QueryBuilder\QueryBuilder;

class MailTrackerController extends Controller
{
    /**
     * Undocumented function.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return QueryBuilder::for(EmailTelemetry::class)
            ->defaultSort('id')
            ->jsonPaginate();
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
        $url = \base64_decode(\str_replace('$', '/', $url), true);
        if (\filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new BadUrlLink('Mail hash: ' . $hash);
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
            ++$tracker->clicks;
            $tracker->save();
            $url = EmailTelemetry::where('url', $url)->where('hash', $hash)->first();

            if ($url) {
                ++$url->clicks;
                $url->save();
            } else {
                $url = EmailTelemetry::create([
                    'sent_email_id' => $tracker->id,
                    'url'           => $url,
                    'hash'          => $tracker->hash,
                ]);
            }

            Event::dispatch(new EmailEvent($tracker));

            return redirect($url);
        }

        throw new BadUrlLink('Mail hash: ' . $hash);
    }
}
