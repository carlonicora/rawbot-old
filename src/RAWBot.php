<?php
namespace CarloNicora\RAWBot;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceConfigurationsInterface;
use CarloNicora\RAWBot\Configurations\RAWBotConfigurations;
use CarloNicora\RAWBot\Data\Databases\Rawbot\RAWBotTables;
use CarloNicora\RAWBot\Facades\BonusFacade;
use CarloNicora\RAWBot\Facades\CampaignFacade;
use CarloNicora\RAWBot\Facades\ChallengeFacade;
use CarloNicora\RAWBot\Facades\CharacterFacade;
use CarloNicora\RAWBot\Facades\DamageFacade;
use CarloNicora\RAWBot\Facades\InitiativeFacade;
use CarloNicora\RAWBot\Facades\RollFacade;
use CarloNicora\RAWBot\Facades\SessionFacade;
use CarloNicora\RAWBot\Facades\SetFacade;
use CarloNicora\RAWBot\Facades\WeaponFacade;
use CarloNicora\RAWBot\Factories\MessageDispatcher;
use CarloNicora\RAWBot\Objects\DiscordRequest;
use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Channel;
use Exception;

class RAWBot extends AbstractService
{
    /** @var DiscordCommandClient  */
    private DiscordCommandClient $discord;

    /** @var RAWBotTables  */
    private RAWBotTables $database;

    /** @var MessageDispatcher|null  */
    private ?MessageDispatcher $dispatcher=null;

    /** @var DiscordRequest|null  */
    private ?DiscordRequest $request=null;

    /** @var Channel|null  */
    private ?Channel $discordChannel=null;

    /**
     * RAWBot constructor.
     * @param ServiceConfigurationsInterface|RAWBotConfigurations $configData
     * @param ServicesFactory $services
     * @throws Exception
     */
    public function __construct(serviceConfigurationsInterface $configData, ServicesFactory $services) {
        parent::__construct($configData, $services);

        $this->database = new RAWBotTables($this->services);



        $this->discord = new DiscordCommandClient([
            'token'=>$configData->getDiscordToken(),
            'prefix'=>'/',
            'name'=>'raw',
            'description'=>'Discord bot for RAW Role Playing Game version 2',
            'discordOptions' => [
                'pmChannels' => true,
                'loadAllMembers' => true
            ]
        ]);
    }

    /**
     * @throws Exception
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function start() : void
    {
        new CampaignFacade($this->services);
        new CharacterFacade($this->services);
        new SetFacade($this->services);
        new BonusFacade($this->services);
        new RollFacade($this->services);
        new DamageFacade($this->services);
        new SessionFacade($this->services);
        new WeaponFacade($this->services);
        new ChallengeFacade($this->services);
        new InitiativeFacade($this->services);

        $this->discord->run();
    }

    /**
     * @return Channel
     */
    public function getDiscordChannel(): Channel
    {
        return $this->discordChannel;
    }

    /**
     * @param Channel $discordChannel
     */
    public function setDiscordChannel(Channel $discordChannel): void
    {
        $this->discordChannel = $discordChannel;
    }

    /**
     * @return DiscordRequest
     */
    public function getRequest(): DiscordRequest
    {
        if ($this->request === null){
            $this->request = new DiscordRequest();
        }

        return $this->request;
    }

    /**
     * @return MessageDispatcher
     * @throws Exception
     */
    public function getDispatcher(): MessageDispatcher
    {
        if ($this->dispatcher === null){
            $this->dispatcher = new MessageDispatcher($this->services);
        }

        return $this->dispatcher;
    }

    /**
     * @return DiscordCommandClient
     */
    public function getDiscord(): DiscordCommandClient
    {
        return $this->discord;
    }

    /**
     * @return RAWBotTables
     */
    public function getDatabase(): RAWBotTables
    {
        return $this->database;
    }
}