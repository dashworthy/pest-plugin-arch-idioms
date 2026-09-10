<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Mail;

use Illuminate\Mail\Mailables\Content;

final class MarkdownMailable
{
    public function content(): Content
    {
        return new Content(markdown: 'mail.welcome');
    }
}
