<?php
namespace CarloNicora\RAWBot\Exceptions;

use Exception;

class RAWBotException extends Exception
{
    /** @var bool  */
    private bool $isPrivateMessage;

    public function __construct(string $message, bool $isPrivateMessage=false)
    {
        parent::__construct($message);

        $this->isPrivateMessage = $isPrivateMessage;
    }

    /**
     * @return bool
     */
    public function isPrivateMessage(): bool
    {
        return $this->isPrivateMessage;
    }
}