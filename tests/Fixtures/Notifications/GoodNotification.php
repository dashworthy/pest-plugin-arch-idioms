<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Notifications;

final class GoodNotification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
