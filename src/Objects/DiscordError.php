<?php
namespace CarloNicora\RAWBot\Objects;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\RAWBot\Abstracts\AbstractDiscordMessage;
use CarloNicora\RAWBot\Exceptions\RAWBotException;
use Exception;

class DiscordError extends AbstractDiscordMessage
{
    /**
     * DiscordError constructor.
     * @param ServicesFactory $services
     * @param RAWBotException $exception
     * @throws Exception
     */
    public function __construct(ServicesFactory $services, RAWBotException $exception)
    {
        parent::__construct($services);

        $this->message->title = 'Error';
        $this->message->description = $exception->getMessage();
        $this->setMessageType(self::MESSAGE_ERROR);
    }
}