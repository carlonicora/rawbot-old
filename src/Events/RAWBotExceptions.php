<?php
namespace CarloNicora\RAWBot\Events;

use CarloNicora\RAWBot\Exceptions\RAWBotException;

class RAWBotExceptions
{
    /**
     * @return RAWBotException
     */
    public static function bonusPointsNotAvailable(): RAWBotException
    {
        return new RAWBotException(
            'You have run out of bonus points, and you cannot access the bonuses'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function actionAllowedToGameMasterOnly(): RAWBotException
    {
        return new RAWBotException(
            'This action can be performed only by the GM'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function campaignNotInitialised(): RAWBotException
    {
        return new RAWBotException(
            'You need to initialise a campaign before running this command' . PHP_EOL
            .  'Just type `/campaign create "your campaign name"` to start!'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function nonPlayerCharacterActionRequestedByPlayer(): RAWBotException
    {
        return new RAWBotException(
            'Only the GM can manage Non Player Characters'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function characterNotFound(): RAWBotException
    {
        return new RAWBotException(
            'The character has not been found'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function characterNotInitialised(): RAWBotException
    {
        return new RAWBotException(
            'You need to initialise your character before running this command' . PHP_EOL
            . 'Just type `/character create "your character short name"` to crate yours!'
        );
    }

    /**
     * @param string $abilityName
     * @return RAWBotException
     */
    public static function abilityNotIncreasedBeforeUp(string $abilityName): RAWBotException
    {
        return new RAWBotException(
            'You cannot update the ability `' . $abilityName . '` as it has not been successfully increased at the end of the session.' . PHP_EOL
            . 'If you have used it during the session, you can still try and increase it sending the command `/bonus roll ' . $abilityName . '`.'
        );
    }

    /**
     * @param string $abilityName
     * @return RAWBotException
     */
    public static function abilityNotUsedBeforeUp(string $abilityName): RAWBotException
    {
        return new RAWBotException(
            'You cannot roll to update the ability `' . $abilityName . '` as it has not been used during the session.' . PHP_EOL
            . 'To update an ability you must have used in the past session.'
        );
    }

    /**
     * @param string $shortName
     * @return RAWBotException
     */
    public static function characterNameAlreadyExisting(string $shortName): RAWBotException
    {
        return new RAWBotException(
            'Sorry, a character with the same short name (`' . $shortName . '`) already exists for this campaign.' . PHP_EOL
            . 'Please pick another short name (character identifier) for your character.'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function campaignAlreadyInitialised(): RAWBotException
    {
        return new RAWBotException(
            'You already have a running campaign on this server.'
        );
    }

    /**
     * @param string $trait
     * @return RAWBotException
     */
    public static function incorrectTrait(string $trait): RAWBotException
    {
        return new RAWBotException(
            'The name of the trait you tried to set (' . $trait . ') is incorrect.' . PHP_EOL
            . 'The traits you can set are `body`, `mind` or `spirit`.'
        );
    }

    /**
     * @param string $trait
     * @return RAWBotException
     */
    public static function incorrectTraitValue(string $trait=''): RAWBotException
    {
        return new RAWBotException(
            'The value of the trait you tried to set ' . ($trait !== '' ? '(' . $trait . ')' : '') . ' is incorrect.' . PHP_EOL
            . 'You can only use numeric values'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function incorrectTraitsValue(): RAWBotException
    {
        return new RAWBotException(
            'In order to sett all the traits at the same time, you have to provide three numerical values for `body`, `mind` and `spirit`.'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function incorrectAbilityValue(): RAWBotException
    {
        return new RAWBotException(
            'The value of the ability you tried to set is incorrect.' . PHP_EOL
            . 'You can only use numeric values'
        );
    }

    /**
     * @param string $ability
     * @return RAWBotException
     */
    public static function incorrectAbility(string $ability): RAWBotException
    {
        return new RAWBotException(
            'The ability you tried to set (' . $ability . ') does not exist.'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function incorrectDamageValue(): RAWBotException
    {
        return new RAWBotException(
            'The value of the damage is incorrect.' . PHP_EOL
            . 'You can only use numeric values'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function bonusNotAvailableDuringSessions(): RAWBotException
    {
        return new RAWBotException(
            'You can only manage bonuses when a session is over.' . PHP_EOL
            . 'Play now, punp your character up later!'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function abilityRollsNotAvailableOutsideSessions(): RAWBotException
    {
        return new RAWBotException(
            'You can only roll your abilities during a game session.' . PHP_EOL
            . 'You can check your `/bonus` now!'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function sessionAlreadyStarted(): RAWBotException
    {
        return new RAWBotException(
            'The session has already started! Enjoy your game.' . PHP_EOL
            . 'You can use `/session end` to end the session!'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function sessionNotStarted(): RAWBotException
    {
        return new RAWBotException(
            'You are not in a session, so you can\'t stop it.' . PHP_EOL
            . 'You can use `/session start` to start a new session!'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function onlyNonPlayerCharactersCanBeCloned(): RAWBotException
    {
        return new RAWBotException(
            'Only Non Player Characters can be cloned.'
        );
    }

    /**
     * @param string $weaponName
     * @return RAWBotException
     */
    public static function weaponAlreadyExisting(string $weaponName): RAWBotException
    {
        return new RAWBotException(
            'A weapon with the name `' . $weaponName . '` already exists in the system. To create a new weapon, please use a unique name.'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function challengeNotAvailableDuringSessions(): RAWBotException
    {
        return new RAWBotException(
            'You can only challenge other character during a session.' . PHP_EOL
            . 'Ask the Game Master to start the session'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function challengeDoesNotSpecifyAnAbility(): RAWBotException
    {
        return new RAWBotException(
            'Sorry, you cannot challenge another character without specifying an ability.' . PHP_EOL
            . 'Run the command again by spcifying which ability you want to use, for example `/challenge empathy`.'
        );
    }

    /**
     * @param string $abilityName
     * @param array $challengers
     * @return RAWBotException
     */
    public static function abilityCannotChallenge(string $abilityName, array $challengers): RAWBotException
    {
        $validChallengers = '';
        foreach ($challengers ?? [] as $challenger){
            $validChallengers .= '`' . $challenger['name'] . '`,';
        }
        $validChallengers = substr($validChallengers, 0, -1);

        return new RAWBotException(
            'Sorry, you cannot challenge another character with `' . $abilityName . '`.' . PHP_EOL
            . 'You can challenge another character with one of the following abilities: ' . $validChallengers . ' .'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function opponentNotFound(): RAWBotException
    {
        return new RAWBotException(
            'Sorry, the opponent you typed cannot be found.' . PHP_EOL
            . 'Please make suer you typed the correct character short name.'
        );
    }

    /**
     * @return RAWBotException
     */
    public static function gmShouldPickOpponent(): RAWBotException
    {
        return new RAWBotException(
            'As the Game Master, you should pick the opponent of this challenge by typing `-o name` or `-o @DiscordPlayerName.' . PHP_EOL
            . 'The challenge is cancelled'
        );
    }

    /**
     * @param string $ability
     * @return RAWBotException
     */
    public static function abilityNotFoud(string $ability): RAWBotException
    {
        return new RAWBotException(
            'The ability you tried to use (' . $ability . ') does not exist.'
        );
    }

    /**
     * @param string $weaponName
     * @return RAWBotException
     */
    public static function weaponNotFound(string $weaponName): RAWBotException
    {
        return new RAWBotException(
            'The weapon you tried to use (' . $weaponName . ') does not exist.'
        );
    }


}