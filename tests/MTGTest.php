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

use Discord\Helpers\ExCollectionInterface;
use SRA\SRA;
use SRA\Parts\Card;
use PHPUnit\Framework\TestCase;

final class SRATest extends TestCase
{
    public function testCardInfoRetrieval()
    {
        wait(function (SRA $sra, $resolve) {
            /** @var Card $card */
            $card = $sra->getFactory()->part(Card::class);
            $card->setPageSize(1);
            $sra->cards->getCards(['name' => 'Black Lotus'])->then(function (ExCollectionInterface $cards) {
                $this->assertInstanceOf(ExCollectionInterface::class, $cards);
                $this->assertInstanceOf(Card::class, $cards->first());
            })->then($resolve, $resolve);
        }, 10);
    }
}
