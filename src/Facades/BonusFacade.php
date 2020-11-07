<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Helpers\DiceRoller;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class BonusFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $bonus = $this->RAWBot->getDiscord()->registerCommand(
            'bonus',
            [$this, 'replyWithOptions']
        );

        $this->RAWBot->getDiscord()->registerAlias('b', 'bonus');

        $bonus->registerSubCommand(
            'up',
            [$this, 'upBonus']
        );

        $bonus->registerSubCommand(
            'roll',
            [$this, 'rollBonus']
        );

        $bonus->registerSubCommand(
            'award',
            [$this, 'awardBonus']
        );
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function replyWithOptions(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER)) {
            return;
        }

        if ($this->server['inSession']){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::bonusNotAvailableDuringSessions());
        }

        $character = $this->getCharacter($message->content, 1);

        if ($character['bonusPoints'] === 0){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::bonusPointsNotAvailable());
        }

        $usedAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadUsed(
            $character['characterId']
        );

        $updatedAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadUpdated(
            $character['characterId']
        );

        $footer = 'Bonus' . PHP_EOL;

        if (!empty($usedAbilities)){
            $footer .= 'Abilities you can try and roll: ';
            foreach ($usedAbilities as $usedAbility){
                $footer .= ucfirst($usedAbility['name']) . (($usedAbility['specialisation'] === '/') ? '' : '/' . ucfirst($usedAbility['specialisation'])) . ',';
            }
            $footer = substr($footer, 0, -1);
        } else {
            $footer .= 'You cannot roll for any ability.';
        }

        $footer .= PHP_EOL;

        if (!empty($updatedAbilities)){
            $footer .= 'Abilities you can update by one point: ';
            foreach ($updatedAbilities as $updatedAbility){
                $footer .= ucfirst($updatedAbility['name']) . (($updatedAbility['specialisation'] === '/') ? '' : '/' . ucfirst($updatedAbility['specialisation'])) . ',';
            }
            $footer = substr($footer, 0, -1);
        } else {
            $footer .= 'You cannot update any ability by one point.';
        }

        $this->response->setTitle('Bonus Helper');
        $this->response->setDescription('With this command you can use your bonus points to upgrade your character\'s abilities.');

        $this->addRemainingBonusPoints($character['bonusPoints']);

        $this->response->setMessageType(DiscordMessage::MESSAGE_INFO);
        $this->response->setFooter($footer);
        $this->response->addField(
            'up',
            '`/bonus up <ability>`' . PHP_EOL
            . 'Update an ability which was successfully updated at the end of the previous session by 1 point'
        );

        $this->response->addField(
            'roll',
            '`/bonus roll <ability>`' . PHP_EOL
            . 'Try and update an ability you have used in the previous session by one or more points'
        );

        $this->response->setFooterThumbnail('https://cdn.onlinewebfonts.com/svg/img_308724.png');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param int $bonusPoints
     */
    private function addRemainingBonusPoints(int $bonusPoints): void
    {
        if ($bonusPoints > 0){
            $this->response->setDescription(
                $this->response->getDescription() . PHP_EOL
                . 'You still have `' . $bonusPoints . '` to assign to your character!*'
            );
        } else {
            $this->response->setDescription(
                $this->response->getDescription() . PHP_EOL
                . '*Alas, you do not have any more bonus points to assign to your character!*'
            );
        }
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function upBonus(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        if ($this->server['inSession']){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::bonusNotAvailableDuringSessions());
        }

        $character = $this->getCharacter($message->content);

        if ($character['bonusPoints'] === 0){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::bonusPointsNotAvailable());
        }

        $updatedAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadUpdated(
            $character['characterId']
        );

        if (str_contains($this->parameters[0], '/')) {
            [$abilityName, $specialisation] = str_getcsv($this->parameters[0], '/');

            if ($specialisation === null) {
                $specialisation = '/';
            }
            $abilityName = strtolower($abilityName);
            $specialisation = strtolower($specialisation);
        } else {
            $abilityName = strtolower($this->parameters[0]);
            $specialisation = '/';
        }

        $ability = null;
        foreach ($updatedAbilities ?? [] as $updatedAbility){
            if ($updatedAbility['name'] === $abilityName && $updatedAbility['specialisation'] === $specialisation){
                $ability = $updatedAbility;
                break;
            }
        }

        if ($ability === null){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::abilityNotIncreasedBeforeUp($this->parameters[0]));
        }

        $this->RAWBot->getDatabase()->getCharacterAbilities()->updateCharacterAbility(
            $character['characterId'],
            $ability['abilityId'],
            $specialisation,
            1
        );

        --$character['bonusPoints'];
        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $ability['value'] += 1;

        $this->response->setTitle('Bonus Up!');
        $this->response->setDescription('You have successfully updated your ability `' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) . '`by one point.' . PHP_EOL
            . 'Your new ability score is now `' . $ability['value'] . '`');
        $this->addRemainingBonusPoints($character['bonusPoints']);
        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->response->setFooterThumbnail('https://cdn.onlinewebfonts.com/svg/img_308724.png');
        $this->response->setFooter('Bonus');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function rollBonus(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        if ($this->server['inSession']){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::bonusNotAvailableDuringSessions());
        }
        $character = $this->getCharacter($message->content);

        if ($character['bonusPoints'] === 0){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::bonusPointsNotAvailable());
        }

        $usedAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadUsed(
            $character['characterId']
        );

        if (str_contains($this->parameters[0], '/')) {
            [$abilityName, $specialisation] = str_getcsv($this->parameters[0], '/');

            if ($specialisation === null) {
                $specialisation = '/';
            }
            $abilityName = strtolower($abilityName);
            $specialisation = strtolower($specialisation);
        } else {
            $abilityName = strtolower($this->parameters[0]);
            $specialisation = '/';
        }

        $ability = null;
        foreach ($usedAbilities ?? [] as $usedAbility){
            if ($usedAbility['name'] === $abilityName && $usedAbility['specialisation'] === $specialisation){
                $ability = $usedAbility;
                break;
            }
        }

        if ($ability === null){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::abilityNotUsedBeforeUp($this->parameters[0]));
        }

        --$character['bonusPoints'];
        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $critical = DiceRoller::CRITICAL_NONE;
        $dice = DiceRoller::roll(100, $critical);

        $this->response->setTitle('Bonus Roll');

        if ($critical === DiceRoller::CRITICAL_FAILURE){
            $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_FAILURE);
            $this->response->setDescription(
                'You failed to increase your ability `' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) .'`' . PHP_EOL .
                'You rolled a 0! Critical Failure!'
            );
        } else {
            $delta = 0;
            $bonus = DiceRoller::calculateBonus($ability['value'], $character[$ability['trait']], $dice, $delta);

            if ($bonus > 0){
                $this->RAWBot->getDatabase()->getCharacterAbilities()->setAbilityUpdated(
                    $character['characterId'],
                    $ability['abilityId'],
                    $specialisation
                );
                $this->response->setDescription(
                    'You increased your ability `' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) . '` by `' . $bonus . '` points!' . PHP_EOL
                    . 'Your new ability score is now `' .($ability['value'] + $bonus) . '`.'
                );
                $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);
            } else {
                $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_FAILURE);
                $this->response->setDescription('You failed to increase your ability `' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) . '`');
            }

            $this->response->setDescription(
                $this->response->getDescription() . PHP_EOL
                . '[roll ('.$dice.') - ability ('.$ability['value'].') - trait ('.$character[$ability['trait']].') = '.$delta.'] => ' . $bonus . ' points'
            );

            if ($character['isNPC']){
                $this->response->setIsPrivateMessage(true);
            }

            $this->RAWBot->getDatabase()->getCharacterAbilities()->updateCharacterAbility(
                $character['characterId'],
                $ability['abilityId'],
                $specialisation,
                $bonus
            );
        }
        $this->addRemainingBonusPoints($character['bonusPoints']);

        $this->response->setFooterThumbnail('https://cdn.onlinewebfonts.com/svg/img_308724.png');
        $this->response->setFooter('Bonus');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function awardBonus(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER + self::GM)) {
            return;
        }

        $this->response->setTitle('Bonus Award');

        try {
            $character = $this->getCharacter($message->content, 2, true);
            $character['bonusPoints'] += $this->parameters[0];
            $this->RAWBot->getDatabase()->getCharacters()->update($character);

            if ($character['isNPC']) {
                $this->response->setDescription($character['name'] . ' has been awarded ' . $this->parameters[0] . ' bonus points.');
                $this->response->setIsPrivateMessage(true);
            } else {
                $this->response->setDescription($character['name'] . ' (<@' . $character['discordUserId'] . '>) has been awarded ' . $this->parameters[0] . ' bonus points.');
            }
        } catch (Exception $e) {
            $this->parameters = explode(' ', $message->content);
            array_shift($this->parameters);
            array_shift($this->parameters);
            $characters = $this->RAWBot->getDatabase()->getCharacters()->loadPlayerCharactersByServerId($this->server['serverId']);

            foreach ($characters as $characterKey=>$character){
                $characters[$characterKey]['bonusPoints'] += $this->parameters[0];

                $this->response->setDescription(
                    $this->response->getDescription()
                    . $character['name'] . ' (<@' . $character['discordUserId'] . '>),'
                );
            }

            $this->response->setDescription(
                substr($this->response->getDescription(), 0, -1)
                    . ' have been awarded ' . $this->parameters[0] . ' bonus points.'
            );

            $this->RAWBot->getDatabase()->getCharacters()->update($characters);
        }

        $this->response->setFooterThumbnail('https://cdn.onlinewebfonts.com/svg/img_308724.png');
        $this->response->setFooter('Bonus');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }
}