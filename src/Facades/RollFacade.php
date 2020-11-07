<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Helpers\DiceRoller;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class RollFacade extends AbstractFacade
{
    /** @var int  */
    protected int $critical=DiceRoller::CRITICAL_NONE;

    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $this->RAWBot->getDiscord()->registerCommand(
            'roll',
            [$this, 'rollDice']
        );

        $this->RAWBot->getDiscord()->registerAlias('r', 'roll');
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function rollDice(Message $message): void
    {
        $this->allowedParameters = ['ability', 'bonus'];

        if (!$this->initialiseVariables($message, false, self::SERVER)) {
            return;
        }

        $this->parameters = explode(' ', $message->content);
        array_shift($this->parameters);

        if (stripos($this->parameters[0], '1d') === 0){
            $typeOfDice = substr($this->parameters[0], 2);

            if (strpos($this->parameters[0], '+') !== false){
                $this->parameters[1] = substr($this->parameters[0], strpos($this->parameters[0], '+'));
            } elseif (strpos($this->parameters[0], '-') !== false){
                $this->parameters[1] = substr($this->parameters[0], strpos($this->parameters[0], '-'));
            }

            $roll = DiceRoller::roll((int)$typeOfDice, $this->critical);

            $this->response->setTitle($this->parameters[0] . ' roll');

            if (count($this->parameters) > 1) {
                $bonus = 0;
                if (strpos($this->parameters[1], '+') === 0) {
                    $bonus = (int)substr($this->parameters[1], 1);
                } elseif ((strpos($this->parameters[1], '-') === 0)){
                    $bonus = -(int)substr($this->parameters[1], 1);
                }

                $this->response->setDescription(
                    'Dice roll: ' . $roll . PHP_EOL
                    . 'Bonus: ' . $bonus . PHP_EOL
                    . 'Total: `' . ($roll+$bonus) . '`'
                );
            } else {
                $this->response->setDescription(
                    'Dice roll: `' . $roll . '`'
                );
            }
        } else {
            if (!$this->server['inSession']){
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::abilityRollsNotAvailableOutsideSessions());
            }

            $character = $this->getCharacter($message->content, 1);

            $bonus = 0;
            if (str_contains($this->parameters[0], '+')) {
                [$parameter, $bonus] = str_getcsv($this->parameters[0], '+');
            } elseif (str_contains($this->parameters[0], '-')) {
                [$parameter, $bonus] = str_getcsv($this->parameters[0], '-');
                $bonus = -$bonus;
            } else {
                $parameter = $this->parameters[0];
            }

            if (str_contains($parameter, '/')) {
                [$abilityName, $specialisation] = str_getcsv($parameter, '/');

                if ($specialisation === null) {
                    $specialisation = '/';
                }
                $abilityName = strtolower($abilityName);
                $specialisation = strtolower($specialisation);
            } else {
                $abilityName = strtolower($parameter);
                $specialisation = '/';
            }

            try {
                $ability = $this->RAWBot->getDatabase()->getAbilities()->loadByName($abilityName);

                $characterAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterId(
                    $character['characterId']
                );

                $ca = null;
                foreach ($characterAbilities as $characterAbility){
                    if ($characterAbility['abilityId'] === $ability['abilityId'] && $characterAbility['specialisation'] === $specialisation){
                        $ca = $characterAbility;
                        break;
                    }
                }

                if ($ca === null){
                    $ca = [
                        'characterId' => $character['characterId'],
                        'abilityId' => $ability['abilityId'],
                        'specialisation' => $specialisation,
                        'value' => 0,
                        'used' => 0,
                        'wasUpdated' => 0
                    ];
                }

                $roll = DiceRoller::roll(20, $this->critical);

                if (count($this->parameters) > 1) {
                    if (strpos($this->parameters[1], '+') === 0) {
                        $bonus = (int)substr($this->parameters[1], 1);
                    } elseif ((strpos($this->parameters[1], '-') === 0)){
                        $bonus = -(int)substr($this->parameters[1], 1);
                    }
                }

                $disadvantage = 0;
                if ($ca['value'] === 0){
                    $disadvantage = -10;
                }

                $total = $ca['value'] + $character[$ability['trait']] + $roll + $bonus + $disadvantage;

                if ($this->critical === DiceRoller::CRITICAL_SUCCESS){
                    $total += 20;
                } elseif ($this->critical === DiceRoller::CRITICAL_FAILURE){
                    $total -= 21;
                }

                $successes = 0;

                if (array_key_exists(self::PARAMETER_THRESHOLD, $this->namedParameters)){
                    if ($total >= $this->namedParameters[self::PARAMETER_THRESHOLD]) {
                        $successes = (int)(($total - $this->namedParameters[self::PARAMETER_THRESHOLD]) / 25) + 1;
                    } else {
                        $successes = (int)(($total - $this->namedParameters[self::PARAMETER_THRESHOLD]) / 25) - 1;
                    }
                }elseif ($total > 0){
                    $successes = (int)($total/25);
                }

                $this->response->setTitle(ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) . ' check for ' . $character['name'] . ': `' . $total . '`');
                $this->response->setDescription(
                    'Ability: ' . $ca['value'] .PHP_EOL
                    . ucfirst($ability['trait']) . ': ' . $character[$ability['trait']] . PHP_EOL
                    . 'Dice roll: ' . $roll . PHP_EOL
                    . ($this->critical === DiceRoller::CRITICAL_FAILURE ? '**Critical Failure**: -20' . PHP_EOL : '')
                    . ($this->critical === DiceRoller::CRITICAL_SUCCESS ? '**Critical Success**: 20' . PHP_EOL : '')
                    . ($bonus !== 0 ? 'Bonus: ' . $bonus . PHP_EOL : '')
                    . ($disadvantage !== 0 ? 'Untrained disadvantage: ' . $disadvantage . PHP_EOL : '')
                    . '---' . PHP_EOL
                    . '**Total: `' . $total . '`**' . PHP_EOL
                    . '---' . PHP_EOL
                    . '*' . $successes . ' Successes*'
                );

                if ($this->critical === DiceRoller::CRITICAL_FAILURE){
                    $this->response->setImage('https://vignette.wikia.nocookie.net/kingsway-role-playing-group/images/a/ab/A7c1d56e7cdb84ee25e6769d9c7b9910--tabletop-rpg-tabletop-games.jpg');
                } elseif ($this->critical === DiceRoller::CRITICAL_SUCCESS) {
                    $this->response->setImage('https://media.giphy.com/media/Z9KdRxSrTcDHGE6Ipf/giphy.gif');
                }

                if (array_key_exists(self::PARAMETER_THRESHOLD, $this->namedParameters)){
                    if ($total >= $this->namedParameters[self::PARAMETER_THRESHOLD]){
                        $this->response->setTitle(
                            '[SUCCESS] ' . $this->response->getTitle()
                        );
                        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);
                    } else {
                        $this->response->setTitle(
                            '[FAILURE] ' . $this->response->getTitle()
                        );
                        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_FAILURE);
                    }
                } elseif ($this->critical === DiceRoller::CRITICAL_SUCCESS){
                    $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);
                } elseif ($this->critical === DiceRoller::CRITICAL_FAILURE){
                    $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_FAILURE);
                }

                $ca['used'] = 1;
                $this->RAWBot->getDatabase()->getCharacterAbilities()->update($ca);


            } catch (DbRecordNotFoundException $e) {
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectAbility($this->parameters[0]));
            }
        }

        $this->response->setFooterThumbnail('https://previews.123rf.com/images/martialred/martialred1512/martialred151200052/49796805-20-sided-20d-dice-line-art-icon-for-apps-and-websites.jpg');
        $this->response->setFooter('Roll');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }
}