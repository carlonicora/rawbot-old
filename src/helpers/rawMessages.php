<?php
namespace carlonicora\rawbot\helpers;

use carlonicora\rawbot\configurations;

class rawMessages {
    public const CAMPAIGN_CREATED=1;
    public const CHARACTER_CREATED=2;
    public const CHARACTER=3;
    public const CHARACTER_NAME=4;
    public const CHARACTER_NAME_NEW=5;
    public const CHARACTER_TRAIT=6;
    public const ABILITY_CHECK=7;
    public const ABILITY_UPDATE=8;
    public const ABILITY_LIST=9;
    public const CHARACTER_IMPROVE=10;
    public const BONUS_COUNT=11;
    public const BONUS_UP=12;
    public const BONUS_ROLL=13;
    public const GM_WELCOME=14;
    public const SESSION_STARTED=15;
    public const CHARACTER_AWARDED=16;

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
     * @param int $messageCode
     * @param array $variables
     * @return string
     */
    public function getMessage(int $messageCode, array $variables): string {
        switch ($messageCode){
            case self::CAMPAIGN_CREATED:
                $response = 'Welcome to your new RAW campaign. You are now ready to go' . PHP_EOL .
                    'To get a list of the commands you can use, type `/help`';
                break;
            case self::CHARACTER_CREATED:
                $response = 'Your new character is ready to go! ' . PHP_EOL .
                'Why don\'t you give them a name using the command `/character name *YourCharacterNameHere*`?';
                break;
            case self::CHARACTER:
                $response = 'Hail to the mighty ' . $variables['name'];
                $response .= '```';

                foreach ($variables['traits'] as $trait){
                    $response .= $trait['name'] . ': ' . $trait['value'] . PHP_EOL;
                    foreach ($trait['abilities'] as $abilityName=>$ability){
                        $response .= '    ' . $abilityName . ': ' . $ability['value'];
                        if ($ability['used']){
                            $response .= ' *';
                        }
                        $response .= PHP_EOL;
                    }
                }

                $response .= '```';
                break;
            case self::CHARACTER_NAME:
                $response = 'Your character name is `' .
                    $variables['name'] .
                    '`';
                break;
            case self::CHARACTER_NAME_NEW:
                $response = 'From now on, your character will be known as `' .
                    $variables['name'] .
                    '`';
                break;
            case self::CHARACTER_TRAIT:
                $response = $variables['character'] . '\'s ';
                if ($variables['isNew']){
                    $response .= 'new ';
                }
                $response .= $variables['name'];
                if ($variables['value'] === NULL){
                    $response .= ' has not been set yet!' . PHP_EOL .
                        '```You can set your character\'s ' . $variables['name'] . ' by typing ' .
                        '`/character ' . $variables['name'] . ' *Value*`.```';
                } else {
                    $response .= ' is `' . $variables['value'] . '`';
                }
                break;
            case self::ABILITY_UPDATE:
                $response = $variables['character'] . '\'s `' . $variables['abilityName'] . '` ability is `' . $variables['abilityValue'] . '`';

                break;
            case self::ABILITY_CHECK:
                $response = '`' . $variables['abilityName'] . '` check: *`' . $variables['result'] . '`*' . PHP_EOL .
                    '```1d20(' . $variables['roll'] . ') + trait(' . $variables['trait'] . ') + ability(' . $variables['ability'] . ')';
                if ($variables['bonus'] !== 0){
                    $response .= ' + bonus (' . $variables['bonus'] . ')';
                }
                $response .= '```';
                break;
            case self::ABILITY_LIST:
                $response = 'List of abilities in RAW:' . PHP_EOL;

                foreach ($variables as $traitName=>$abilities){
                    $response .=  $traitName . ':' . PHP_EOL;
                    foreach ($abilities as $ability){
                        $response .= '    ' . $ability . PHP_EOL;
                    }
                }

                break;
            case self::CHARACTER_IMPROVE:
                $response = '`' . $variables['characterName'] . '` improvements:' . PHP_EOL ;

                foreach ($variables['abilities'] as $ability){
                    if ($ability['improvement'] > 0){
                        $response .= '    `' . $ability['name'] . '`: **+' . $ability['improvement'] . '** > ' . $ability['value'] . ' ';
                    } else {
                        $response .= '    *' . $ability['name'] . ': not improved* ';
                    }
                    $response .= '(rolled ' . $ability['roll'] . ')' . PHP_EOL;
                }
                break;
            case self::BONUS_COUNT:
                $response = 'you have ' . $variables['bonuses'] . ' bonus points at your disposal';
                break;
            case self::BONUS_UP:
                $response = 'you have increased your character\'s `' . $variables['abilityName'] . '` to `' . $variables['abilityValue'] . '`!';
                break;
            case self::BONUS_ROLL:
                $diff = $variables['roll'] - $variables['originalValue'] - $variables['traitValue'];
                if ($variables['improvement'] > 0){
                    $response = 'you have increased your character\'s `' . $variables['name'] .
                        '` by ' . $variables['improvement'] . ' points to a total of `' .  $variables['value']. '`' . PHP_EOL .
                        '```[1d100(' . $variables['roll'] . ') - ' . $variables['name'] . '(' . $variables['originalValue'] . ') - ' . $variables['trait'] . ' (' . $variables['traitValue'] . ') = ' . $diff . ']```' . PHP_EOL;
                } else {
                    $response = 'you have failed to increased your character\'s `' . $variables['name'] . '`' . PHP_EOL .
                        '```[1d100(' . $variables['roll'] . ') - ' . $variables['name'] . '(' . $variables['originalValue'] . ') - ' . $variables['trait'] . ' (' . $variables['traitValue'] . ') = ' . $diff . ']```' . PHP_EOL;
                }
                break;
            case self::GM_WELCOME:
                $response = 'Hail <@' . $variables['gm'] . '>, ruler of the universe!';
                break;
            case self::SESSION_STARTED:
                $response = 'The session has started!' . PHP_EOL .
                    'from now on every ability check rolled with `/a *abilityname*` will count to increase your ' .
                    'character\'s abilities and the possbility to use any bonus point has been ' .
                    'halted until the end of the session.' . PHP_EOL .
                    '**enjoy your session**!';
                break;
            case self::CHARACTER_AWARDED:
                $response = '';
                foreach ($variables['characters'] as $character){
                    $response .= '<@' . $character . '> ';
                }
                $response .= 'you have been rewarded with ' . $variables['award'] . ' bonus points!';
                break;
            default:
                $response = 'YES! I am not sure to what exactly, but yes!' . PHP_EOL .
                '...maybe it is better to let the developers know about this';
                break;
        }

        return $response;
    }
}