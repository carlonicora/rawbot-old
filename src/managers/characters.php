<?php
namespace carlonicora\rawbot\managers;

use carlonicora\minimalism\exceptions\dbRecordNotFoundException;
use carlonicora\minimalism\exceptions\dbUpdateException;
use carlonicora\rawbot\abstracts\abstractManagers;
use carlonicora\rawbot\helpers\rawErrors;
use carlonicora\rawbot\helpers\rawMessages;
use carlonicora\rawbot\helpers\tables;
use carlonicora\rawbot\objects\request;
use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;
use Exception;

class characters extends abstractManagers {
    /**
     * @param DiscordCommandClient $discord
     */
    public function registerCommands(DiscordCommandClient $discord): void{
        try {
            $command = $discord->registerCommand('character', [$this, 'init']);
            $discord->registerAlias('c', 'character');

            $command->registerSubCommand('list', array($this, 'list'), [
                'description'=>'Creates a new character for you'
            ]);

            $command->registerSubCommand('create', array($this, 'create'), [
                'description'=>'Creates a new character for you'
            ]);

            $command->registerSubCommand('name', array($this, 'name'), [
                'description'=>'Sets you character name'
            ]);

            $command->registerSubCommand('body', array($this, 'trait'), [
                'description'=>'Gets or Sets you character body trait'
            ]);

            $command->registerSubCommand('mind', array($this, 'trait'), [
                'description'=>'Gets or Sets you character mind trait'
            ]);

            $command->registerSubCommand('spirit', array($this, 'trait'), [
                'description'=>'Gets or Sets you character spirit trait'
            ]);
        } catch (Exception $e) {
            $this->configurations->logger->addError('init command failed to initialise');
        }
    }

    /**
     * @param Message $message
     */
    public function init(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER+self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        if ($this->characters[$request->discordServerId.$request->discordUserId] === NULL){
            $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_NOT_CREATED);
            return;
        }

        $variables = $this->generateCharacterRecordSheet($request);

        $this->sendResponse($message->channel, $request, rawMessages::CHARACTER, $variables);
    }

    /**
     * @param Message $message
     */
    public function list(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        try {
            $characters = tables::getCharacters()->loadFromServerId($this->servers[$request->discordServerId]['serverId']);
            $variables = [];

            foreach ($characters as $characterKey=>$character){
                $variables[] = [
                    'discordUserId'=>$character['discordUserId'],
                    'name'=>$character['name']
                ];
            }
            $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_LIST, $variables, false, true);
        } catch (dbRecordNotFoundException $e) {
            $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_NOT_FOUND);
            return;
        }
    }

    /**
     * @param Message $message
     */
    public function create(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER);
        } catch (Exception $e) {
            return;
        }

        if ($this->characters[$request->discordServerId.$request->discordUserId] === NULL){
            $this->characters[$request->discordServerId.$request->discordUserId] = [
                'serverId'=>$this->servers[$request->discordServerId]['serverId'],
                'discordUserId'=>$request->discordUserId,
                'discordUserName'=>$message->author->username,
                'name'=>$message->author->username
            ];

            try {
                tables::getCharacters()->update($this->characters[$request->discordServerId.$request->discordUserId]);
                $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_CREATED);
            } catch (dbUpdateException $e) {
                $this->sendError($message->channel, rawErrors::CHARACTER_CREATION_FAILED);
            }
        } else {
            $this->sendError($message->channel, rawErrors::CHARACTER_ALREADY_PRESENT);
        }
    }

    /**
     * @param Message $message
     */
    public function name(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER+self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        [$command, $subCommand] = str_getcsv(substr($message->content, 1), ' ');
        $newCharacterName = substr($message->content, 3+strlen($command)+strlen($subCommand));

        if (!empty($newCharacterName)){
            $this->characters[$request->discordServerId.$request->discordUserId]['name'] = $newCharacterName;

            try {
                tables::getCharacters()->update($this->characters[$request->discordServerId.$request->discordUserId]);
                $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_NAME_NEW, [
                    'name'=>$this->characters[$request->discordServerId.$request->discordUserId]['name']
                ]);
            } catch (dbUpdateException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_UPDATE_FAILED);
            }
        } else {
            $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_NAME, [
                'name'=>$this->characters[$request->discordServerId.$request->discordUserId]['name']
            ]);
        }
    }

    /**
     * @param Message $message
     */
    public function trait(Message $message): void {
        try {
            $request = $this->intialiseVariables($message, self::SERVER+self::CHARACTER);
        } catch (Exception $e) {
            return;
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        [$command, $traitName, $traitValue] = str_getcsv(substr($message->content, 1), ' ');

        if ($traitValue !== NULL && !is_int((int)$traitValue)){
            $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_TRAIT_NONINT);
            return;
        }

        $newValue = false;
        if ($traitValue !== NULL) {
            $newValue = true;
            $this->characters[$request->discordServerId.$request->discordUserId][$traitName] = $traitValue;
            try {
                tables::getCharacters()->update($this->characters[$request->discordServerId.$request->discordUserId]);
            } catch (dbUpdateException $e) {
                $this->sendError($message->channel, $request->discordUserId, rawErrors::CHARACTER_UPDATE_FAILED);
            }
        }

        $this->sendResponse($message->channel, $request, rawMessages::CHARACTER_TRAIT, [
            'character'=>$this->characters[$request->discordServerId.$request->discordUserId]['name'],
            'name'=>$traitName,
            'value'=>$this->characters[$request->discordServerId.$request->discordUserId][$traitName],
            'isNew'=>$newValue
        ]);
    }

    /**
     * @param request $request
     * @return array
     */
    private function generateCharacterRecordSheet(request $request): array {
        $character = $this->characters[$request->discordServerId.$request->discordUserId];

        $response['name'] = $character['name'];
        $response['traits'] = [
            $this->generateTraitAbilities($character, 'body'),
            $this->generateTraitAbilities($character, 'mind'),
            $this->generateTraitAbilities($character, 'spirit')
        ];

        return $response;
    }

    /**
     * @param array $character
     * @param string $traitName
     * @return array
     */
    private function generateTraitAbilities(array $character, string $traitName): array {
        $response = [
            'name'=>$traitName,
            'value'=>$character[$traitName],
            'abilities'=>[]
        ];
        try {
            $abilities = tables::getCharacterAbilities()->loadFromCharacterIdTrait($character['characterId'], $traitName);
            foreach ($abilities as $ability){
                $response['abilities'][$ability['name']] = [
                    'value'=>$ability['value'],
                    'used'=>$ability['used']
                ];
            }
        } catch (dbRecordNotFoundException $e) {
        }

        return $response;
    }
}