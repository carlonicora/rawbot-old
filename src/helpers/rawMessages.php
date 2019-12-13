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
                    '```1d20(' . $variables['roll'] . ') + trait(' . $variables['trait'] . ') + ability(' . $variables['ability'] . ')```';
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
                $response = 'The session is over and it is time to improve the ability the characters have used!' . PHP_EOL . PHP_EOL;

                foreach ($variables as $character){
                    $response .= '<@' . $character['discordUserId'] . '>: these are the results for `' . $character['name'] . '`: ' . PHP_EOL;
                    $response .= '```';
                    foreach ($character['abilities'] as $ability) {
                        $diff = $ability['roll'] - $ability['originalValue'] - $ability['traitValue'];
                        $response .= $ability['name'] . ': ' . $ability['value'] . PHP_EOL;
                        if ($ability['improvement'] > 0){
                            $response .= '    Improves by ' . $ability['improvement'] . ' points: ';
                        } else {
                            $response .= '    Failed to improved: ';
                        }
                        $response .= '[1d100(' . $ability['roll'] . ') - ' . $ability['name'] . '(' . $ability['originalValue'] . ') - ' . $ability['trait'] . ' (' . $ability['traitValue'] . ') = ' . $diff . ']' . PHP_EOL;
                    }
                    $response .= '```' . PHP_EOL . PHP_EOL;
                }
                break;
            default:
                $response = 'YES! I am not sure to what exactly, but yes!' . PHP_EOL .
                '...maybe it is better to let the developers know about this';
                break;
        }

        return $response;
    }
}