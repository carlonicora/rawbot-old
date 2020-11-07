<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class CampaignFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $campaign = $this->RAWBot->getDiscord()->registerCommand(
            'campaign',
            [$this, 'readCampaign'],
            [
                'description' => 'Provides the name of the Campaign'
            ]
        );

        $campaign->registerSubCommand(
            'create',
            [$this, 'createCampaign'],
            [
                'description' => 'Creates a new campaign in the current server'
            ]
        );
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function readCampaign(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER)) {
            return;
        }

        $this->response->setTitle('Your campaign');
        $this->response->setDescription('You are playing "' . $this->server['campaignName'] . '"');

        $this->response->setFooterThumbnail('https://res.cloudinary.com/teepublic/image/private/s--vUHPDVWq--/t_Preview/t_watermark_lock/b_rgb:000000,c_limit,f_auto,h_630,q_90,w_630/v1528388275/production/designs/2765565_0.jpg');
        $this->response->setFooter('Campaign');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function createCampaign(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true)) {
            return;
        }

        if ($this->server !== null){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::campaignAlreadyInitialised());
        }

        [$command, $subCommand] = str_getcsv(substr($message->content, 1), ' ');
        $campaignName = substr($message->content, 3+strlen($command)+strlen($subCommand));

        $this->server = [
            'discordServerId' => $this->RAWBot->getRequest()->getDiscordServerId(),
            'discordUserId' => $this->RAWBot->getRequest()->getDiscordUserId(),
            'campaignName' => $campaignName,
            'inSession' => false
        ];

        $this->RAWBot->getDatabase()->getServers()->update($this->server);

        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);
        $this->response->setTitle('New campaign created');
        $this->response->setDescription('The campaign "' . $this->server['campaignName'] . '" has been created!');

        $this->response->setFooterThumbnail('https://res.cloudinary.com/teepublic/image/private/s--vUHPDVWq--/t_Preview/t_watermark_lock/b_rgb:000000,c_limit,f_auto,h_630,q_90,w_630/v1528388275/production/designs/2765565_0.jpg');
        $this->response->setFooter('Campaign');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }
}