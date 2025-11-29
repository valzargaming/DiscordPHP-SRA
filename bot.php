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

use Discord\Builders\CommandBuilder;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Separator;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\OAuth\Application;
use Discord\Parts\User\User;
use Discord\Repository\EmojiRepository;
use Discord\Repository\Interaction\GlobalCommandRepository;
use Discord\WebSockets\Intents;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use React\EventLoop\Loop;

use function React\Async\async;
use function React\Promise\set_rejection_handler;

$technician_id = getenv('technician_id') ?: '116927250145869826'; // Default to Valithor Obsidion's ID

ini_set('zend.assertions', '1'); // Enable assertions for development

define('SRACARDINFOBOT_START', microtime(true));
ini_set('display_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);
ignore_user_abort(true);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1'); // Unlimited memory usage
define('MAIN_INCLUDED', 1); // Token and SQL credential files may be protected locally and require this to be defined to access

$autoload_path = file_exists($autoload_path = __DIR__.'/vendor/autoload.php') ? $autoload_path
    : (file_exists($autoload_path = dirname(__DIR__).'/vendor/autoload.php') ? $autoload_path
    : (file_exists($autoload_path = realpath(__DIR__.'/../vendor/autoload.php')) ? $autoload_path
    : (file_exists($autoload_path = realpath(__DIR__.'/../../vendor/autoload.php')) ? $autoload_path
    : (file_exists($autoload_path = realpath(dirname(__DIR__).'/../vendor/autoload.php')) ? $autoload_path
    : (
        file_exists($autoload_path = realpath(dirname(__DIR__).'/../../vendor/autoload.php')) ? $autoload_path
    : null
    )))));
$autoload_path ? require ($autoload_path) : throw new \Exception('Composer autoloader not found. Run `composer update` and try again.');

function loadEnv(string $filePath): void
{
    if (! file_exists($filePath)) {
        throw new \Exception('The .env file does not exist.');
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $trimmedLines = array_map('trim', $lines);
    $filteredLines = array_filter($trimmedLines, fn ($line) => $line && ! str_starts_with($line, '#'));

    array_walk($filteredLines, function ($line) {
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if (! array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
        }
    });
}

$env_path = file_exists($env_path = getcwd().'/.env') ? $env_path
    : (file_exists($env_path = dirname(getcwd()).'/.env') ? $env_path
    : (file_exists($env_path = realpath(getcwd().'/../.env')) ? $env_path
    : (file_exists($env_path = realpath(getcwd().'/../../.env')) ? $env_path
    : (file_exists($env_path = realpath(dirname(getcwd()).'/../.env')) ? $env_path
    : (
        file_exists($env_path = realpath(dirname(getcwd()).'/../../.env')) ? $env_path
    : null
    )))));
$env_path ? loadEnv($env_path) : throw new \Exception('The .env file does not exist. Please create one in the root directory.');

$streamHandler = new StreamHandler('php://stdout', Level::Debug);
$streamHandler->setFormatter(new LineFormatter(null, null, true, true, true));
$logger = new Logger('SRACARDINFOBOT', [$streamHandler]);
//file_put_contents('output.log', ''); // Clear the contents of 'output.log'
//$logger->pushHandler(new StreamHandler('output.log', Level::Debug));
$logger->info('Loading configurations for the bot...');
set_rejection_handler(function (\Throwable $e) use ($logger): void {
    //if ($e->getMessage() === 'Cannot resume a fiber that is not suspended') return;
    $logger->warning("Unhandled Promise Rejection: {$e->getMessage()} [{$e->getFile()}:{$e->getLine()}] ".str_replace('#', '\n#', $e->getTraceAsString()));
});

$sra = new SRA([
    'loop' => Loop::get(),
    'logger' => $logger,
    'socket_options' => [
        'dns' => '8.8.8.8',
    ],
    'token' => getenv('TOKEN'),
    'storeMessages' => true, // Only needed if messages need to be stored in the cache
    'intents' => Intents::getDefaultIntents() /*| Intents::GUILD_MEMBERS | Intents::GUILD_PRESENCES | Intents::MESSAGE_CONTENT*/,
    'useTransportCompression' => false, // Disable zlib-stream
    'usePayloadCompression' => true, // RFC1950 2.2
    //'loadAllMembers' => true,
]);

$webapi = null;
$socket = null;

$global_error_handler = async(function (int $errno, string $errstr, ?string $errfile, ?int $errline) use (&$sra, &$logger, &$technician_id) {
    if (! $sra instanceof SRA) {
        return;
    }
    $logger->error($msg = sprintf("[%d] Fatal error on `%s:%d`: %s\nBacktrace:\n```\n%s\n```", $errno, $errfile, $errline, $errstr, implode("\n", array_map(fn ($trace) => ($trace['file'] ?? '').':'.($trace['line'] ?? '').($trace['function'] ?? ''), debug_backtrace()))));
    if (getenv('TESTING')) {
        return;
    }
    $promise = $sra->users->fetch($technician_id);
    $promise = $promise->then(fn (User $user) => $user->getPrivateChannel());
    $promise = $promise->then(fn (Channel $channel) => $channel->sendMessage(SRA::createBuilder()->setContent($msg)));
});
set_error_handler($global_error_handler);

use React\Socket\SocketServer;
use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;
use SRA\Parts\Animal;
use SRA\Parts\Fact;

$socket = new SocketServer(
    sprintf('%s:%s', '0.0.0.0', getenv('http_port') ?: 55555),
    [
        'tcp' => [
            'so_reuseport' => true,
        ],
    ],
    Loop::get()
);
/**
 * Handles the HTTP request using the HttpServiceManager.
 *
 * @param  ServerRequestInterface $request The HTTP request object.
 * @return Response               The HTTP response object.
 */
$webapi = new HttpServer(Loop::get(), async(
    fn (ServerRequestInterface $request): Response =>
    /** @var ?SRA $sra */
    ($sra instanceof SRA)
        ? new Response(Response::STATUS_IM_A_TEAPOT, ['Content-Type' => 'text/plain'], 'Service Not Yet Implemented')
        : new Response(Response::STATUS_SERVICE_UNAVAILABLE, ['Content-Type' => 'text/plain'], 'Service Unavailable')
));

/**
 * This code snippet handles the error event of the web API.
 * It logs the error message, file, line, and trace, and handles specific error cases.
 * If the error message starts with 'Received request with invalid protocol version', it is ignored.
 * If the error message starts with 'The response callback', it triggers a restart process.
 * The restart process includes sending a message to a specific Discord channel and closing the socket connection.
 * After a delay of 5 seconds, the script is restarted by calling the 'restart' function and closing the Discord connection.
 *
 * @param \Exception                              $e       The \exception object representing the error.
 * @param \Psr\Http\Message\RequestInterface|null $request The HTTP request object associated with the error, if available.
 * @param object                                  $sra     The main object of the application.
 * @param object                                  $socket  The socket object.
 * @param bool                                    $testing Flag indicating if the script is running in testing mode.
 */
$webapi->on('error', async(function (\Exception $e, ?\Psr\Http\Message\RequestInterface $request = null) use (&$sra, &$logger, &$socket, $technician_id) {
    if (str_starts_with($e->getMessage(), 'Received request with invalid protocol version')) {
        return;
    }
    $logger->warning("[WEBAPI] {$e->getMessage()} [{$e->getFile()}:{$e->getLine()}] ".str_replace('\n', PHP_EOL, $e->getTraceAsString()));
    if ($request) {
        $logger->error('[WEBAPI] Request: '.preg_replace('/(?<=key=)[^&]+/', '********', $request->getRequestTarget()));
    }
    if (! str_starts_with($e->getMessage(), 'The response callback')) {
        return;
    }
    $logger->error('[WEBAPI] ERROR - RESTART');
    if (! $sra instanceof SRA) {
        return;
    }
    $socket->close();
    if (getenv('TESTING')) {
        return;
    }
    $promise = $sra->users->fetch($technician_id);
    $promise = $promise->then(fn (User $user) => $user->getPrivateChannel());
    $promise = $promise->then(fn (Channel $channel) => $channel->sendMessage(SRA::createBuilder()->setContent('Restarting due to error in HttpServer API...')));
}));

$func = function (SRA $sra) {
    $sra->emojis->freshen()
        ->then(fn (EmojiRepository $emojis) => $sra->application->commands->freshen())
        ->then(function (GlobalCommandRepository $commands) use ($sra): void {
            if ($names = array_map(fn ($command) => $command->name, iterator_to_array($commands))) {
                $sra->logger->debug('[GLOBAL APPLICATION COMMAND LIST] `'.implode('`, `', $names).'`');
            }

            $sra->listenCommand(
                $name = 'birdfact',
                fn (Interaction $interaction) => $interaction->acknowledgeWithResponse(true)
                    ->then(fn () => $sra->facts->bird())
                    ->then(function (Fact $bird) use ($interaction): PromiseInterface {
                        $builder = SRA::createBuilder();
                        $container = $bird->toContainer();
                        return $interaction->updateOriginalResponse(
                            $builder->addComponent(
                                $container->addComponents([
                                    Separator::new(),
                                    //ActionRow::new()->addComponents($buttons),
                                    //Separator::new(),
                                    Button::link(SRA::GITHUB)->setLabel('GitHub'),
                                ])
                            )
                        );
                })
            );

            $sra->listenCommand(
                $name = 'birdfactimage',
                fn (Interaction $interaction) => $interaction->acknowledgeWithResponse(true)
                    ->then(fn () => $sra->animals->bird())
                    ->then(function (Animal $bird) use ($interaction): PromiseInterface {
                        $builder = SRA::createBuilder();
                        $container = $bird->toContainer();
                        return $interaction->updateOriginalResponse(
                            $builder->addComponent(
                                $container->addComponents([
                                    Separator::new(),
                                    //ActionRow::new()->addComponents($buttons),
                                    //Separator::new(),
                                    Button::link(SRA::GITHUB)->setLabel('GitHub'),
                                ])
                            )
                        );
                })
            );

            if (! $command = $commands->get('name', $name = 'birdfact')) {
                $sra->logger->debug("[GLOBAL APPLICATION COMMAND] Creating `$name` command...");
                $builder = CommandBuilder::new()
                    ->setName($name)
                    ->setType(Command::CHAT_INPUT)
                    ->setDescription('Get a random bird fact.')
                    ->setContext([Interaction::CONTEXT_TYPE_GUILD, Interaction::CONTEXT_TYPE_BOT_DM, Interaction::CONTEXT_TYPE_PRIVATE_CHANNEL])
                    ->addIntegrationType(Application::INTEGRATION_TYPE_GUILD_INSTALL)
                    ->addIntegrationType(Application::INTEGRATION_TYPE_USER_INSTALL);
                $commands->save($sra->application->commands->create($builder->toArray()));
            }

            if (! $command = $commands->get('name', $name = 'birdfactimage')) {
                $sra->logger->debug("[GLOBAL APPLICATION COMMAND] Creating `$name` command...");
                $builder = CommandBuilder::new()
                    ->setName($name)
                    ->setType(Command::CHAT_INPUT)
                    ->setDescription('Get a random bird fact with an image.')
                    ->setContext([Interaction::CONTEXT_TYPE_GUILD, Interaction::CONTEXT_TYPE_BOT_DM, Interaction::CONTEXT_TYPE_PRIVATE_CHANNEL])
                    ->addIntegrationType(Application::INTEGRATION_TYPE_GUILD_INSTALL)
                    ->addIntegrationType(Application::INTEGRATION_TYPE_USER_INSTALL);
                $commands->save($sra->application->commands->create($builder->toArray()));
            }
            //var_dump($command);
            //$commands->delete($command);
        });
};

$init_called = false;
$application_init_called = false;
$sra->once('init', function (SRA $sra) use (&$init_called, &$application_init_called, &$func) {
    $init_called = true;
    if (! $application_init_called) {
        return;
    }
    $func($sra);
    unset($func, $init_called, $application_init_called);
});
$sra->once('application-init', function (SRA $sra) use (&$init_called, &$application_init_called, &$func) {
    $application_init_called = true;
    if (! $init_called) {
        return;
    }
    $func($sra);
    unset($func, $init_called, $application_init_called);
});

$sra->run();
