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

class sessions extends abstractManagers {
    /**
     * @param DiscordCommandClient $discord
     */
    public function registerCommands(DiscordCommandClient $discord): void{
        try {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $command = $discord->registerCommand('session', [$this, 'close']);
            $discord->registerAlias('s', 'session');
        } catch (Exception $e) {
            $this->configurations->logger->addError('init command failed to initialise');
        }
    }

    /**
     * @param Message $message
     */
    public function close(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        if ($request->discordUserId !== $this->servers[$request->discordServerId]['discordUserId']){
            $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_NON_MASTER);
        }

        $variables = [];

        foreach ($this->characters as $characterKey=>$character){
            if (strpos($characterKey, $request->discordServerId) === 0){
                $variable = [
                    'name'=>$character['name'],
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
                            'originalValue'=>$ability['value']
                        ];

                        /** @noinspection DisconnectedForeachInstructionInspection */
                        try {
                            $roll = random_int(1, 100);
                        } catch (Exception $e) {
                            $roll = 30;
                        }

                        $improvements = 0;
                        $delta = $roll - $ability['value'] - $character[$ability['trait']];
                        if ($delta > 0){
                            $improvements = (int)($delta/20)+1;
                        }

                        if ($roll === 100){
                            $improvements *= 2;
                        }

                        $usedAbilities[$abilityKey]['value'] += $improvements;
                        $usedAbilities[$abilityKey]['used'] = false;

                        $usedAbility['trait'] = $ability['trait'];
                        $usedAbility['traitValue'] = $character[$ability['trait']];
                        $usedAbility['roll'] = $roll;
                        $usedAbility['improvement'] = $improvements;
                        $usedAbility['value'] = $usedAbilities[$abilityKey]['value'];

                        $variable['abilities'][] = $usedAbility;
                    }

                    try {
                        tables::getCharacterAbilities()->update($usedAbilities);
                        $variables[] = $variable;
                    } catch (dbUpdateException $e) {
                        $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_IMPROVE_FAILED);
                        return;
                    }
                } catch (dbRecordNotFoundException $e) {}

            }
        }
        $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_IMPROVE, $variables, false, true);
    }
}