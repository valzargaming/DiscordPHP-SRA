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

use SRA\SRA;
use Psr\Log\NullLogger;

const TIMEOUT = 10;

function wait(callable $callback, float $timeout = TIMEOUT, ?callable $timeoutFn = null)
{
    $sra = SRASingleton::get();

    $result = null;
    $finally = null;
    $timedOut = false;

    $sra->getLoop()->futureTick(function () use ($callback, $sra, &$result, &$finally) {
        $resolve = function ($x = null) use ($sra, &$result) {
            $result = $x;
            $sra->getLoop()->stop();
        };

        try {
            $finally = $callback($sra, $resolve);
        } catch (\Throwable $e) {
            $resolve($e);
        }
    });

    $timeout = $sra->getLoop()->addTimer($timeout, function () use ($sra, &$timedOut) {
        $timedOut = true;
        $sra->getLoop()->stop();
    });

    $sra->getLoop()->run();
    $sra->getLoop()->cancelTimer($timeout);

    if ($result instanceof Exception) {
        throw $result;
    }

    if (is_callable($finally)) {
        $finally();
    }

    if ($timedOut) {
        if ($timeoutFn != null) {
            $timeoutFn();
        } else {
            throw new \Exception('Timed out');
        }
    }

    return $result;
}

function getMockSRA(): SRA
{
    return new SRA(['token' => '', 'logger' => new NullLogger()]);
}
