<?php

declare(strict_types=1);

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkChannel;

it('resolves the chatwork short-name driver to a ChatworkChannel', function () {
    /** @var ChannelManager $manager */
    $manager = app(ChannelManager::class);

    $driver = $manager->driver('chatwork');

    expect($driver)->toBeInstanceOf(ChatworkChannel::class);
});

it('resolves the channel through the Notification facade', function () {
    $driver = Notification::channel('chatwork');

    expect($driver)->toBeInstanceOf(ChatworkChannel::class);
});
