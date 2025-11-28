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

namespace SRA\Http;

use Discord\Http\EndpointInterface;
use Discord\Http\EndpointTrait;

class Endpoint implements EndpointInterface
{
    use EndpointTrait;

    // GET
    public const FACTS = 'facts';
    // GET
    public const FACTS_CAT = self::FACTS.'/cat';
    // GET
    public const FACTS_FOX = self::FACTS.'/fox';
    // GET
    public const FACTS_BIRB = self::FACTS.'/birb';
    // GET
    public const FACTS_PANDA = self::FACTS.'/panda';
    // GET
    public const FACTS_KOALA = self::FACTS.'/koala';
    // GET
    public const FACTS_KANGAROO = self::FACTS.'/kangaroo';
    // GET
    public const FACTS_RACOON = self::FACTS.'/racoon';
    // GET
    public const FACTS_GIRAFFE = self::FACTS.'/giraffe';
    // GET
    public const FACTS_WHALE = self::FACTS.'/whale';
    // GET
    public const FACTS_ELEPHANT = self::FACTS.'/elephant';
    // GET
    public const FACTS_DOG = self::FACTS.'/dog';
    // GET
    public const FACTS_BIRD = self::FACTS.'/bird';
    // GET
    public const FACTS_RED_PANDA = self::FACTS.'/red_panda';

    /**
     * Regex to identify parameters in endpoints.
     *
     * @var string
     */
    public const REGEX = '/:([^\/]*)/';

    /**
     * The string version of the endpoint, including all parameters.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Array of placeholders to be replaced in the endpoint.
     *
     * @var string[]
     */
    protected $vars = [];

    /**
     * Array of arguments to substitute into the endpoint.
     *
     * @var string[]
     */
    protected $args = [];

    /**
     * Array of query data to be appended
     * to the end of the endpoint with `http_build_query`.
     *
     * @var array
     */
    protected $query = [];

    /**
     * Creates an endpoint class.
     *
     * @param string $endpoint
     */
    public function __construct(string $endpoint)
    {
        $this->endpoint = $endpoint;

        if (preg_match_all(self::REGEX, $endpoint, $vars)) {
            $this->vars = $vars[1] ?? [];
        }
    }
}
