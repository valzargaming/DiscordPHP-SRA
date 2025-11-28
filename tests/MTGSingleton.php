<?php

/*
 * This file is a part of the DiscordPHP-SRA project.
 *
 * Copyright (c) 2025-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use SRA\SRA;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use React\EventLoop\Loop;

class SRASingleton
{
    private static $sra;

    /**
     * @return SRA
     */
    public static function get()
    {
        if (! self::$sra) {
            self::new_cache();
        }

        return self::$sra;
    }

    private static function new_cache()
    {
        $loop = Loop::get();

        $redis = (new Clue\React\Redis\Factory($loop))->createLazyClient('localhost:6379');
        $cache = new WyriHaximus\React\Cache\Redis($redis);

        //$cache = new WyriHaximus\React\Cache\Filesystem(React\Filesystem\Filesystem::create($loop), getenv('RUNNER_TEMP').DIRECTORY_SEPARATOR);

        //$memcached = new \Memcached();
        //$memcached->addServer('localhost', 11211);
        //$psr6Cache = new \Symfony\Component\Cache\Adapter\MemcachedAdapter($memcached, 'dphp', 0);
        //$cache = new RedisPsr16($psr6Cache);

        $logger = new Logger('SRAPHP-UnitTests');
        $handler = new StreamHandler(fopen(__DIR__.'/../phpunit.log', 'w'));
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        $sra = new SRA([
            'token' => getenv('SRA_TOKEN'),
            'loop' => $loop,
            'logger' => $logger,
            'cache' => $cache,
        ]);

        $e = null;

        $timer = $sra->getLoop()->addTimer(10, function () use (&$e) {
            $e = new Exception('Timed out trying to connect to SRA.');
        });

        $sra->on('ready', function (SRA $sra) use ($timer) {
            $sra->getLoop()->cancelTimer($timer);
            $sra->getLoop()->stop();
        });

        self::$sra = $sra;

        $sra->run();

        if ($e !== null) {
            throw $e;
        }
    }

    private static function new()
    {
        $logger = new Logger('SRAPHP-UnitTests');
        $handler = new StreamHandler(fopen(__DIR__.'/../phpunit.log', 'w'));
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        $sra = new SRA([
            'token' => getenv('SRA_TOKEN'),
            'logger' => $logger,
        ]);

        $e = null;

        $timer = $sra->getLoop()->addTimer(10, function () use (&$e) {
            $e = new Exception('Timed out trying to connect to SRA.');
        });

        $sra->on('ready', function (SRA $sra) use ($timer) {
            $sra->getLoop()->cancelTimer($timer);
            $sra->getLoop()->stop();
        });

        $sra->getLoop()->run();

        if ($e !== null) {
            throw $e;
        }

        self::$sra = $sra;
    }
}
