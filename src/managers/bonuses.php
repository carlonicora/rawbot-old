<?php
namespace carlonicora\rawbot\managers;

use carlonicora\minimalism\exceptions\dbRecordNotFoundException;
use carlonicora\minimalism\exceptions\dbUpdateException;
use carlonicora\rawbot\abstracts\abstractManagers;
use carlonicora\rawbot\helpers\rawErrors;
use carlonicora\rawbot\helpers\rawMessages;
use carlonicora\rawbot\helpers\tables;
use carlonicora\rawbot\objects\request;
use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;
use \Exception;
use RuntimeException;

class bonuses extends abstractManagers {
    /**
     * @param DiscordCommandClient $discord
     */
    public function registerCommands(DiscordCommandClient $discord): void{
        try {
            $command = $discord->registerCommand('bonus', [$this, 'count'], [
                'description'=> 'bonus' . PHP_EOL

            ]);

            $discord->registerAlias('b', 'bonus');

            $command->registerSubCommand('roll', array($this, 'roll'), [
                'description'=>''
            ]);

            $command->registerSubCommand('up', array($this, 'up'), [
                'description'=>''
            ]);

        } catch (Exception $e) {
            $this->configurations->logger->addError('init command failed to initialise');
        }
    }

    /**
     * @param Message $message
     */
    public function count(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER + self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        $variables = [
            'bonuses'=>$this->characters[$request->discordServerId.$request->discordUserId]['bonusPoints']
        ];

        $this->sendResponse($message->channel, $request, rawMessages::BONUS_COUNT, $variables);
    }

    /**
    * @param Message $message
    */
    public function roll(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER + self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        if ($this->characters[$request->discordServerId.$request->discordUserId]['bonusPoints'] === 0) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::BONUS_ZERO);
        } else {

            if ($this->servers[$request->discordServerId]['inSession']){
                $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_STARTED);
                return;
            }

            try {
                $characterAbility = $this->getCharacterAbility($message, $request);

                $oldValue = $characterAbility['value'];
                $traitValue = $this->characters[$request->discordServerId.$request->discordUserId][$characterAbility['trait']];
                try {
                    $roll = random_int(1, 100);
                } catch (Exception $e) {
                    $roll = 40;
                }
                $improvement = 0;
                $delta = $roll-$oldValue-$traitValue;
                if ($delta >= 0){
                    $improvement = (int)($delta/20)+1;
                }

                if ($roll === 100){
                    $improvement *= 2;
                }

                if ($improvement > 0){
                    $characterAbility['value'] += $improvement;
                }

                $variables = [
                    'roll'=>$roll,
                    'originalValue'=>$oldValue,
                    'trait'=>$characterAbility['trait'],
                    'traitValue'=>$traitValue,
                    'improvement'=>$improvement,
                    'name'=>$characterAbility['name'],
                    'value'=>$characterAbility['value']
                ];

                $this->characters[$request->discordServerId.$request->discordUserId]['bonusPoints']--;

                if ($improvement > 0) {
                    try {
                        tables::getCharacterAbilities()->update($characterAbility);
                    } catch (dbUpdateException $e) {
                        $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_UPDATE_FAILED);
                        return;
                    }
                }

                try {
                    tables::getCharacters()->update($this->characters[$request->discordServerId . $request->discordUserId]);
                } catch (dbUpdateException $e) {
                    $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_UPDATE_FAILED);
                    return;
                }

                $this->sendResponse($message->channel, $request, rawMessages::BONUS_ROLL, $variables);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @param Message $message
     */
    public function up(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER + self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        if ($this->characters[$request->discordServerId.$request->discordUserId]['bonusPoints'] === 0) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::BONUS_ZERO);
        } else {

            if ($this->servers[$request->discordServerId]['inSession']){
                $this->sendError($message->channel, $request->discordUserId, rawErrors::SESSION_STARTED);
                return;
            }

            try {
                $characterAbility = $this->getCharacterAbility($message, $request);

                $characterAbility['value']++;
                $this->characters[$request->discordServerId.$request->discordUserId]['bonusPoints']--;
                try {
                    tables::getCharacterAbilities()->update($characterAbility);
                } catch (dbUpdateException $e) {
                    $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_UPDATE_FAILED);
                    return;
                }

                try {
                    tables::getCharacters()->update($this->characters[$request->discordServerId . $request->discordUserId]);
                } catch (dbUpdateException $e) {
                    $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_UPDATE_FAILED);
                    return;
                }

                $variables = [
                    'abilityName'=>$characterAbility['name'],
                    'abilityValue'=>$characterAbility['value']
                ];
                $this->sendResponse($message->channel, $request, rawMessages::BONUS_UP, $variables);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @param Message $message
     * @param request $request
     * @return array
     * @throws Exception
     */
    private function getCharacterAbility(Message $message, request $request): array {
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$command, $subcommand, $abilityName] = str_getcsv(substr($message->content, 1), ' ');
        [$abilityName, $specialisation] = str_getcsv($abilityName, '-');

        if ($specialisation === NULL){
            $specialisation = '-';
        }

        try {
            tables::getAbilities()->loadFromName($abilityName);
        } catch (dbRecordNotFoundException $e) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::ABILITY_MISSING);
            throw new RuntimeException('failed');
        }

        try {
            $characterAbility = tables::getCharacterAbilities()->loadFromCharacterIdAbilitySpecialisation(
                $this->characters[$request->discordServerId . $request->discordUserId]['characterId'],
                $abilityName,
                $specialisation
            );

            if ($characterAbility['value'] === 0){
                throw new dbRecordNotFoundException('');
            }
        } catch (dbRecordNotFoundException $e) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_ABILITY_MISSING);
            throw new RuntimeException('failed');
        }

        if ($characterAbility['wasUpdated'] === false || $characterAbility['wasUpdated'] === 0){
            $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_ABILITY_NOTUSED);
            throw new RuntimeException('failed');
        }

        return $characterAbility;
    }
}