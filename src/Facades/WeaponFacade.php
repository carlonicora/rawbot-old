<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use Discord\Parts\Channel\Message;
use Exception;

class WeaponFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $weapon = $this->RAWBot->getDiscord()->registerCommand(
            'weapon',
            [$this, 'showWeaponList'],
            [
                'description' => 'Lists all the weapons available'
            ]
        );

        $weapon->registerSubCommand(
            'add',
            [$this, 'addWeapon']
        );
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function showWeaponList(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER)) {
            return;
        }

        $weapons = $this->RAWBot->getDatabase()->getWeapons()->loadAll();

        $this->response->setTitle('List of weapons');
        $this->response->setDescription('Here you can find the list of all the weapons available in RAW. To use a weapon, just use its name.');
        foreach ($weapons as $weapon) {
            $this->response->addField(
                $weapon['name'],
                $weapon['description'] . PHP_EOL
                    . 'Delivers ' . $weapon['damage'] . ' damage per success'
            );
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function addWeapon(Message $message): void
    {
        $this->allowedParameters = ['name', 'damage'];

        if (!$this->initialiseVariables($message, true, self::SERVER + self::GM)) {
            return;
        }

        $weapon = [
            'name' => $this->namedParameters['name'],
            'damage' => $this->namedParameters['damage'],
            'description' => ''
        ];

        try {
            $this->RAWBot->getDatabase()->getWeapons()->loadByName($weapon['name']);

            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::weaponAlreadyExisting($weapon['name']));
        } catch (DbRecordNotFoundException $e) {
            $this->RAWBot->getDatabase()->getWeapons()->update($weapon);

            $this->response->setTitle('New weapon added');
            $this->response->setDescription('You have added a new weapon to the arsenal. A **' . $weapon['name'] . '** now delivers **' . $weapon['damage'] . '** for every success');

            $this->RAWBot->getDispatcher()->sendMessage($this->response);
        }
    }
}