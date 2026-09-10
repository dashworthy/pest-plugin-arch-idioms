<?php

use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Notifications\EmptyChannelsNotification;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Notifications\GoodNotification;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Notifications\NoViaNotification;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use PHPUnit\Framework\AssertionFailedError;

it('accepts a notification declaring at least one channel', function () {
    expect(GoodNotification::class)->toDeclareNotificationChannels();
});

it('rejects a notification with no via method', function () {
    expect(fn () => expect(NoViaNotification::class)->toDeclareNotificationChannels()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares no via() method');
});

it('rejects a notification returning no channels', function () {
    expect(fn () => expect(EmptyChannelsNotification::class)->toDeclareNotificationChannels()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'returns an empty channel list');
});

it('accepts a whole namespace of notifications once the undeclared ones are excluded', function () {
    expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Notifications')
        ->classes()
        ->toDeclareNotificationChannels()
        ->ignoring([NoViaNotification::class, EmptyChannelsNotification::class]);
});

it('rejects the same namespace without the exclusions, proving they are load-bearing', function () {
    expect(fn () => expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Notifications')
        ->classes()
        ->toDeclareNotificationChannels()
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'Expecting the notification to declare its delivery channels');
});

it('points a missing via() method at a non-zero line', function () {
    $captured = null;

    try {
        expect(NoViaNotification::class)->toDeclareNotificationChannels()->ensureLazyExpectationIsVerified();
    } catch (ArchExpectationFailedException $e) {
        $captured = $e;
    }

    expect($captured)->not->toBeNull()
        ->and($captured->toCollisionEditor()->getLine())->not->toBe(0);
});
