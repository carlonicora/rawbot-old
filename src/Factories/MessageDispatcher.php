<?php
namespace CarloNicora\RAWBot\Factories;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\RAWBot\Exceptions\RAWBotException;
use CarloNicora\RAWBot\Objects\DiscordError;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use CarloNicora\RAWBot\RAWBot;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\User;
use Exception;

class MessageDispatcher
{
    /** @var ServicesFactory  */
    protected ServicesFactory $services;

    /** @var RAWBot  */
    protected RAWBot $RAWBot;

    /**
     * AbstractFacade constructor.
     * @param ServicesFactory $services
     * @throws Exception
     */
    public function __construct(ServicesFactory $services)
    {
        $this->services = $services;
        $this->RAWBot = $this->services->service(RAWBot::class);
    }

    /**
     * @param RAWBotException $e
     * @throws Exception
     */
    public function sendError(RAWBotException $e): void
    {
        $error = new DiscordError($this->services, $e);

        $channel = $this->generateChannel($e->isPrivateMessage());
        $channel->sendMessage('', false, $error->getMessage());

        throw $e;
    }

    /**
     * @param DiscordMessage $message
     * @param string $textMessage
     * @throws Exception
     */
    public function sendMessage(DiscordMessage $message, string $textMessage=''): void
    {
        if ($textMessage === ''){
            $textMessage = $message->getAdditionalMessage();
        }

        /** @var Channel|User $channel */
        $channel = $this->generateChannel($message->isPrivateMessage());
        $channel->sendMessage($textMessage, false, $message->getMessage());
    }

    /**
     * @param bool $isPrivateMessage
     * @return mixed
     */
    private function generateChannel(bool $isPrivateMessage)
    {
        if ($isPrivateMessage){
            $response = $this->RAWBot->getDiscord()->factory(
                User::class,
                [
                    'id' => $this->RAWBot->getRequest()->getDiscordUserId()
                ],
                true
            );
        } else {
            $response = $this->RAWBot->getDiscordChannel();
        }

        return $response;
    }
}