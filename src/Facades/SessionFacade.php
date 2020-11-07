<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Helpers\DiceRoller;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class SessionFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $session = $this->RAWBot->getDiscord()->registerCommand(
            'session',
            [$this, 'replyWithOptions']
        );

        $session->registerSubCommand(
            'start',
            [$this, 'startSession']
        );

        $session->registerSubCommand(
            'end',
            [$this, 'endSession']
        );
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function replyWithOptions(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER + self::GM)) {
            return;
        }

        $this->response->setTitle('Session Manager');
        $this->response->setDescription('With this command you can start or end a session');
        $this->response->setMessageType(DiscordMessage::MESSAGE_INFO);
        $this->response->addField(
            'start',
            '`/session start`' . PHP_EOL
            . 'Starts a session, allows the ability checks and removes the ability to roll any bonus'
        );

        $this->response->addField(
            'end',
            '`/session end`' . PHP_EOL
            . 'Stops a session, increases the Character\'s stats, disallow ability checks and allows the bonus rolls'
        );


        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function startSession(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER + self::GM)) {
            return;
        }

        if ($this->server['inSession'] === 1){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::sessionAlreadyStarted());
        }

        $this->server['inSession'] = 1;
        $this->RAWBot->getDatabase()->getServers()->update($this->server);

        $this->RAWBot->getDatabase()->getCharacterAbilities()->resetAbilityUsage($this->server['serverId']);

        $this->response->setTitle('This session has started');
        $this->response->setDescription(
            'The session is now on. Enjoy!' . PHP_EOL
            . 'From now on you can roll your abilities through `/roll <ability>` and they will count to increase your character\'s strenght' . PHP_EOL
            . 'The bonus rolls are on hold until the end of the session.' . PHP_EOL
            . '`ENJOY THE GAME!'
        );

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param array $usedAbility
     * @param array $abilities
     * @return array|null
     */
    private function getAbility(array $usedAbility, array $abilities): ?array
    {
        foreach ($abilities as $ability){
            if ($usedAbility['abilityId'] === $ability['abilityId']){
                return $ability;
            }
        }

        return null;
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function endSession(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER + self::GM)) {
            return;
        }

        if ($this->server['inSession'] === 0){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::sessionNotStarted());
        }

        $this->server['inSession'] = 0;
        $this->RAWBot->getDatabase()->getServers()->update($this->server);

        $characters = $this->RAWBot->getDatabase()->getCharacters()->loadAllCharactersByServerId($this->server['serverId']);

        $abilities = $this->RAWBot->getDatabase()->getAbilities()->loadAll();

        foreach ($characters as $characterKey=>$character){
            $upgraded = '';
            $usedAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadUsedByCharacterId($character['characterId']);

            $character['bonusPoints'] += 3;
            $characters[$characterKey]['bonusPoints'] += 3;

            $upgraded .= 'You have been awarded 3 bonus points (' . $character['bonusPoints'] . ')' . PHP_EOL . '---' . PHP_EOL;

            $atLeastOneAbilityUpdated = false;
            foreach ($usedAbilities as $usedAbilityKey=>$usedAbility){
                if (($ability = $this->getAbility($usedAbility, $abilities)) !== null) {
                    $critical = DiceRoller::CRITICAL_NONE;
                    $delta = 0;
                    $roll = DiceRoller::roll(100, $critical);
                    $bonus = DiceRoller::calculateBonus(
                        $usedAbility['value'],
                        $character[$ability['trait']],
                        $roll,
                        $delta
                    );

                    if ($bonus > 0) {
                        $usedAbility['value'] += $bonus;
                        $usedAbilities[$usedAbilityKey]['value'] += $bonus;
                        $usedAbilities[$usedAbilityKey]['wasUpdated'] = 1;
                        $atLeastOneAbilityUpdated = true;
                    }

                    if ($bonus === 0){
                        $upgraded .= '- ' . ucfirst($ability['name']) . (($usedAbility['specialisation'] === '/') ? '' : '/' . ucfirst($usedAbility['specialisation'])) . ' (' . $usedAbility['value'] . ') not improved (roll: '.$roll.')' . PHP_EOL;
                    } else {
                        $upgraded .= '* ' . ucfirst($ability['name']) . (($usedAbility['specialisation'] === '/') ? '' : '/' . ucfirst($usedAbility['specialisation'])) . ' (' . $usedAbility['value'] . ') `+' . $bonus . '` (roll: '.$roll.')' . PHP_EOL;
                    }
                }
            }

            if ($atLeastOneAbilityUpdated) {
                $this->RAWBot->getDatabase()->getCharacterAbilities()->update($usedAbilities);
            }

            if (!$character['isNPC']) {
                $this->response->addField(
                    $character['name'],
                    $upgraded
                );
            }
        }

        $this->RAWBot->getDatabase()->getCharacters()->update($characters);

        $this->response->setTitle('This session has ended');
        $this->response->setDescription(
            'Did you enjoy it?' . PHP_EOL
        );

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }
}