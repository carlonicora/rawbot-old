<?php
namespace CarloNicora\RAWBot\Configurations;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractServiceConfigurations;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\MySQL;
use CarloNicora\RAWBot\Events\RAWBotErrorEvents;
use Exception;

class RAWBotConfigurations extends AbstractServiceConfigurations
{
    /** @var string  */
    private string $discordToken;

    /** @var array|string[]  */
    protected array $dependencies = [
        MySQL::class,
    ];

    /**
     * RAWBotConfigurations constructor.
     * @param ServicesFactory $services
     * @throws Exception
     */
    public function __construct(ServicesFactory $services)
    {
        if (!($this->discordToken = getenv('DiscordToken'))){
            $services->logger()->error()->log(
                RAWBotErrorEvents::MISSING_CONFIGURATION('GRACE_CALCULATION_TIMEOUT')
            )->throw();
        }
    }

    /**
     * @return string
     */
    public function getDiscordToken(): string
    {
        return $this->discordToken;
    }
}