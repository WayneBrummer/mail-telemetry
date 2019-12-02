<?php

namespace Pace\MailTelemetry\Http\Controllers;

use App\Http\Controllers\Controller;
use Pace\MailTelemetry\Models\Email;
use Spatie\QueryBuilder\QueryBuilder;

class MailController extends Controller
{
    /**
     * Protected by middleware and by permission guard.
     */
    public function index()
    {
        return QueryBuilder::for(Email::class)
            ->defaultSort('id')
            ->allowedFilters(['recipient', 'subject', 'opens', 'clicks'])
            ->allowedAppends(['urltelemetry'])
            ->jsonPaginate(
                config('mail-telemetry.emails-per-page', [])
            );
    }
}
