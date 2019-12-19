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
use \Exception;

class masters extends abstractManagers {
    /**
     * @param DiscordCommandClient $discord
     */
    public function registerCommands(DiscordCommandClient $discord): void{
        try {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $command = $discord->registerCommand('gm', [$this, 'init']);

            $subcommand = $command->registerSubCommand('session', array($this, 'session'), [
                'description'=>''
            ]);

            $subcommand->registerSubCommand('end', array($this, 'end'), [
                'description'=>''
            ]);

            $subcommand->registerSubCommand('start', array($this, 'start'), [
                'description'=>''
            ]);

            $command->registerSubCommand('award', array($this, 'award'), [
                'description'=>''
            ]);
        } catch (Exception $e) {
            $this->configurations->logger->addError('init command failed to initialise');
        }
    }

    /**
     * @param Message $message
     */
    public function init(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        $this->sendResponse($message->channel, $request, rawMessages::GM_WELCOME, ['gm'=>$this->servers[$request->discordServerId]['discordUserId']], false, false);
    }

    /**
     * @param Message $message
     */
    public function session(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        if ($request->discordUserId !== $this->servers[$request->discordServerId]['discordUserId']) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_NON_MASTER);
            return;
        }

        $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_UNSPECIFIED_COMMAND);
    }

    /**
     * @param Message $message
     */
    public function start(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        if ($request->discordUserId !== $this->servers[$request->discordServerId]['discordUserId']) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_NON_MASTER);
            return;
        }

        if (tables::getCharacterAbilities()->startSession($request->discordServerId)){
            $this->sendResponse($message->channel, $request, rawMessages::SESSION_STARTED, [], false, false);
        } else {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_START_FAILED);
        }

    }

    /**
     * @param Message $message
     */
    public function end(Message $message): void {
         try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        if ($request->discordUserId !== $this->servers[$request->discordServerId]['discordUserId']) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_NON_MASTER);
            return;
        }

        try {
            $characters = tables::getCharacters()->loadFromServerId($this->servers[$request->discordServerId]['serverId']);

            foreach ($characters as $character) {
                $characterVariables = [
                    'characterName'=>$character['name'],
                    'discordUserId'=>$character['discordUserId'],
                    'abilities'=>[]
                ];

                try {
                    $usedAbilities = tables::getCharacterAbilities()->loadUsedFromCharacterId($character['characterId']);

                    foreach ($usedAbilities as $abilityKey=>$ability){
                        $abilityName = $ability['name'];
                        if ($ability['specialisation'] !== '-'){
                            $abilityName .= '-'.$ability['specialisation'];
                        }
                        $usedAbility = [
                            'name'=>$abilityName,
                            'originalValue'=>$ability['value'],
                            'characterName'=>$character['name'],
                            'discordUserId'=>$character['discordUserId'],
                        ];

                        /** @noinspection DisconnectedForeachInstructionInspection */
                        try {
                            $roll = random_int(1, 100);
                        } catch (Exception $e) {
                            $roll = 30;
                        }

                        $improvements = 0;
                        $delta = $roll - $ability['value'] - $character[$ability['trait']];
                        if ($delta >= 0){
                            $improvements = (int)($delta/20)+1;
                        }

                        if ($roll === 100){
                            $improvements *= 2;
                        }

                        $ability['used'] = false;
                        if ($improvements > 0) {
                            $ability['value'] += $improvements;
                            $ability['wasUpdated'] = true;
                        }

                        $usedAbility['trait'] = $ability['trait'];
                        $usedAbility['traitValue'] = $character[$ability['trait']];
                        $usedAbility['roll'] = $roll;
                        $usedAbility['improvement'] = $improvements;
                        $usedAbility['value'] = $ability['value'];

                        $characterVariables['abilities'][] = $usedAbility;

                        try {
                            tables::getCharacterAbilities()->update($ability);
                        } catch (dbUpdateException $e) {
                            $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_IMPROVE_FAILED);
                            return;
                        }
                    }

                    $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_IMPROVE, $characterVariables, false, true);
                } catch (dbRecordNotFoundException $e) {}
            }
        } catch (dbRecordNotFoundException $e) {}
    }

    /**
     * @param Message $message
     */
    public function award(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        if ($request->discordUserId !== $this->servers[$request->discordServerId]['discordUserId']) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_NON_MASTER);
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        [$command, $subcommand, $amount, $user] = str_getcsv(substr($message->content, 1), ' ');

        if (null !== $user){
            try {
                $discordUserId = substr($user, 2, -1);
                $character = tables::getCharacters()->loadFromDiscordUserId($this->servers[$request->discordServerId]['serverId'], $discordUserId);
                $character['bonusPoints'] += $amount;
                $characters = [
                    $character
                ];
            } catch (dbRecordNotFoundException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_NOT_FOUND);
                return;
            }
        } else {
            try {
                $characters = tables::getCharacters()->loadFromServerId($this->servers[$request->discordServerId]['serverId']);
                foreach ($characters as $characterKey=>$character){
                    $characters[$characterKey]['bonusPoints'] += $amount;
                }
            } catch (dbRecordNotFoundException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_NOT_FOUND);
                return;
            }
        }

        try {
            tables::getCharacters()->update($characters);

            $variables = [
                'award'=>$amount,
                'characters'=>[]
            ];

            foreach ($characters as $character){
                $variables['characters'][] = $character['discordUserId'];
            }

            $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_AWARDED, $variables, false, true);

        } catch (dbUpdateException $e) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_UPDATE_FAILED);
            return;
        }
    }
}