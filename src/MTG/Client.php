<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP-SRA project.
 *
 * Copyright (c) 2025-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace SRA;

use Discord\Parts\User\Client as DiscordClient;
use Discord\Repository\EmojiRepository;
use Discord\Repository\GuildRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Repository\SoundRepository;
use Discord\Repository\UserRepository;
use SRA\Repository\CardRepository;
use SRA\Repository\SetRepository;

class Client extends DiscordClient
{
    /**
     * @inheritDoc
     */
    protected $repositories = [
        'emojis' => EmojiRepository::class,
        'guilds' => GuildRepository::class,
        'private_channels' => PrivateChannelRepository::class,
        'sounds' => SoundRepository::class,
        'users' => UserRepository::class,
        'cards' => CardRepository::class,
        'sets' => SetRepository::class,
    ];
}
