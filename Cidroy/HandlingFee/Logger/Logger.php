<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Logger;

use Cidroy\HandlingFee\Model\Config;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    private Config $config;

    public function __construct(
        string $name,
        Config $config,
        array $handlers = [],
        array $processors = []
    ) {
        $this->config = $config;
        parent::__construct($name, $handlers, $processors);
    }

    public function addRecord(int $level, string $message, array $context = [], \DateTimeImmutable $datetime = null): bool
    {
        if (!$this->config->isLogEnabled()) {
            return false;
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }
}
