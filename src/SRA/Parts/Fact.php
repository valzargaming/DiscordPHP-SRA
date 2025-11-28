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

namespace SRA\Parts;

use Discord\Parts\Part;

/**
 * A fact returned by the SRA API.
 *
 * @property string $fact The fact.
 *
 * @since 0.1.0
 */
class Fact extends Part
{
    protected $fillable = [
        'fact',
    ];
}
