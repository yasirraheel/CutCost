<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->is('en/userlogin') || $request->is('en/register')) {
                $target = $request->is('en/register') ? '/en/register' : '/en/userlogin';

                return redirect($target)
                    ->withInput($request->except('password'))
                    ->withErrors(['email' => 'Your session expired. Please submit the form again.']);
            }

            return null;
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() === 419 && ($request->is('en/userlogin') || $request->is('en/register'))) {
                $target = $request->is('en/register') ? '/en/register' : '/en/userlogin';

                return redirect($target)
                    ->withInput($request->except('password'))
                    ->withErrors(['email' => 'Your session expired. Please submit the form again.']);
            }

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
