<?php
namespace carlonicora\rawbot\abstracts;

use carlonicora\minimalism\exceptions\dbRecordNotFoundException;
use carlonicora\rawbot\configurations;
use carlonicora\rawbot\helpers\rawErrors;
use carlonicora\rawbot\helpers\rawMessages;
use carlonicora\rawbot\helpers\tables;
use carlonicora\rawbot\objects\request;
use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\User;
use Exception;
use RuntimeException;

abstract class abstractManagers {
    protected const SERVER=0b1;
    protected const CHARACTER=0b10;

    /** @var array */
    public $characters;

    /** @var configurations */
    protected $configurations;

    /** @var rawErrors */
    private $rawErrors;

    /** @var rawMessages */
    private $rawMessages;

    /** @var string */
    protected $response;

    /** @var array */
    public $servers;

    /**
     * abstractManagers constructor.
     * @param configurations $configurations
     * @param DiscordCommandClient $discord
     */
    public function __construct(configurations $configurations, DiscordCommandClient $discord){
        $this->configurations = $configurations;
        $this->rawErrors = new rawErrors();
        $this->rawMessages = new rawMessages();
        $this->registerCommands($discord);

        $this->servers = [];
        $this->characters = [];
    }

    /**
     * @param DiscordCommandClient $discord
     */
    abstract public function registerCommands(DiscordCommandClient $discord): void;

    /**
     * @param Message $message
     * @param int $requiredElements
     * @return request
     * @throws Exception
     */
    public function intialiseVariables(Message $message, int $requiredElements = 0): request {
        $response = new request();
        $response->discordServerId = $message->channel->guild_id;
        $response->discordUserId = $message->author->id;

        try {
            $this->loadServer($response->discordServerId, $requiredElements);
            $this->loadCharacter($response->discordServerId, $response->discordUserId, $requiredElements);
        } catch (Exception $e){
            $this->sendError($message->channel, $message->author->id, 0, $e->getMessage());
            throw $e;
        }

        return ($response);
    }

    /**
     * @param int $requiredElements
     * @param int $me
     * @return bool
     */
    private function amIrequired(int $requiredElements, int $me): bool {
        return (($requiredElements & $me) > 0);
    }

    /**
     * Loads the server of the Discord Guild
     * @param string $discordServerId
     * @param int $requiredElements
     */
    private function loadServer(string $discordServerId, int $requiredElements): void {
        try {
            $this->servers[$discordServerId] = tables::getServers()->loadFromDiscordServerId($discordServerId);
        } catch (dbRecordNotFoundException $e) {
            if ($this->amIrequired($requiredElements, self::SERVER)){
                throw new RuntimeException('ERROR: sorry a valid RAW campaign initialised is required. Just type `/campaign` to start!');
            }
        }
    }

    /**
     * @param string $discordServerId
     * @param string $discordUserId
     * @param int $requiredElements
     */
    private function loadCharacter(string $discordServerId, string $discordUserId, int $requiredElements):void {
        if ($this->servers[$discordServerId] !== NULL){
            try {
                $this->characters[$discordServerId.$discordUserId] = tables::getCharacters()->loadFromDiscordUserId($this->servers[$discordServerId]['serverId'], $discordUserId);
            } catch (dbRecordNotFoundException $e) {
                if ($this->amIrequired($requiredElements, self::CHARACTER)){
                    throw new RuntimeException('ERROR: sorry a valid RAW character is required. Just type `/character` to crate yours!');
                }
            }
        }
    }

    /**
     * @param Channel $channel
     * @param request $request
     * @param int $messageCode
     * @param array $variables
     * @param bool $notifyGameMaster
     * @param bool $forAllUsers
     */
    public function sendResponse(Channel $channel, request $request, int $messageCode, array $variables=[], bool $notifyGameMaster=false, bool $forAllUsers=false): void {
        $response = '';

        if (!$forAllUsers){
            $response .= '<@' . $request->discordUserId . '>: ';
        }

        $response .= $this->rawMessages->getMessage($messageCode, $variables);

        $channel->sendMessage($response);

        if ($notifyGameMaster){
            /** @var User $gameMaster */
            $gameMaster = $channel->guild->members[$this->servers[$request->discordServerId]['discordUserId']]->getUserAttribute();
            $gameMaster->sendMessage($response);
        }
    }

    /**
     * @param Channel $channel
     * @param string $discordUserId
     * @param int $errorCode
     * @param string $message
     */
    public function sendError(Channel $channel, string $discordUserId, int $errorCode=0, string $message=''): void {
        if ($discordUserId !== NULL){
            $response = '<@' . $discordUserId . '>: ';
        } else {
            $response = '';
        }

        $response .= 'rawBOT encountered an error' . PHP_EOL . '```';

        if ($errorCode === 0){
            $response .= $message;
        } else {
            $response .= $this->rawErrors->getMessage($errorCode);
        }

        $response .= '```';

        $channel->sendMessage($response);
    }
}