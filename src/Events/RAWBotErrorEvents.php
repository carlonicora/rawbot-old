<?php
namespace CarloNicora\RAWBot\Events;

use CarloNicora\Minimalism\Core\Events\Abstracts\AbstractErrorEvent;
use CarloNicora\Minimalism\Core\Events\Interfaces\EventInterface;
use CarloNicora\Minimalism\Core\Modules\Interfaces\ResponseInterface;

class RAWBotErrorEvents extends AbstractErrorEvent
{
    public static function MISSING_CONFIGURATION(string $configurationName) : EventInterface
    {
        return new self (1, ResponseInterface::HTTP_STATUS_500, 'Configuration error: ' . $configurationName . ' missing');
    }
}