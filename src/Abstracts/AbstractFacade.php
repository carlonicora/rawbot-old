<?php
namespace CarloNicora\RAWBot\Abstracts;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\RAWBot\Events\RAWBotExceptions;
use CarloNicora\RAWBot\Interfaces\FacadeInterface;
use CarloNicora\RAWBot\Objects\DiscordMessage;
use CarloNicora\RAWBot\RAWBot;
use Discord\Parts\Channel\Message;
use Exception;
use RuntimeException;

abstract class AbstractFacade implements FacadeInterface
{
    protected const SERVER=0b1;
    protected const CHARACTER=0b10;
    protected const GM=0b100;

    protected const PARAMETER_THRESHOLD=1001;
    protected const PARAMETER_DAMAGE=1002;
    protected const PARAMETER_OPPONENT=1003;
    protected const PARAMETER_NON_PLAYER_CHARACTER=1004;
    protected const PARAMETER_PLAYER_CHARACTER=1006;
    protected const PARAMETER_WEAPON=1005;

    /** @var ServicesFactory  */
    protected ServicesFactory $services;

    /** @var RAWBot  */
    protected RAWBot $RAWBot;

    /** @var array|null  */
    protected ?array $server=null;

    /** @var array|null  */
    protected ?array $character=null;

    /** @var DiscordMessage  */
    protected DiscordMessage $response;

    /** @var array  */
    protected array $parameters=[];

    /** @var array  */
    protected array $namedParameters=[];

    /** @var array  */
    protected array $allowedParameters=[];

    /** @var string  */
    protected string $additionalMessage='';

    /**
     * AbstractFacade constructor.
     * @param ServicesFactory $services
     * @throws Exception
     */
    public function __construct(ServicesFactory $services)
    {
        $this->services = $services;
        $this->RAWBot = $this->services->service(RAWBot::class);

        $this->registerCommands();
    }

    /**
     *
     */
    abstract public function registerCommands(): void;

    /**
     * @param Message $message
     * @param bool $isSubCommand
     * @param int $requiredElements
     * @return bool
     * @throws Exception
     */
    public function initialiseVariables(Message $message, bool $isSubCommand, int $requiredElements=0): bool
    {
        $this->RAWBot->setDiscordChannel($message->channel);

        $this->server = null;
        $this->character = null;
        $this->parameters=[];
        $this->namedParameters=[];
        $this->additionalMessage='';

        $this->RAWBot->getRequest()->setDiscordServerId($message->channel->guild_id);
        $this->RAWBot->getRequest()->setDiscordUserId($message->author->id);

        $this->loadServer($requiredElements);

        if ($this->server !== null) {
            if (!$this->isUserAllowed($requiredElements)){
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::actionAllowedToGameMasterOnly());
            }

            $this->loadCharacter($requiredElements);
        }

        $this->response = new DiscordMessage($this->services);

        $this->initialiseNamedParameters($message->content, $isSubCommand);

        return true;
    }

    /**
     * @param string $command
     * @param bool $isSubCommand
     * @throws Exception
     */
    private function initialiseNamedParameters(string $command, bool $isSubCommand): void
    {
        $parameters = explode(' ', $command);

        $skipNext = false;
        $allowedParameters = 0;

        foreach ($parameters as $parameterKey=>$parameter){
            if ($skipNext){
                $skipNext = false;
                continue;
            }

            if ($parameterKey !== 0 && !($isSubCommand && $parameterKey === 1)) {
                if (strpos($parameter, '-') === 0 && !is_numeric(substr($parameter, 1))) {
                    if (str_contains($parameter, '=')) {
                        [$parameterName, $parameterValue] = explode('=', substr($parameter, 1));
                    } else {
                        $parameterName = substr($parameter, 1);
                        if (!array_key_exists($parameterKey + 1, $parameters)) {
                            $parameterValue = '';
                        } else {
                            $parameterValue = $parameters[$parameterKey + 1];
                            $skipNext = true;
                        }
                    }

                    switch (strtolower($parameterName)) {
                        case 't':
                        case 'threshold':
                            $this->namedParameters[self::PARAMETER_THRESHOLD] = $parameterValue;
                            break;
                        case 'd':
                        case 'damage':
                            $this->namedParameters[self::PARAMETER_DAMAGE] = $parameterValue;
                            break;
                        case 'o':
                        case 'opponent':
                            $this->namedParameters[self::PARAMETER_OPPONENT] = $parameterValue;
                            break;
                        case 'npc':
                            $this->namedParameters[self::PARAMETER_NON_PLAYER_CHARACTER] = $parameterValue;
                            break;
                        case 'pc':
                            $this->namedParameters[self::PARAMETER_PLAYER_CHARACTER] = $parameterValue;
                            break;
                        case 'w':
                        case 'weapon':
                            try {
                                $this->namedParameters[self::PARAMETER_WEAPON] = $this->RAWBot->getDatabase()->getWeapons()->loadByName($parameterValue);
                                $this->namedParameters[self::PARAMETER_DAMAGE] = $this->namedParameters[self::PARAMETER_WEAPON]['damage'];
                            } catch (DbRecordNotFoundException $e) {
                                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::weaponNotFound($parameterValue));
                                throw new RuntimeException('');
                            }
                            break;
                    }
                } elseif (array_key_exists($allowedParameters, $this->allowedParameters)){
                    $this->namedParameters[$this->allowedParameters[$allowedParameters]] = $parameter;
                    $allowedParameters++;
                } else {
                    $this->parameters[] = $parameter;
                }
            }
        }
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
     * @param int $requiredElements
     * @return bool
     */
    private function isUserAllowed(int $requiredElements): bool
    {
        if (($requiredElements & self::GM) > 0) {
            return $this->amITheGM();
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function amITheGM(): bool
    {
        return $this->server['discordUserId'] === $this->RAWBot->getRequest()->getDiscordUserId();
    }

    /**
     * Loads the server of the Discord Guild
     * @param int $requiredElements
     * @throws Exception
     */
    private function loadServer(int $requiredElements): void
    {
        try {
            $this->server = $this->RAWBot->getDatabase()->getServers()->loadByDiscordServerId(
                $this->RAWBot->getRequest()->getDiscordServerId()
            );
        } catch (DbRecordNotFoundException $e) {
            if ($this->amIrequired($requiredElements, self::SERVER)){
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::campaignNotInitialised());
            }
        }
    }

    /**
     * @param string $characterShortName
     * @throws Exception
     */
    public function loadCharacterByShortName(string $characterShortName): void
    {
        try {
            $this->character = $this->RAWBot->getDatabase()->getCharacters()->loadByCharacterShortName(
                $this->server['serverId'],
                $characterShortName
            );

            if ($this->character['isNPC'] === true && !$this->amITheGM()){
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::nonPlayerCharacterActionRequestedByPlayer());
            }
        } catch (DbRecordNotFoundException $e) {
            $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::characterNotFound());
        }
    }

    /**
     * @param int $requiredElements
     * @throws Exception
     */
    private function loadCharacter(int $requiredElements):void
    {
        try {
            $this->character = $this->RAWBot->getDatabase()->getCharacters()->loadByDiscordUserId(
                $this->server['serverId'],
                $this->RAWBot->getRequest()->getDiscordServerId()
            );
        } catch (DbRecordNotFoundException $e) {
            $this->character = null;

            if ($this->amIrequired($requiredElements, self::CHARACTER)){
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::characterNotInitialised());
            }
        }
    }

    /**
     * @param string $command
     * @param int $shifts
     * @param bool $skipError
     * @return array
     * @throws Exception
     */
    protected function getCharacter(string $command, int $shifts = 2, bool $skipError=false): array
    {
        $response = [];

        $this->parameters = explode(' ', $command);

        if ($this->amITheGM()){
            if (array_key_exists(self::PARAMETER_NON_PLAYER_CHARACTER, $this->namedParameters)) {
                $shortName = $this->namedParameters[self::PARAMETER_NON_PLAYER_CHARACTER];
            } elseif (array_key_exists(self::PARAMETER_PLAYER_CHARACTER, $this->namedParameters)) {
                $shortName = $this->namedParameters[self::PARAMETER_PLAYER_CHARACTER];
            } else {
                $shortName = array_pop($this->parameters);
            }


            try {

                if (strpos($shortName, '<@') === 0){
                    $response = $this->RAWBot->getDatabase()->getCharacters()->loadByDiscordUserId($this->server['serverId'], substr($shortName, 3, -1));
                } else {
                    $response =$this->RAWBot->getDatabase()->getCharacters()->loadByCharacterShortName($this->server['serverId'], strtolower($shortName));
                }
            } catch (DbRecordNotFoundException $e) {
                if ($skipError) {
                    throw new RuntimeException('');
                }
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::characterNotFound());
            }
        } else {
            try {
                $response = $this->RAWBot->getDatabase()->getCharacters()->loadByDiscordUserId(
                    $this->server['serverId'],
                    $this->RAWBot->getRequest()->getDiscordUserId()
                );
            } catch (DbRecordNotFoundException $e) {
                if ($skipError) {
                    throw new RuntimeException('');
                }
                $this->RAWBot->getDispatcher()->sendError(RAWBotExceptions::characterNotInitialised());
            }
        }

        for ($shift=0; $shift<$shifts; $shift++){
            array_shift($this->parameters);
        }

        if ($response['thumbnail'] !== null){
            $this->response->setThumbnailUrl($response['thumbnail']);
        }

        if (!$response['isNPC']) {
            $this->response->setAdditionalMessage('<@' . $response['discordUserId'] . '>');
        }

        return $response;
    }
}