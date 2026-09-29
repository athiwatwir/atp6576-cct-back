<?php

namespace App\Providers;

use App\Services\Mail\MailService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        Mail::extend('smtp', function (array $config) {
            $scheme = $config['scheme'] ?? null;

            if (! $scheme) {
                $scheme = ((int) ($config['port'] ?? 0) === 465) ? 'smtps' : 'smtp';
            }

            $transport = (new EsmtpTransportFactory)->create(new Dsn(
                $scheme,
                $config['host'],
                $config['username'] ?? null,
                $config['password'] ?? null,
                isset($config['port']) ? (int) $config['port'] : null,
                $config,
            ));

            $peerName = $config['peer_name'] ?? null;

            if ($transport instanceof EsmtpTransport && is_string($peerName) && $peerName !== '') {
                $stream = $transport->getStream();
                $options = $stream->getStreamOptions();
                $options['ssl']['peer_name'] = $peerName;
                $options['ssl']['SNI_enabled'] = true;
                $stream->setStreamOptions($options);
            }

            return $transport;
        });
    }
}
