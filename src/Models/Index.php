<?php
namespace CarloNicora\RAWBot\Models;

use CarloNicora\Minimalism\Core\Modules\Interfaces\ResponseInterface;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Modules\Cli\CliModel;
use CarloNicora\RAWBot\RAWBot;
use Exception;

class Index extends CliModel
{
    /** @var RAWBot  */
    private RAWBot $RAWBot;

    /**
     * Index constructor.
     * @param ServicesFactory $services
     * @throws Exception
     */
    public function __construct(ServicesFactory $services)
    {
        parent::__construct($services);

        $this->RAWBot = $this->services->service(RAWBot::class);
    }

    /**
     * @return ResponseInterface
     */
    public function run(): ResponseInterface
    {
        try {
            $this->RAWBot->start();
        } catch (Exception $e) {
        }

        return $this->generateResponse($this->document, ResponseInterface::HTTP_STATUS_200);
    }
}