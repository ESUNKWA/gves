<?php

namespace App\Providers;

use App\Mail\Transport\EkwatechApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

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
        // Switched off the shared LWS SMTP mailbox (mail.ekwatech.com): despite
        // correct SPF/DKIM/DMARC, transactional emails with a call-to-action
        // link/button were still landing in spam for some recipients — the
        // internal Ekwatech sendmail API doesn't have that shared-IP reputation
        // problem. See config/mail.php's "ekwatech" mailer.
        Mail::extend('ekwatech', function (array $config) {
            return new EkwatechApiTransport($config['endpoint']);
        });
    }
}
