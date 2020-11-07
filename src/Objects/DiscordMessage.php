<?php
namespace CarloNicora\RAWBot\Objects;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\RAWBot\Abstracts\AbstractDiscordMessage;
use Exception;

class DiscordMessage extends AbstractDiscordMessage
{
    /**
     * DiscordError constructor.
     * @param ServicesFactory $services
     * @param string $title
     * @param string $description
     * @param bool $isPrivateMessage
     * @throws Exception
     */
    public function __construct(ServicesFactory $services, string $title='', string $description='', bool $isPrivateMessage=false)
    {
        parent::__construct($services);

        $this->message->title = $title;
        $this->message->description = $description;
        $this->isPrivateMessage = $isPrivateMessage;
    }
}