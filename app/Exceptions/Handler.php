<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
    }

    public function render($request, Throwable $e): Response
    {
        if ($e instanceof TokenMismatchException) {
            Auth::logout();

            return redirect()->route('landing')
                ->with('auth_error', 'Tu sesión expiró, vuelve a iniciar sesión.');
        }

        if ($e instanceof MethodNotAllowedHttpException && $request->is('logout')) {
            return redirect()->route('landing');
        }

        return parent::render($request, $e);
    }
}
