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

namespace SRA\Repository;

use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Endpoint;
use SRA\Http\Endpoint as HttpEndpoint;
use SRA\Parts\Fact;
use React\Promise\PromiseInterface;

/**
 * Repository for managing facts from the SRA API.
 *
 * @since 0.3.0
 */
class FactsRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'bird' => HttpEndpoint::FACTS_BIRD,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Fact::class;

    /**
     * Fetch fact information by query parameters.
     *
     * @return PromiseInterface<ExCollectionInterface<Fact>|Fact[]>
     */
    public function bird(): PromiseInterface
    {
        return $this->sra_http->get(new Endpoint($this->endpoints['bird']))
            ->then(fn ($response) => $this->factory->part($this->class, (array) $response, true));
    }
}
