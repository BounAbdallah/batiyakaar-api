<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Illuminate\Support\Facades\Http;

class DiscordLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger('discord');
        $logger->pushHandler(new DiscordHandler($config['url'], $config['level'] ?? Logger::DEBUG));

        return $logger;
    }
}

class DiscordHandler extends AbstractProcessingHandler
{
    private $url;

    public function __construct($url, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->url = $url;
    }

    protected function write(LogRecord $record): void
    {
        if (empty($this->url)) {
            return;
        }

        try {
            Http::post($this->url, [
                'content' => "**[{$record->level->name}]** {$record->message}",
                'embeds' => [
                    [
                        'title' => 'Détails',
                        'description' => substr(json_encode($record->context, JSON_PRETTY_PRINT), 0, 2000), // Limit context size
                        'color' => $this->getColor($record->level->value),
                        'timestamp' => $record->datetime->format('c')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            // Fail silently to avoid infinite loops if logging fails
        }
    }

    private function getColor($level)
    {
        return match ($level) {
            Logger::DEBUG => 10395294, // Gray
            Logger::INFO => 5025616, // Green
            Logger::NOTICE => 6323595, // Teal
            Logger::WARNING => 16753920, // Orange
            Logger::ERROR => 16007990, // Red
            Logger::CRITICAL => 9906492, // Dark Red
            Logger::ALERT => 16711680, // Bright Red
            Logger::EMERGENCY => 0, // Black
            default => 10395294,
        };
    }
}
