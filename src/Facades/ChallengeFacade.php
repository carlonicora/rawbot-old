<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Helpers\DiceRoller;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class ChallengeFacade extends AbstractFacade
{
    /** @var array|null  */
    private ?array $challenger=null;

    /** @var array|null  */
    private ?array $opposer=null;

    /** @var array|null  */
    private ?array $challengingAbility=null;

    /** @var array|null  */
    private ?array $opposingAbilities=null;

    /** @var array|null  */
    private ?array $opposingAbility=null;

    /** @var int  */
    private int $challengerBonus=0;

    /** @var array|null  */
    private ?array $weapon=null;

    /** @var array|null  */
    private ?array $hitLocation=null;

    /** @var int  */
    private int $damage=0;

    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $this->RAWBot->getDiscord()->registerCommand(
            'challenge',
            [$this, 'initiateChallenge'],
            [
                'description' => 'Challenges '
            ]
        );

    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function initiateChallenge(Message $message): void
    {
        $this->challenger=null;
        $this->opposer=null;
        $this->challengingAbility=null;
        $this->opposingAbilities=null;
        $this->opposingAbility=null;
        $this->challengerBonus=0;
        $this->weapon=null;
        $this->hitLocation=null;
        $this->damage=0;

        $this->allowedParameters = ['ability', 'bonus'];

        $listenForOpposition = false;

        if (!$this->initialiseVariables($message, false, self::SERVER)) {
            return;
        }

        if (!$this->server['inSession']){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::challengeNotAvailableDuringSessions());
        }

        if (!array_key_exists('ability', $this->namedParameters)){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::challengeDoesNotSpecifyAnAbility());
        }

        if (array_key_exists('bonus', $this->namedParameters) && is_numeric($this->namedParameters['bonus'])){
            $this->challengerBonus = $this->namedParameters['bonus'];
        }

        if (array_key_exists(self::PARAMETER_WEAPON, $this->namedParameters)){
            $this->weapon = $this->namedParameters[self::PARAMETER_WEAPON];
        }

        if (array_key_exists(self::PARAMETER_HIT_LOCATION, $this->namedParameters)){
            $this->hitLocation = $this->namedParameters[self::PARAMETER_HIT_LOCATION];
        }

        if (array_key_exists(self::PARAMETER_DAMAGE, $this->namedParameters)){
            $this->damage = $this->namedParameters[self::PARAMETER_DAMAGE];
        }

        $this->challenger = $this->getCharacter($message->content, 1);

        try {
            $this->challengingAbility = $this->RAWBot->getDatabase()->getAbilities()->loadByName(strtolower($this->namedParameters['ability']));

            if (!$this->challengingAbility['canChallenge']){
                $challengers = $this->RAWBot->getDatabase()->getAbilities()->loadChallengers();
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::abilityCannotChallenge($this->challengingAbility['name'], $challengers));
            }

            $opposableAbilities = $this->RAWBot->getDatabase()->getOpposingAbilities()->loadByAbilityId($this->challengingAbility['abilityId']);

            if (count($opposableAbilities) > 0) {
                $this->opposingAbilities = [];
                foreach ($opposableAbilities ?? [] as $opposableAbility) {
                    $this->opposingAbilities[] = $this->RAWBot->getDatabase()->getAbilities()->loadFromId($opposableAbility['opposingAbilityId']);
                }
            }

        } catch (DbRecordNotFoundException $e) {
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectAbility($this->namedParameters['ability']));
        }

        if (array_key_exists(self::PARAMETER_OPPONENT, $this->namedParameters)){
            try {
                if (strpos($this->namedParameters[self::PARAMETER_OPPONENT], '<@') === 0){
                    $this->opposer = $this->RAWBot->getDatabase()->getCharacters()->loadByDiscordUserId($this->server['serverId'], substr($this->namedParameters[self::PARAMETER_OPPONENT], 3, -1));
                } else {
                    $this->opposer = $this->RAWBot->getDatabase()->getCharacters()->loadByCharacterShortName($this->server['serverId'], $this->namedParameters[self::PARAMETER_OPPONENT]);
                }

                if ($this->opposingAbilities === null){
                    $this->resolveChallenge();
                } elseif ($this->opposer['automaticallyAcceptChallenges'] || $this->opposer['isNPC']){
                    $this->findBestOpposingAbility();

                    $this->resolveChallenge();
                } else {
                    $this->prepareMessageForUserResponse();
                    $listenForOpposition = true;
                }
            } catch (DbRecordNotFoundException $e) {
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::opponentNotFound());
            }
        } elseif ($this->amITheGM()){
            $this->response->setTitle('Pick an opponent Non Player Character');
            $this->response->setDescription(
                '<@' . $this->server['discordUserId'] . '> please pick the `shortName` of the Non Player Character opposing ' . $this->challenger['name'] . '\'s challenge' . PHP_EOL
                . 'Just type the name of the NPC, without any other command'
            );
            $listenForOpposition = true;
            //$this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::gmShouldPickOpponent());
        } else {
            $this->response->setTitle('Pick an opponent Non Player Character');
            $this->response->setDescription(
                '<@' . $this->server['discordUserId'] . '> please pick the `shortName` of the Non Player Character opposing ' . $this->challenger['name'] . '\'s challenge' . PHP_EOL
                    . 'Just type the name of the NPC, without any other command'
            );
            $listenForOpposition = true;
        }

        $this->response->setFooterThumbnail('https://previews.123rf.com/images/martialred/martialred1512/martialred151200052/49796805-20-sided-20d-dice-line-art-icon-for-apps-and-websites.jpg');
        $this->response->setFooter('Roll');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);

        if ($listenForOpposition) {
            /** @var Message $a */
            $message->channel->createMessageCollector(
                [$this, 'listenOpposition'],
                [
                    'time' => 15000,
                    'limit' => 1
                ]
            );
        }
    }

    /**
     * @throws Exception
     */
    private function prepareMessageForUserResponse(): void
    {

        $this->response->setTitle('You have been challenged');

        $this->response->setDescription(
            '<@' . $this->opposer['discordUserId'] . '> you have been challenged. Your opponent is using `' . $this->challengingAbility['name'] . '` against you.' . PHP_EOL
            . 'To oppose the action, please respond in one of the following ways (without sending any other command):'
        );

        $availableOpposingAbilities = $this->findOpposerAbilities();

        $this->response->addField(
            'always',
            'Automatically resolve all the challenges by using the best ability to oppose it.' . PHP_EOL
            . 'You will not be asked to oppose a challenge, but RAWBot will pick the ability that gives you the best options to succeed.'
        );

        if (count($availableOpposingAbilities) === 1){
            $this->response->addField(
                'oppose',
                'Use the only available ability to oppose the challenge'
            );
        }

        foreach ($availableOpposingAbilities ?? [] as $opposingAbility)
        {
            $description = 'You can oppose the challenge using `'. $opposingAbility['name'] . '`.' . PHP_EOL;
            if ($opposingAbility['value'] === 0){
                $description .= 'You are not trained to use this ability, so you will have a `-10` difficulty on the roll.';
                $total = -10;
            } else {
                $description .= 'You skill with this ability is `' . $opposingAbility['value'] . '`.';
                $total = $opposingAbility['value'];
            }

            $total += $opposingAbility['traitValue'];
            $description .= 'It is based on ' . $opposingAbility['traitName'] . ' for which you have a value of `' . $opposingAbility['traitValue'] . '`.' . PHP_EOL
                . 'Using `' . $opposingAbility['name'] . '` would give you a value of **' . $total . '** to be added to your roll.';

            $this->response->addField(
                ucfirst($opposingAbility['name']),
                $description
            );
        }
    }

    /**
     * @param Message $message
     * @return bool
     * @throws Exception
     */
    public function listenOpposition(Message $message): bool
    {
        if (!$this->initialiseVariables($message, false, self::SERVER)) {
            return false;
        }

        if ($this->opposer === null && $this->amITheGM()) {
            try {
                if (strpos($message->content, '<@') === 0){
                    $this->opposer = $this->RAWBot->getDatabase()->getCharacters()->loadByDiscordUserId($this->server['serverId'], substr($message->content, 3, -1));
                } else {
                    $this->opposer = $this->opposer = $this->RAWBot->getDatabase()->getCharacters()->loadByCharacterShortName($this->server['serverId'], $message->content);
                }

                if ($this->opposer['isNPC'] || $this->opposer['automaticallyAcceptChallenges']) {
                    $this->findBestOpposingAbility();
                } else {
                    $this->prepareMessageForUserResponse();
                    $this->RAWBot->getDispatcher()->sendMessage($this->response);
                }
            } catch (DbRecordNotFoundException $e) {
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::opponentNotFound());
            }
        } elseif ($this->opposer !== null && $this->opposer['discordUserId'] === $this->RAWBot->getRequest()->getDiscordUserId()) {
            $parameters = explode(' ', $message->content);

            switch (strtolower($parameters[0])) {
                case 'oppose':
                    if (count($this->opposingAbilities) === 1) {
                        $this->opposingAbility = $this->opposingAbilities[0];
                    }
                    break;
                case 'always':
                    $this->opposer['automaticallyAcceptChallenges'] = 1;
                    $this->RAWBot->getDatabase()->getCharacters()->update($this->opposer);
                    $this->findBestOpposingAbility();
                    break;
                default:
                    foreach ($this->opposingAbilities ?? [] as $opposingAbility) {
                        if ($opposingAbility['name'] === strtolower($parameters[0])) {
                            $this->opposingAbility = $opposingAbility;
                            break;
                        }
                    }

                    if ($this->opposingAbility === null) {
                        $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::abilityNotFoud($parameters[0]));
                    }
                    break;
            }
        }

        if (
            $this->challenger !== null
            &&
            $this->challengingAbility !== null
            &&
            $this->opposer !== null
            &&
            $this->opposingAbility !== null
        ) {
            $this->resolveChallenge();

            $this->response->setFooterThumbnail('https://previews.123rf.com/images/martialred/martialred1512/martialred151200052/49796805-20-sided-20d-dice-line-art-icon-for-apps-and-websites.jpg');
            $this->response->setFooter('Roll');

            $this->RAWBot->getDispatcher()->sendMessage($this->response);

            return true;
        }

        return false;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function findOpposerAbilities(): array
    {
        $response = [];

        $characterAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterId($this->opposer['characterId']);

        foreach ($this->opposingAbilities ?? [] as $opposingAbility){
            $ability = null;

            foreach ($characterAbilities ?? [] as $characterAbility){
                if ($characterAbility['abilityId'] === $opposingAbility['abilityId']){
                    $ability = $characterAbility;
                    $ability['name'] = $opposingAbility['name'];
                    $ability['traitName'] = $opposingAbility['trait'];
                    $ability['traitValue'] = $this->opposer[$opposingAbility['trait']];
                    break;
                }
            }

            if ($ability === null){
                $ability = [
                    'name' => $opposingAbility['name'],
                    'traitName' => $opposingAbility['trait'],
                    'traitValue' => $this->opposer[$opposingAbility['trait']],
                    'characterId' => $this->opposer['characterId'],
                    'abilityId' => $opposingAbility['abilityId'],
                    'specialisation' => '/',
                    'value' => 0,
                    'used' => 0,
                    'wasUpdated' => 0
                ];
            }

            $response[] = $ability;
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    private function findBestOpposingAbility(): void
    {
        $abilities = $this->findOpposerAbilities();

        foreach ($abilities ?? [] as $ability){
            if (
                $this->opposingAbility === null
                ||
                (
                    ($this->opposingAbility['traitValue'] + $this->opposingAbility['value'])
                    <
                    ($ability['traitValue'] + $ability['value'])
                )) {
                $this->opposingAbility = $ability;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function resolveChallenge(): void
    {
        $challengerCritical = DiceRoller::CRITICAL_NONE;
        $challengerDisadvantage = 0;

        $opposerCritical = DiceRoller::CRITICAL_NONE;
        $opposerDisadvantage = 0;

        $damage = 0;

        try {
            $challengerAbility = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterIdAbilityIdSpecialisation(
                $this->challenger['characterId'],
                $this->challengingAbility['abilityId'],
                '/'
            );
        } catch (DbRecordNotFoundException $e) {
            $challengerAbility = [
                'characterId' => $this->challenger['characterId'],
                'abilityId' => $this->challengingAbility['abilityId'],
                'specialisation' => '/',
                'value' => 0,
                'used' => 0,
                'wasUpdated' => 0
            ];
        }

        if ($challengerAbility['value'] === 0){
            $challengerDisadvantage = -10;
        }

        $challengerAbility['used'] = 1;
        $this->RAWBot->getDatabase()->getCharacterAbilities()->update($challengerAbility);

        $hitLocationDifficultyIncreate = 0;
        if ($this->hitLocation !== null){
            $hitLocationDifficultyIncreate = $this->hitLocation['difficultyIncrese'];
        }

        $challengerRoll = DiceRoller::roll(20, $challengerCritical);
        $challengerTotal = $challengerAbility['value']
            + $this->challenger[$this->challengingAbility['trait']]
            + $challengerRoll
            + $hitLocationDifficultyIncreate
            + $this->challengerBonus
            + $challengerDisadvantage;
        if ($challengerCritical === DiceRoller::CRITICAL_SUCCESS){
            $challengerTotal += 20;
        } elseif ($challengerCritical === DiceRoller::CRITICAL_FAILURE){
            $challengerTotal -= 21;
        }

        $challengerDescription = 'Ability: ' . $challengerAbility['value'] .PHP_EOL
            . ucfirst($this->challengingAbility['trait']) . ': ' . $this->challenger[$this->challengingAbility['trait']] . PHP_EOL
            . 'Dice roll: ' . $challengerRoll . PHP_EOL
            . ($challengerCritical === DiceRoller::CRITICAL_FAILURE ? '**Critical Failure**: -20' . PHP_EOL : '')
            . ($challengerCritical === DiceRoller::CRITICAL_SUCCESS ? '**Critical Success**: 20' . PHP_EOL : '')
            . ($this->challengerBonus !== 0 ? 'Bonus: ' . $this->challengerBonus . PHP_EOL : '')
            . ($challengerDisadvantage !== 0 ? 'Untrained disadvantage: ' . $challengerDisadvantage . PHP_EOL : '')
            . ($hitLocationDifficultyIncreate !== 0 ? 'Hit Location (' . $this->hitLocation['name'] . ') modifier:' . $hitLocationDifficultyIncreate . PHP_EOL : '')
            . '---' . PHP_EOL
            . '**Total: `' . $challengerTotal . '`**';

        $this->response->addField(
            $this->challenger['name'] . '`s ' . ucfirst($this->challengingAbility['name']) . ' check',
            $challengerDescription
        );

        if ($this->opposingAbilities !== null){
            try {
                $opposerAbility = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterIdAbilityIdSpecialisation(
                    $this->opposer['characterId'],
                    $this->opposingAbility['abilityId'],
                    '/'
                );
            } catch (DbRecordNotFoundException $e) {
                $opposerAbility = [
                    'characterId' => $this->opposer['characterId'],
                    'abilityId' => $this->opposingAbility['abilityId'],
                    'specialisation' => '/',
                    'value' => 0,
                    'used' => 0,
                    'wasUpdated' => 0
                ];
            }

            if ($opposerAbility['value'] === 0){
                $opposerDisadvantage = -10;
            }

            $opposerAbility['used'] = 1;
            $this->RAWBot->getDatabase()->getCharacterAbilities()->update($opposerAbility);

            $opposerRoll = DiceRoller::roll(20, $opposerCritical);
            $opposerTotal = $opposerAbility['value']
                + $this->opposingAbility['traitValue']
                + $opposerRoll
                + $opposerDisadvantage;
            if ($opposerCritical === DiceRoller::CRITICAL_SUCCESS){
                $opposerTotal += 20;
            } elseif ($opposerCritical === DiceRoller::CRITICAL_FAILURE){
                $opposerTotal -= 21;
            }

            $opposerDescription = 'Ability: ' . $opposerAbility['value'] .PHP_EOL
                . ucfirst($this->opposingAbility['traitName']) . ': ' . $this->opposingAbility['traitValue'] . PHP_EOL
                . 'Dice roll: ' . $opposerRoll . PHP_EOL
                . ($opposerCritical === DiceRoller::CRITICAL_FAILURE ? '**Critical Failure**: -20' . PHP_EOL : '')
                . ($opposerCritical === DiceRoller::CRITICAL_SUCCESS ? '**Critical Success**: 20' . PHP_EOL : '')
                . ($opposerDisadvantage !== 0 ? 'Untrained disadvantage: ' . $opposerDisadvantage . PHP_EOL : '')
                . '---' . PHP_EOL
                . '**Total: `' . $opposerTotal . '`**';

            $this->response->addField(
                $this->opposer['name'] . '`s ' . ucfirst($this->opposingAbility['name']) . ' check',
                $opposerDescription
            );
        } elseif ($this->challengingAbility['canBeOpposed']){
            $opposerTotal = 0;
        } else {
            $opposerTotal = 25;
        }

        if ($this->opposingAbilities !== null) {
            $this->response->setTitle(
                ucfirst($this->challengingAbility['name'])
                . ' Challenge of '
                . $this->challenger['name']
                . ' versus '
                . ucfirst($this->opposingAbility['name'])
                . ' of '
                . $this->opposer['name']
            );
        } else {
            $this->response->setTitle(
                ucfirst($this->challengingAbility['name'])
                . ' Challenge of '
                . $this->challenger['name']
                . ' against '
                . $this->opposer['name']
            );
        }

        if ($challengerTotal > $opposerTotal){
            $success = (int)(($challengerTotal-$opposerTotal)/25)+1;

            if (!$this->challengingAbility['canBeOpposed'] && $success > 1) {
                $success--;
            }

            if ($this->damage > 0 && $success > 0){
                $hitLocationMultiplier = 1;
                if ($this->hitLocation === null) {
                    $hitLocationRange = DiceRoller::simpleRoll(10);

                    try {
                        $this->hitLocation = $this->RAWBot->getDatabase()->getHitLocations()->loadByHitLocationRange($hitLocationRange);
                    } catch (DbRecordNotFoundException $e) {
                    }
                } else {
                    $hitLocationMultiplier = $this->hitLocation['damageMultiplier'];
                }

                $damage = $this->damage * $success * $hitLocationMultiplier;

                $this->opposer['damages'] += $damage;

                $this->RAWBot->getDatabase()->getCharacters()->update($this->opposer);
            }

            $this->response->setDescription(
                $this->challenger['name']
                    . ' wins the challenge'
            );

            $this->response->setDescription(
                $this->response->getDescription()
                    . ' with **' . $success . '** degrees of success'
            );

            if ($damage > 0){
                $additionalDamageDescription = '';
                if ($this->hitLocation !== null){
                    $additionalDamageDescription = '\'s ' . $this->hitLocation['name'];

                    if ($this->hitLocation['damageMultiplier'] !== 1){
                        $additionalDamageDescription .= '(' . $this->hitLocation['damageMultiplier'] . ' multiplier to damages)';
                    }
                }

                $this->response->setDescription(
                    $this->response->getDescription()
                    . ', delivering **' . $damage . '** to ' . $this->opposer['name']
                    . $additionalDamageDescription
                );

                if ($this->weapon !== null){
                    $this->response->setDescription(
                        $this->response->getDescription()
                        . ' using a ' . $this->weapon['name'] . '.'
                    );
                }

                $lifePoints = 40 + $this->opposer['body'] - $this->opposer['damages'];

                if ($lifePoints > 0){
                    $additionalDescription = ' has `' . $lifePoints . '` life remaining before becoming incapacitated';
                } elseif ($lifePoints > -$this->opposer['body']){
                    $additionalDescription = ' is now `incapacitated`';
                } else {
                    $additionalDescription = ' is now `dead`';
                    $this->response->setImage('https://media.giphy.com/media/g5Fjqgd4zcu40/giphy.gif');
                }

                $this->response->setDescription(
                    $this->response->getDescription() . PHP_EOL
                    . $this->opposer['name'] .
                    $additionalDescription
                );

                if ($this->opposingAbilities === null) {
                    $this->response->setDescription(
                        $this->response->getDescription() . PHP_EOL
                        . PHP_EOL . 'This challenge cannot be opposed'
                    );
                }
            }

            if (($this->opposer['isNPC'] & $this->challenger['isNPC']) === 0){
                if ($this->challenger['isNPC']){
                    $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_FAILURE);
                } else {
                    $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);
                }
            }
        } else {
            $this->response->setDescription(
                $this->opposer['name']
                    . ' successfully opposes the challenge' . PHP_EOL
            );

            if (($this->opposer['isNPC'] & $this->challenger['isNPC']) === 0){
                if ($this->challenger['isNPC']){
                    $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);
                } else {
                    $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_FAILURE);
                }
            }
        }
    }
}