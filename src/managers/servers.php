<?php
namespace carlonicora\rawbot\managers;

use carlonicora\minimalism\exceptions\dbRecordNotFoundException;
use carlonicora\minimalism\exceptions\dbUpdateException;
use carlonicora\rawbot\abstracts\abstractManagers;
use carlonicora\rawbot\helpers\rawErrors;
use carlonicora\rawbot\helpers\rawMessages;
use carlonicora\rawbot\helpers\tables;
use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;
use Exception;

class servers extends abstractManagers {
    /**
     * @param DiscordCommandClient $discord
     */
    public function registerCommands(DiscordCommandClient $discord): void {
        try {
            $discord->registerCommand('campaign', [$this, 'init']);
        } catch (Exception $e) {
            $this->configurations->logger->addError('init command failed to initialise');
        }
    }

    /**
     * @param Message $message
     */
    public function init(Message $message): void {
        try {
            $request = $this->intialiseVariables($message);
        } catch (Exception $e) {
            return;
        }

        try {
            tables::getServers()->loadFromDiscordServerId($request->discordServerId);
            $this->sendError($message->channel, $request->discordUserId, rawErrors::SERVER_ALREADY_PRESENT);
        } catch (dbRecordNotFoundException $e) {
            $this->servers[$request->discordServerId] = [
                'discordServerId'=>$request->discordServerId,
                'discordUserId'=>$request->discordUserId
            ];

            try {
                tables::getServers()->update($this->servers[$request->discordServerId]);
                $this->sendResponse($message->channel, $request, rawMessages::CAMPAIGN_CREATED);
            } catch (dbUpdateException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::SERVER_CREATION_FAILED);
            }
        }
    }
}