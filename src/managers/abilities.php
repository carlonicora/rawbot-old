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

class abilities extends abstractManagers {
    /**
     * @param DiscordCommandClient $discord
     */
    public function registerCommands(DiscordCommandClient $discord): void{
        try {
            $command = $discord->registerCommand('ability', [$this, 'ability']);
            $discord->registerAlias('a', 'ability');

            $command->registerSubCommand('list', array($this, 'list'), [
                'description'=>'list every ability available'
            ]);

        } catch (Exception $e) {
            $this->configurations->logger->addError('init command failed to initialise');
        }
    }

    /**
     * @param Message $message
     */
    public function ability(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER+self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        [$command, $abilityName, $value] = str_getcsv(substr($message->content, 1), ' ');
        [$abilityName, $specialisation] = str_getcsv($abilityName, '-');

        if ($specialisation === NULL){
            $specialisation = '-';
        }

        try {
            $characterAbility = tables::getCharacterAbilities()->loadFromCharacterIdAbilitySpecialisation(
                $this->characters[$request->discordServerId.$request->discordUserId]['characterId'],
                $abilityName,
                $specialisation
            );
        } catch (dbRecordNotFoundException $e) {
            try {
                $ability = tables::getAbilities()->loadFromName($abilityName);
            } catch (dbRecordNotFoundException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_MISSING);
                return;
            }

            $characterAbility = [
                'characterId'=>$this->characters[$request->discordServerId.$request->discordUserId]['characterId'],
                'abilityId'=>$ability['abilityId'],
                'specialisation'=>$specialisation,
                'value'=>0,
                'used'=>false
            ];

            try {
                tables::getCharacterAbilities()->update($characterAbility);
            } catch (dbUpdateException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_CREATION_FAILED);
                return;
            }

            $characterAbility['trait'] = $ability['trait'];
        }

        if ($value === NULL){
            try {
                $roll = random_int(1, 20);
            } catch (Exception $e) {
                $roll = 10;
            }

            if ($roll === 1){
                $rollResult = -20;
            } else if ($roll === 20){
                $rollResult = 40;
            } else {
                $rollResult = $roll;
            }

            $traitResult = $this->characters[$request->discordServerId.$request->discordUserId][$characterAbility['trait']];
            if ($traitResult === NULL){
                $traitResult = 0;
            }
            $abilityResult = $characterAbility['value'];

            $result = max($rollResult + $traitResult + $abilityResult, 0);

            if ($specialisation !== '-'){
                $abilityName .= '-' . $specialisation;
            }

            if ($roll !== $rollResult) {
                $roll .= '/' . $rollResult;
            }

            $characterAbility['used'] = true;
            try {
                tables::getCharacterAbilities()->update($characterAbility);
            } catch (dbUpdateException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_USED_FAILED);
            }

            $variables = [
                'abilityName'=>$abilityName,
                'result'=>$result,
                'roll'=>$roll,
                'trait'=>$traitResult,
                'ability'=>$abilityResult
            ];

            $this->sendResponse($message->channel, $request, rawMessages::ABILITY_CHECK, $variables, true);
        } else if (is_int((int)$value)){
            $characterAbility['value'] = $value;
            if ($specialisation !== '-'){
                $abilityName .= '-' . $specialisation;
            }
            try {
                tables::getCharacterAbilities()->update($characterAbility);
                $variables = [
                    'character'=>$this->characters[$request->discordServerId.$request->discordUserId]['name'],
                    'abilityName'=>$abilityName,
                    'abilityValue'=>$value
                ];
                $this->sendResponse($message->channel, $request, rawMessages::ABILITY_UPDATE, $variables);
            } catch (dbUpdateException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_UPDATE_FAILED);
            }
        } else {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_NONINT);
        }
    }

    /**
     * @param Message $message
     */
    public function list(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER + self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        try {
            $abilities = tables::getAbilities()->loadAll();

            $variables = [];

            foreach ($abilities as $ability){
                if (!array_key_exists($ability['trait'], $variables)){
                    $variables[$ability['trait']] = [];
                }
                $variables[$ability['trait']][] = $ability['name'];
            }

            $this->sendResponse($message->channel, $request, rawMessages::ABILITY_LIST, $variables);

        } catch (dbRecordNotFoundException $e) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_LIST_EMPTY);
        }
    }
}