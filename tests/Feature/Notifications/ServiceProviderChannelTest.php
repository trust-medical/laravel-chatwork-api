<?php

declare(strict_types=1);

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkChannel;

it('chatwork ショートネームドライバーを ChatworkChannel に解決する', function () {
    /** @var ChannelManager $manager */
    $manager = app(ChannelManager::class);

    $driver = $manager->driver('chatwork');

    expect($driver)->toBeInstanceOf(ChatworkChannel::class);
});

it('Notification ファサード経由でチャンネルを解決する', function () {
    $driver = Notification::channel('chatwork');

    expect($driver)->toBeInstanceOf(ChatworkChannel::class);
});
