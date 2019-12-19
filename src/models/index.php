<?php
namespace carlonicora\rawbot\models;

use carlonicora\minimalism\abstracts\abstractCliModel;
use carlonicora\rawbot\configurations;
use carlonicora\rawbot\managers\abilities;
use carlonicora\rawbot\managers\bonuses;
use carlonicora\rawbot\managers\characters;
use carlonicora\rawbot\managers\servers;
use carlonicora\rawbot\managers\masters;
use Discord\DiscordCommandClient;

class index extends abstractCliModel {
    /** @var configurations */
    protected $configurations;

    /** @var DiscordCommandClient */
    private $discord;

    /**
     * @return bool
     */
    public function run(): bool {
        $this->discord = new DiscordCommandClient([
            'token'=>$this->configurations->getDiscordToken(),
            'prefix'=>'/',
            'name'=>'raw',
            'description'=>'Discord bot for RAW Role Playing Game',
			'discordOptions' => [
                'loadAllMembers' => true
            ]
        ]);

        /** @noinspection PhpUnusedLocalVariableInspection */
        $servers = new servers($this->configurations, $this->discord);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $characters = new characters($this->configurations, $this->discord);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $abilities = new abilities($this->configurations, $this->discord);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $masters = new masters($this->configurations, $this->discord);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $bonus = new bonuses($this->configurations, $this->discord);

        $this->discord->run();

        return true;
    }
}