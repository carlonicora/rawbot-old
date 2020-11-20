<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Helpers\DiceRoller;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use Discord\Parts\Channel\Message;
use Exception;

class InitiativeFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $this->RAWBot->getDiscord()->registerCommand(
            'initiative',
            [$this, 'initiative']
        );

        $this->RAWBot->getDiscord()->registerAlias('i', 'initiative');
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function initiative(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER + self::GM)) {
            return;
        }

        if (!$this->server['inSession']){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::abilityRollsNotAvailableOutsideSessions());
        }

        $characters = [];

        if (array_key_exists(self::PARAMETER_PLAYER_CHARACTERS, $this->namedParameters)) {
            foreach ($this->namedParameters[self::PARAMETER_PLAYER_CHARACTERS] ?? [] as $playerCharacterIdentifier) {
                try {
                    $characters[] = $this->loadCharacterByParameter($playerCharacterIdentifier);
                } catch (DbRecordNotFoundException $e) {
                }
            }
        } else {
            $characters = $this->RAWBot->getDatabase()->getCharacters()->loadPlayerCharactersByServerId(
                $this->server['serverId']
            );
        }

        foreach ($this->namedParameters[self::PARAMETER_NON_PLAYER_CHARACTERS] ?? [] as $nonPlayerCharacterIdentifier){
            try {
                $characters[] = $this->loadCharacterByParameter($nonPlayerCharacterIdentifier);
            } catch (DbRecordNotFoundException $e) {
            }
        }

        foreach ($characters as $characterKey=>$character){
            try {
                $characterAbility = $this->RAWBot->getDatabase()->getCharacterAbilities()->loadBestCharacterInitiativeAbility(
                    $character['characterId']
                );

                $abilityValue = $characterAbility['value'];
                $characters[$characterKey]['initiativeAbility'] = $characterAbility['name'];
                $traitValue = $character[$characterAbility['trait']];
            } catch (DbRecordNotFoundException $e) {
                $abilityValue = 0;

                $characters[$characterKey]['initiativeAbility'] = 'trait';

                $traitValue = max(
                    $character['body'],
                    $character['mind'],
                    $character['spirit']
                );
            }

            $crititical = DiceRoller::CRITICAL_NONE;
            $roll = DiceRoller::roll(20, $crititical);
            if ($crititical === DiceRoller::CRITICAL_SUCCESS){
                $roll += 20;
            } elseif ($crititical === DiceRoller::CRITICAL_FAILURE){
                $roll -= 21;
            }

            $characters[$characterKey]['initiative'] = $abilityValue
                + $traitValue
                + $roll;
        }

        usort($characters, [$this, 'sortByInitiative']);

        $declarationOrder = '';
        $actionOrder = '';
        $description = '';

        foreach ($characters as $character){
            if ($character['discordUserId'] !== null){
                $name = '<@' . $character['discordUserId'] . '>';
            } else {
                $name = $character['name'] . ' (' . $character['shortName'] . ')';
            }

            if ($character === $characters[0]) {
                $description .= 'The initiative was won by ' . $name . PHP_EOL;
            }

            $name = '* ' . $name;

            $description .= $name . ' with `' . $character['initiative'] . '` on ' . $character['initiativeAbility'] . PHP_EOL;

            //$declarationOrder = $name . PHP_EOL . $declarationOrder;
            //$actionOrder .= $name . PHP_EOL;
            $actionOrder = $name . PHP_EOL . $actionOrder;
            $declarationOrder .= $name . PHP_EOL;
        }

        $description .= PHP_EOL
            . 'The actions should be declared in this order:' . PHP_EOL
            . $declarationOrder . PHP_EOL
            . '**AND** carried out in the opposite order:' . PHP_EOL
            . $actionOrder;

        $footer = 'Initiative' . PHP_EOL;

        $this->response->setTitle('Initiative Roll!');
        $this->response->setDescription($description);


        $this->response->setMessageType(DiscordMessage::MESSAGE_INFO);
        $this->response->setFooter($footer);

        //$this->response->setFooterThumbnail('https://cdn.onlinewebfonts.com/svg/img_308724.png');

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param array $characterA
     * @param array $characterB
     * @return int
     */
    private function sortByInitiative(array $characterA, array $characterB): int
    {
        return $characterA['initiative'] - $characterB['initiative'];
    }
}