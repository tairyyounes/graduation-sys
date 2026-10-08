<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('smtp', function (array $config = []) {
            $transport = new EsmtpTransport(
                $config['host'] ?? 'smtp.gmail.com',
                (int) ($config['port'] ?? 587),
                false
            );

            if (!empty($config['username'])) {
                $transport->setUsername($config['username']);
            }

            if (!empty($config['password'])) {
                $transport->setPassword($config['password']);
            }

            $stream = $transport->getStream();
            if (is_object($stream) && method_exists($stream, 'setStreamOptions')) {
                $stream->setStreamOptions([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ]);
            }

            return $transport;
        });
    }
}
