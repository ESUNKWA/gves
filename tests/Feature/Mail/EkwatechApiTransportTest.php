<?php

namespace Tests\Feature\Mail;

use App\Mail\TenantAdminWelcomeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EkwatechApiTransportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'ekwatech',
            'mail.mailers.ekwatech.endpoint' => 'https://sendmail.ekwatech.test/api/send-mail',
        ]);
    }

    public function test_it_posts_recipients_subject_and_text_body_as_multipart_to_the_configured_endpoint(): void
    {
        Http::fake([
            'sendmail.ekwatech.test/*' => Http::response(['status' => 'ok'], 200),
        ]);

        Mail::to('admin@example.test')->send(new TenantAdminWelcomeMail(
            'Acme',
            'https://acme.test/reset-password/token?email=admin@example.test',
            'admin@example.test',
        ));

        Http::assertSent(function ($request) {
            $parts = collect($request->data());

            $recipients = $parts->where('name', 'recipients[]')->pluck('contents');
            $subject = $parts->firstWhere('name', 'subject')['contents'] ?? null;
            $body = $parts->firstWhere('name', 'body')['contents'] ?? null;

            return $request->url() === 'https://sendmail.ekwatech.test/api/send-mail'
                && $request->isMultipart()
                && $recipients->contains('admin@example.test')
                && $subject === 'Votre espace ' . config('app.name') . '-Acme est prêt'
                && str_contains($body, 'https://acme.test/reset-password/token')
                && ! str_contains($body, '<html'); // plain text, not the HTML part
        });
    }

    public function test_it_throws_when_the_api_responds_with_an_error(): void
    {
        Http::fake([
            'sendmail.ekwatech.test/*' => Http::response('server error', 500),
        ]);

        $this->expectException(TransportException::class);

        Mail::to('admin@example.test')->send(new TenantAdminWelcomeMail(
            'Acme',
            'https://acme.test/reset-password/token?email=admin@example.test',
            'admin@example.test',
        ));
    }
}
