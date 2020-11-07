<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class CharacterFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $character = $this->RAWBot->getDiscord()->registerCommand(
            'character',
            [$this, 'showCharacter'],
            [
                'description' => 'Gives access to the character record sheet of your Character.' . PHP_EOL
                    . 'The Game Master can access a specific Player Character or Non Player Character record sheets by using the parameter `<shortName>` '
            ]
        );

        $this->RAWBot->getDiscord()->registerAlias('c', 'character');

        $character->registerSubCommand(
            'create',
            [$this, 'createCharacter'],
            [
                'description' => 'Creates a new Character. You must specify the parameter `<shortName>`, which is an identifier of the character, which must be a single word without spaces.' . PHP_EOL
                    . 'Using this function, the Game Master creates Non Player Characters'
            ]
        );

        $character->registerSubCommand(
            'list',
            [$this, 'listCharacters'],
            [
                'description' => 'Lists all the characters in the campaign.' . PHP_EOL
                    . 'Only the Game Master can access the list of all the Characters.' . PHP_EOL
                    . 'Players can access the list of all the Player Characters using the parameter `pcs`' . PHP_EOL
                    . 'The Game Master can access the list of all the Non Player Characters alone using the parameter `npcs`'
            ]
        );

        $character->registerSubCommand(
            'ability',
            [$this, 'listAbility'],
            [
                'description' => 'Lists the specific ability of the characters in the campaign. It requires the parameter `<ability>` to be passed' . PHP_EOL
                    . 'Only the Game Master can access the abilities of the Non Player Characters'
            ]
        );

        $character->registerSubCommand(
            'clone',
            [$this, 'cloneCharacter'],
            [
                'description' => 'The Game Master can clone a Non Player Character. It requires the parameter `<name of the new npc>` and `<name of the npc to be cloned>` to be passed'
            ]
        );

        $character->registerSubCommand(
            'delete',
            [$this, 'deleteCharacter'],
            [
                'description' => 'The Game Master can delete a Non Player Character. It requires the parameter `<name>` to be passed.' . PHP_EOL
                    . 'Please note, this action cannot be undone!'
            ]
        );
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function showCharacter(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER)) {
            return;
        }
        $character = $this->getCharacter($message->content);

        $this->response->setTitle($character['name'] . ' (' . $character['shortName'] . ')');
        $this->response->setDescription(empty($character['description']) ? '...' : $character['description']);

        $this->response->setIsPrivateMessage(true);

        if ($character['isNPC']){
            $this->response->setFooter('Non Player Character');
        } else {
            $this->response->setDescription(
                $this->response->getDescription() . PHP_EOL
                . '<@' . $character['discordUserId'] . '>');
            $this->response->setFooter('Player Character (' . $character['bonusPoints'] . ' bonus points available)');
        }

        $abilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterId(
            $character['characterId']
        );

        $abilityLines = [
            'body' => '',
            'mind' => '',
            'spirit' => ''
        ];
        $untrainedAbilities = [
            'body' => '',
            'mind' => '',
            'spirit' => ''
        ];

        $allAbilities = $this->RAWBot->getDatabase()->getAbilities()->loadAll();

        foreach ($allAbilities as $allAbility){
            $found = false;

            foreach ($abilities as $ability){
                if ($ability['abilityId'] === $allAbility['abilityId']){
                    $abilityLines[$ability['trait']] .= '* `' . ucfirst($ability['name']) . (($ability['specialisation'] === '/') ? '' : '/' . ucfirst($ability['specialisation'])) . '` (' . $ability['value'] . ')' . PHP_EOL;
                    $found = true;
                }
            }

            if (!$found){
                $untrainedAbilities[$allAbility['trait']] .= ucfirst($allAbility['name']) . ',';
            }
        }

        $body = $abilityLines['body'];
        if ($untrainedAbilities['body'] !== ''){
            $body .= '...untrained abilities: ' . substr($untrainedAbilities['body'], 0 ,-1);
        } else {
            $body .= '...no untrained abilities';
        }

        $mind = $abilityLines['mind'];
        if ($untrainedAbilities['mind'] !== ''){
            $mind .= '...untrained abilities: ' . substr($untrainedAbilities['mind'], 0 ,-1);
        } else {
            $mind .= '...no untrained abilities';
        }

        $spirit = $abilityLines['spirit'];
        if ($untrainedAbilities['spirit'] !== ''){
            $spirit .= '...untrained abilities: ' . substr($untrainedAbilities['spirit'], 0 ,-1);
        } else {
            $spirit .= '...no untrained abilities';
        }

        $this->response->addField(
            'body (' . $character['body'] . ')',
            $body
        );

        $this->response->addField(
            'mind (' . $character['mind'] . ')',
            $mind
        );

        $this->response->addField(
            'spirit (' . $character['spirit'] . ')',
            $spirit
        );

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function createCharacter(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $this->parameters = explode(' ', $message->content);
        $shortName = strtolower(array_pop($this->parameters));

        try {
            $this->RAWBot->getDatabase()->getCharacters()->loadByCharacterShortName(
                $this->server['serverId'],
                $shortName
            );
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::characterNameAlreadyExisting($shortName));
        } catch (DbRecordNotFoundException $e) {
            $this->response->setTitle('New character created!');
            $this->response->setDescription('Your new character, `' . $shortName . '` has been created.' . PHP_EOL
                . 'You can start setting all the character\'s details (name, description, thumbnail, traits, abilities) with the command `/set`.' . PHP_EOL
                . 'Here is what you should do:'
            );

            $this->response->addField(
                '/set name <full name here>',
                'Set the full name of your character'
            );

            $this->response->addField(
                '/set description <character description>',
                'Set a short description of your character'
            );

            $this->response->addField(
                '/set thumbnail <URL of an image representing your character>',
                'Set the thumbnail for character'
            );

            $this->response->addField(
                '/set traits <body value> <mind value> <spirit value>',
                'Set the traits for your character.' . PHP_EOL
                    . 'You can also set each trait using' . PHP_EOL
                    . '`/set trait body <value>` and `/set trait mind <value>` and `/set trait spirit <value>`' . PHP_EOL
                    . '*...but why using three calls when you can do all in one?*'
            );

            $this->response->addField(
                '/set ability <ability name> <ability value',
                'Set the value of each specific ability.' . PHP_EOL
                    . 'If you want to specify an ability specialisation, you can use the notation `ability/specialisation`. For example your character speaks Italian, you can say `/set ability language/italian 100`' . PHP_EOL
                    . '*PS: if you use the command `/character` you will get your character record sheet, where you can see every available ability.'
            );


            $character = [
                'serverId' => $this->server['serverId'],
                'shortName' => $shortName
            ];

            if ($this->amITheGM()){
                $character['isNPC'] = 1;
                $character['automaticallyAcceptChallenges'] = 1;
                $this->response->setIsPrivateMessage(true);
            } else {
                $character['isNPC'] = 0;
                $character['automaticallyAcceptChallenges'] = 0;
                $character['discordUserId'] = $this->RAWBot->getRequest()->getDiscordUserId();
            }

            $this->RAWBot->getDatabase()->getCharacters()->update($character);

            $this->response->setFooterThumbnail('https://cdn0.iconfinder.com/data/icons/users-groups-1/512/user-512.png');
            $this->response->setFooter($character['isNPC'] ? 'New Non Player Character Created' : 'New Player Character Created');
            $this->response->setMessageType(DiscordMessage::MESSAGE_ACTION_SUCCESS);

            $this->RAWBot->getDispatcher()->sendMessage($this->response);
        }
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function listCharacters(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER)) {
            return;
        }

        $this->response->setFooterThumbnail('https://cdn0.iconfinder.com/data/icons/users-groups-1/512/user-512.png');

        $this->parameters = explode(' ', $message->content);
        array_shift($this->parameters);
        array_shift($this->parameters);

        $characters = [];
        if (count($this->parameters) > 0){
            if (strtolower($this->parameters[0]) === 'pcs'){
                $characters = $this->RAWBot->getDatabase()->getCharacters()->loadPlayerCharactersByServerId(
                    $this->server['serverId']
                );

                $this->response->setTitle('Player Characters List');
                $this->response->setFooter('Player Characters');
            } elseif (strtolower($this->parameters[0]) === 'npcs'){
                if (!$this->amITheGM()){
                    $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::actionAllowedToGameMasterOnly());
                }

                $characters = $this->RAWBot->getDatabase()->getCharacters()->loadNonPlayerCharactersByServerId(
                    $this->server['serverId']
                );

                $this->response->setIsPrivateMessage(true);
                $this->response->setTitle('Non Player Characters List');
                $this->response->setFooter('Non Player Characters');
            }
        } else {
            if (!$this->amITheGM()){
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::actionAllowedToGameMasterOnly());
            }

            $characters = $this->RAWBot->getDatabase()->getCharacters()->loadNonPlayerCharactersByServerId(
                $this->server['serverId']
            );

            $this->response->setIsPrivateMessage(true);
            $this->response->setTitle('Characters List');
            $this->response->setFooter('Characters');
        }

        if (empty($characters)){
            $this->response->setDescription('There are no non player characters yet.');
        } else {
            foreach ($characters ?? [] as $character) {
                $this->response->addField(
                    $character['name'] . ' (' . $character['shortName'] . ')',
                    (empty($character['description']) ? '...' : $character['description'])  . ($character['isNPC'] ? '' : '<@' . $character['discordUserId'] . '>')
                );
            }
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function listAbility(Message $message): void
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

            $this->response->setTitle('Ability: ' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)));

            if ($ca === null){
                $this->response->setDescription('The character is untrained in `' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) . '`');
            } else {
                $this->response->setDescription('The character\'s `' . ucfirst($abilityName) . (($specialisation === '/') ? '' : '/' . ucfirst($specialisation)) . '` is ' . $ca['value']);
            }

            $this->response->setDescription(
                $this->response->getDescription() . PHP_EOL
                    . 'The ability is based on the trait `' . $ability['trait'] . '` which has a score of `' . $character[$ability['trait']] . '`'
            );

            $this->response->setIsPrivateMessage(true);

            $this->response->setFooterThumbnail('https://cdn0.iconfinder.com/data/icons/users-groups-1/512/user-512.png');
            $this->response->setFooter($character['isNPC'] ? 'Non Player Character' : 'Player Character');

            $this->RAWBot->getDispatcher()->sendMessage($this->response);
        } catch (DbRecordNotFoundException $e) {
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectAbility($this->parameters[0]));
        }
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function cloneCharacter(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER + self::GM)) {
            return;
        }

        $character = $this->getCharacter($message->content);

        if (!$character['isNPC']){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::onlyNonPlayerCharactersCanBeCloned());
        }

        $newCharacter = $character;
        unset($newCharacter['originalValues'], $newCharacter['characterId']);
        $newCharacter['shortName'] = strtolower($this->parameters[0]);

        $this->RAWBot->getDatabase()->getCharacters()->update($newCharacter);

        $characterAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterId($character['characterId']);

        $newCharacterAbilities = [];
        foreach ($characterAbilities ?? [] as $characterAbility) {
            $newCharacterAbility = $characterAbility;
            unset($newCharacterAbilities['originalValues']);
            $newCharacterAbility['characterId'] = $newCharacter['characterId'];
            $newCharacterAbility['used'] = 0;
            $newCharacterAbility['wasUpdated'] = 0;

            $newCharacterAbilities[] = $newCharacterAbility;
        }

        if ($newCharacterAbilities !== []) {
            $this->RAWBot->getDatabase()->getCharacterAbilities()->update($newCharacterAbilities);
        }

        $this->response->setTitle('Non Player Character Cloning');
        $this->response->setDescription('The Non Player Character `' . $character['shortName'] . '` has been successfully cloned to `' . $newCharacter['shortName'] . '`');
        $this->response->setIsPrivateMessage(true);

        $this->response->setFooterThumbnail('https://cdn0.iconfinder.com/data/icons/users-groups-1/512/user-512.png');
        $this->response->setFooter('Non Player Character');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function deleteCharacter(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, true, self::SERVER + self::GM)) {
            return;
        }

        $character = $this->getCharacter($message->content);

        if (!$character['isNPC']) {
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::onlyNonPlayerCharactersCanBeCloned());
        }

        $characterAbilities = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadByCharacterId($character['characterId']);

        $this->RAWBot->getDatabase()->getCharacterAbilities()->delete($characterAbilities);
        $this->RAWBot->getDatabase()->getCharacters()->delete($character);

        $this->response->setTitle('Non Player Character Deletion');
        $this->response->setDescription('The Non Player Character `' . $character['shortName'] . '` has been successfully deleted');
        $this->response->setIsPrivateMessage(true);
        $this->response->setFooterThumbnail('https://cdn0.iconfinder.com/data/icons/users-groups-1/512/user-512.png');
        $this->response->setFooter('Non Player Character');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }
}