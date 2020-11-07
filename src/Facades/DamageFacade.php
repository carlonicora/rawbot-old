<?php
namespace CarloNicora\RAWBot\Facades;

use CarloNicora\RAWBot\Abstracts\AbstractFacade;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use Discord\Parts\Channel\Message;
use Exception;

class DamageFacade extends AbstractFacade
{
    /**
     * @throws Exception
     */
    public function registerCommands(): void
    {
        $this->RAWBot->getDiscord()->registerCommand(
            'damage',
            [$this, 'inflictDamage']
        );

        $this->RAWBot->getDiscord()->registerCommand(
            'recover',
            [$this, 'recoverDamage']
        );
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function inflictDamage(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER+self::GM)) {
            return;
        }

        $character = $this->getCharacter($message->content, 1);

        if (!is_numeric($this->parameters[0])){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectDamageValue());
        }

        $character['damages'] += $this->parameters[0];

        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $lifePoints = 40 + $character['body'] - $character['damages'];

        $this->response->setTitle('Damages inflicted to ' . $character['name']);

        if ($lifePoints > 0){
            $additionalDescription = ' has `' . $lifePoints . '` life remaining before becoming incapacitated';
        } elseif ($lifePoints > -$character['body']){
            $additionalDescription = ' is now `incapacitated`';
        } else {
            $additionalDescription = ' is now `dead`';
            $this->response->setImage('https://media.giphy.com/media/g5Fjqgd4zcu40/giphy.gif');
        }

        $this->response->setDescription(
            $character['name'] . ' received `' . $this->parameters[0] . '` damages' . PHP_EOL
            . 'The total damage sustained by the character is ' . $character['damages'] . PHP_EOL
            . '---' . PHP_EOL
            . $character['name'] . $additionalDescription
        );

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }

    /**
     * @param Message $message
     * @throws Exception
     */
    public function recoverDamage(Message $message): void
    {
        $this->allowedParameters=[];
        if (!$this->initialiseVariables($message, false, self::SERVER+self::GM)) {
            return;
        }

        $character = $this->getCharacter($message->content, 1);

        if ($character['damages'] === 0){
            return;
        }

        if (!is_numeric($this->parameters[0])){
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::incorrectDamageValue());
        }

        $character['damages'] -= $this->parameters[0];

        if ($character['damages'] < 0){
            $character['damages'] = 0;
        }

        $this->RAWBot->getDatabase()->getCharacters()->update($character);

        $lifePoints = 40 + $character['body'] - $character['damages'];

        $this->response->setTitle('Damages recovered by ' . $character['name']);

        if ($lifePoints > 0){
            $additionalDescription = ' has `' . $lifePoints . '` life remaining before becoming incapacitated';
        } elseif ($lifePoints > -$character['body']){
            $additionalDescription = ' is now `incapacitated`';
        } else {
            $additionalDescription = ' is now `dead`';
            $this->response->setImage('https://media.giphy.com/media/g5Fjqgd4zcu40/giphy.gif');
        }

        $this->response->setDescription(
            $character['name'] . ' recovered `' . $this->parameters[0] . '` damages' . PHP_EOL
            . 'The total damage on the character is ' . $character['damages'] . PHP_EOL
            . '---' . PHP_EOL
            . $character['name'] . $additionalDescription
        );

        if ($character['isNPC']){
            $this->response->setIsPrivateMessage(true);
        }

        $this->RAWBot->getDispatcher()->sendMessage($this->response);
    }
}