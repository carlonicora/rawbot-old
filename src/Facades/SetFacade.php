<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class SetFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $set = $this->RAWBot->getDiscord()->registerCommand(
            'set',
            [$this, 'replyWithOptions']
        );

        $set->registerSubCommand(
            'name',
            [$this, 'setCharacterName']
        );

        $set->registerSubCommand(
            'thumbnail',
            [$this, 'setCharacterThumbnail']
        );

        $set->registerSubCommand(
            'description',
            [$this, 'setCharacterDescription']
        );

        $set->registerSubCommand(
            'trait',
            [$this, 'setCharacterTrait']
        );

        $set->registerSubCommand(
            'traits',
            [$this, 'setCharacterTraits']
        );

        $set->registerSubCommand(
            'ability',
            [$this, 'setCharacterAbility']
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

        $this->response->setTitle('Character set helper');
        $this->response->setDescription('With this command you can set the following for your character:');

        $this->response->addField(
            'name',
            '`/set name <name>`' . PHP_EOL
            . 'Sets the full name of your character',
        );

        $this->response->addField(
            'description',
            '`/set description <description>`' . PHP_EOL
            . 'Sets the short description of your character',
        );

        $this->response->addField(
            'thumbnail',
            '`/set thumbnail <description>`' . PHP_EOL
            . 'Sets the thumbnail for character',
        );

        $this->response->addField(
            'trait',
            '`/set trait <body/mind/spirit> <value>`' . PHP_EOL
                . 'Sets the value of a specific trait of your character'
        );

        $this->response->addField(
            'traits',
            '`/set traits <body value> <mind value> <spirit value>`' . PHP_EOL
                . 'Sets the value of all the traits of your character'
        );

        $this->response->addField(
            'ability',
            '`/set ability <ability name> <value>`' . PHP_EOL
            . 'Sets the value of a specific ability of your character'
        );

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function setCharacterName(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $character = $this->getCharacter($message->content);

        $name = implode(' ', $this->parameters);
        $character['name'] = $name;

        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $this->response->setTitle('Name');
        $this->response->setDescription('The name of the character has been successfully changed to `' . $name . '`');
        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function setCharacterThumbnail(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $character = $this->getCharacter($message->content);

        $thumbnail = implode(' ', $this->parameters);
        $character['thumbnail'] = $thumbnail;

        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $this->response->setTitle('Name');
        $this->response->setDescription('The thumbnail of the character has been successfully changed.');
        $this->response->setThumbnailUrl($thumbnail);
        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function setCharacterDescription(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $character = $this->getCharacter($message->content);

        $description = implode(' ', $this->parameters);
        $character['description'] = $description;

        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $this->response->setTitle('Description');
        $this->response->setDescription('The description of the character has been successfully changed');
        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function setCharacterTrait(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $character = $this->getCharacter($message->content);

        if (!in_array(strtolower($this->parameters[0]), ['body','mind','spirit'])){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectTrait($this->parameters[0]));
        }

        if (!is_numeric($this->parameters[1])){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectTraitValue($this->parameters[0]));
        }

        $character[strtolower($this->parameters[0])] = (int)$this->parameters[1];

        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $this->response->setTitle('Trait');
        $this->response->setDescription('The ' . $this->parameters[0] . ' of the character has been successfully changed');
        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function setCharacterTraits(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $character = $this->getCharacter($message->content);

        if (count($this->parameters) !== 3){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectTraitsValue());
        }

        if (!is_numeric($this->parameters[0]) || !is_numeric($this->parameters[1]) || !is_numeric($this->parameters[2])){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectTraitValue());
        }

        $character['body'] = (int)$this->parameters[0];
        $character['mind'] = (int)$this->parameters[1];
        $character['spirit'] = (int)$this->parameters[2];

        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $this->response->setTitle('Traits');
        $this->response->setDescription('The traits of the character has been successfully changed');
        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function setCharacterAbility(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $character = $this->getCharacter($message->content);

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

        if (!is_numeric($this->parameters[1])){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectAbilityValue());
        }

        $ability = null;

        try {
            $ability = $this->RAWBot->getDatabase()->getAbilities()->loadByName($abilityName);
        } catch (DbRecordNotFoundException $e) {
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectAbility($this->parameters[0]));
        }

        try {
            $characterAbility = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterIdAbilityIdSpecialisation(
                $character['characterId'],
                $ability['abilityId'],
                $specialisation
            );
        } catch (DbRecordNotFoundException $e) {
            $characterAbility = [
                'characterId' => $character['characterId'],
                'abilityId' => $ability['abilityId'],
                'specialisation' => $specialisation,
                'used' => 0,
                'wasUpdated' => 0
            ];
        }

        $characterAbility['value'] = (int)$this->parameters[1];

        $this->RAWBot->getDatabase()->getCharacterAbilities()->update($characterAbility);

        $this->response->setTitle('Ability');
        $this->response->setDescription('The ability `' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) . '` of the character has been successfully changed.');
        $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }
}