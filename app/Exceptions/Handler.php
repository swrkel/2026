<?php

namespace App\Exceptions;

use App\Mail\ExceptionOccured;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    public function report(Throwable $exception)
    {
        /* ==================================================================
         * IS2114 TEMPORARY DIAGNOSTIC - REMOVE ONCE THE CAUSE IS FOUND
         * ==================================================================
         *
         * MPCS pages time out after 900 seconds for ordinary users, while a
         * superadmin is unaffected. The log shows the failure ending inside
         * spatie's PermissionDoesNotExist, but it never records WHICH
         * permission was being looked for, WHICH page was being loaded, or
         * WHERE the request was at the time - so there is nothing to act on.
         *
         * Every permission the MPCS pages check passes instantly when tested
         * from the command line against the same user and the same tenant
         * database, so the name that fails is one not yet identified.
         *
         * The two blocks below add exactly that missing information.
         *
         * Everything here only WRITES TO THE LOG. No behaviour changes, and
         * nothing is suppressed - the original reporting still runs below.
         *
         * To remove: delete from this comment down to the marker that says
         * END IS2114 DIAGNOSTIC.
         */

        // 1. The missing permission, by name, with the code that asked for it.
        if ($exception instanceof \Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            try {
                Log::error('IS2114 PERMISSION MISSING: ' . $exception->getMessage(), [
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                    'username' => optional(auth()->user())->username,
                    'called_from' => collect($exception->getTrace())
                        ->take(15)
                        ->map(function ($frame) {
                            return ($frame['file'] ?? '?') . ':' . ($frame['line'] ?? '?')
                                . '  ' . ($frame['class'] ?? '') . ($frame['type'] ?? '')
                                . ($frame['function'] ?? '');
                        })
                        ->all(),
                ]);
            } catch (Throwable $ignore) {
                // Diagnostics must never break the request.
            }
        }

        /*
         * 2. The 900-second timeout, with the page and the position in the
         *    code. These entries already appear in the log but carry no URL
         *    and no trace, which is why they have not been actionable.
         *
         *    If this fires but block 1 never does, the permission exception is
         *    NOT being thrown - it is simply where PHP happened to be when the
         *    limit was reached, and the real problem is a loop elsewhere.
         */
        if ($exception instanceof \Symfony\Component\ErrorHandler\Error\FatalError
            && str_contains($exception->getMessage(), 'Maximum execution time')) {
            try {
                Log::error('IS2114 TIMEOUT DETAIL', [
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'user_id' => auth()->id(),
                    'username' => optional(auth()->user())->username,
                    'died_at' => $exception->getFile() . ':' . $exception->getLine(),
                    'stack' => collect($exception->getTrace())
                        ->take(20)
                        ->map(function ($frame) {
                            return ($frame['file'] ?? '?') . ':' . ($frame['line'] ?? '?')
                                . '  ' . ($frame['class'] ?? '') . ($frame['type'] ?? '')
                                . ($frame['function'] ?? '');
                        })
                        ->all(),
                ]);
            } catch (Throwable $ignore) {
                // Diagnostics must never break the request.
            }
        }
        /* ================ END IS2114 DIAGNOSTIC ========================== */

        if ($this->shouldReport($exception) && config('app.env') == 'demo') {
            $this->sendEmail($exception);
        }

        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        $exceptionClass = get_class($exception);

        if (str_starts_with($exceptionClass, 'Stancl\\Tenancy\\Exceptions\\')) {
            Log::warning('Tenancy request rejected', [
                'exception' => $exceptionClass,
                'message' => $exception->getMessage(),
                'host' => $request->getHost(),
                'path' => $request->path(),
                'central_domains' => array_values(array_filter(
                    (array) config('tenancy.central_domains', [])
                )),
            ]);
        }

        /*
         * Do not convert a POST MethodNotAllowed exception into a redirect to
         * the GET version of the same URL. That old behavior made a missing
         * POST /login route silently reload the login page and look as though
         * the Sign In button did nothing. Laravel's normal 405 response is the
         * correct and diagnosable behavior.
         */
        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest($request->getSchemeAndHttpHost() . '/login');
    }

    public function sendEmail(Throwable $exception)
    {
        try {
            $e = FlattenException::create($exception);
            $handler = new HtmlErrorRenderer();
            $html = $handler->getHtml($e);
            $email = config('mail.username');

            if (! empty($email)) {
                Mail::to($email)->send(new ExceptionOccured($html));
            }
        } catch (Throwable $ex) {
            dd($ex);
        }
    }
}
