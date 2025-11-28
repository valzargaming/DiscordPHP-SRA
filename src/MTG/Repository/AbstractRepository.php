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

use Discord\Repository\AbstractRepository as DiscordAbstractRepository;
use SRA\SRA;

/**
 * Repositories provide a way to store and update parts on the Discord server.
 *
 * @author Valithor Obsidion <valithor@discordphp.org>
 */
abstract class AbstractRepository extends DiscordAbstractRepository
{
    use AbstractRepositoryTrait;

    /**
     * AbstractRepository constructor.
     *
     * @param SRA   $sra
     * @param array $vars An array of variables used for the endpoint.
     */
    public function __construct(protected $sra, array $vars = [])
    {
        parent::__construct($sra, $vars);
        $this->sra_http = $sra->getSRAHttpClient();
    }
}
