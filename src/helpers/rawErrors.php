<?php
namespace carlonicora\rawbot\helpers;

use carlonicora\rawbot\configurations;

class rawErrors {
    public const ABILITY_MISSING=1;
    public const ABILITY_CREATION_FAILED=2;
    public const SERVER_ALREADY_PRESENT=3;
    public const SERVER_CREATION_FAILED=4;
    public const CHARACTER_CREATION_FAILED=5;
    public const CHARACTER_ALREADY_PRESENT=6;
    public const CHARACTER_NOT_CREATED=7;
    public const CHARACTER_UPDATE_FAILED=8;
    public const CHARACTER_TRAIT_NONINT=9;
    public const ABILITY_USED_FAILED=10;
    public const ABILITY_NONINT=11;
    public const ABILITY_UPDATE_FAILED=12;
    public const ABILITY_LIST_EMPTY=13;
    public const CHARACTER_IMPROVE_FAILED=14;
    public const SESSION_NON_MASTER=15;

    /** @var configurations */
    //private $configurations;

    /**
     * rawMessages constructor.
     * @param configurations $configurations
     */
    /*
    public function __construct(configurations $configurations){
        $this->configurations = $configurations;
    }
    */

    /**
     * @param int $errorCode
     * @return string
     */
    public function getMessage(int $errorCode): string {
        switch ($errorCode) {
            case self::ABILITY_LIST_EMPTY:
                $response = 'It seems the database does not have knowledge of any ability!' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::ABILITY_MISSING:
                $response = 'The ability you typed does not exist.' . PHP_EOL . PHP_EOL .
                    'To get a list of all the available abilities you can type:' . PHP_EOL .
                    '    /ability list';
                break;
            case self::ABILITY_CREATION_FAILED:
                $response = 'There has been a problem creating your character\'s ability.' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::ABILITY_USED_FAILED:
                $response = 'There has been a problem marking your character\'s ability as used.' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::SERVER_ALREADY_PRESENT:
                $response = 'This discord server already has a valid RAW campaign. ' .
                    'You cannot create another one.' . PHP_EOL . PHP_EOL .
                    'To get a list of the commands you can use, type `/help`';
                break;
            case self::SERVER_CREATION_FAILED:
                $response = 'There has been a problem creating your RAW campaign.' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::CHARACTER_CREATION_FAILED:
                $response = 'There has been a problem creating your character.' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::CHARACTER_ALREADY_PRESENT:
                $response = 'You already have a character for this RAW campaign. ' .
                    'You cannot create another one.' . PHP_EOL . PHP_EOL .
                    'To get a list of the commands you can use, type `/help`';
                break;
            case self::CHARACTER_NOT_CREATED:
                $response = 'It seems you haven\'t created your character yet. ' . PHP_EOL .
                    'You can get startd by typing `/character create` or `/c create`';
                break;
            case self::CHARACTER_UPDATE_FAILED:
                $response = 'There has been a problem updating your character.' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::ABILITY_UPDATE_FAILED:
                $response = 'There has been a problem updating your character\'s ability.' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::CHARACTER_TRAIT_NONINT:
                $response = 'The value of a character\'s trait must be a number.';
                break;
            case self::ABILITY_NONINT:
                $response = 'The value of a character\'s ability must be a number.';
                break;
            case self::CHARACTER_IMPROVE_FAILED:
                $response = 'There has been a problem improving the characters\' ability.' . PHP_EOL . PHP_EOL .
                    'Please contact the developers for support.';
                break;
            case self::SESSION_NON_MASTER:
                $response = 'Sorry, only the Game Master can terminate a session and allow the characters to improve their skills!';
                break;
            default:
                $response = 'OOOPS, it seems I cannot understand what happened here!';
                break;
        }

        return $response;
    }
}