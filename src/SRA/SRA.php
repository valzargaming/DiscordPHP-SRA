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

use Discord\Discord;
use Discord\Http\Drivers\React;
use Discord\Stats;
use SRA\Http\Http;
use SRA\Repository\CardRepository;

/**
 * The SRA client class.
 *
 * @version 1.0.0
 *
 * @property CardRepository $cards
 * @property SetRepository  $sets
 */
class SRA extends Discord
{
    use HelperTrait;

    public const string GITHUB = 'https://github.com/discord-php/DiscordPHP-SRA';

    protected Stats $stats;

    /**
     * The extended HTTP client.
     *
     * @var Http Extended Discord HTTP client.
     */
    protected $sra_http;

    /**
     * The extended Client class.
     *
     * @var Client Extended Discord client.
     */
    protected $client;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->sra_http = new Http(
            'Bot '.$this->token,
            $this->loop,
            $this->options['logger'] ?? null,
            new React($this->loop, $options['socket_options'] ?? [])
        );
        $this->client = $this->factory->part(Client::class, (array) $this->client);
        $this->stats = Stats::new($this);
    }

    /**
     * Gets the SRA HTTP client.
     *
     * @return Http
     */
    public function getSRAHttpClient(): Http
    {
        return $this->sra_http;
    }

    /**
     * Handles dynamic get calls to the client.
     *
     * @param string $name Variable name.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        static $allowed = ['loop', 'options', 'logger', 'http', 'sra_http', 'application_commands'];

        if (in_array($name, $allowed)) {
            return $this->{$name};
        }

        if (null === $this->client) {
            return;
        }

        return $this->client->{$name};
    }
}
