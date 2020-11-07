<?php
namespace CarloNicora\RAWBot\Factories;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractServiceFactory;
use CarloNicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\RAWBot\Configurations\RAWBotConfigurations;
use CarloNicora\RAWBot\RAWBot;
use Exception;

class ServiceFactory extends AbstractServiceFactory
{
    /**
     * serviceFactory constructor.
     * @param ServicesFactory $services
     * @throws Exception|ConfigurationException
     */
    public function __construct(ServicesFactory $services)
    {
        $this->configData = new RAWBotConfigurations($services);

        parent::__construct($services);
    }

    /**
     * @param ServicesFactory $services
     * @return RAWBot
     * @throws Exception
     */
    public function create(ServicesFactory $services): RAWBot
    {
        return new RAWBot($this->configData, $services);
    }
}