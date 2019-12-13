<?php
namespace carlonicora\rawbot;

use carlonicora\minimalism\abstracts\abstractConfigurations;

class configurations extends abstractConfigurations {
    /** @var string */
    private $discordToken;

    /**
     *
     */
    public function loadConfigurations(): void {
        parent::loadConfigurations();

        $this->discordToken = getenv('TOKEN');
    }

    /**
     * Being a CLI application
     * @param string $cookies
     */
    public function unserialiseCookies(string $cookies): void{}

    /**
     * @return string
     */
    public function serialiseCookies(): string{
        return '';
    }

    /**
     * @return string
     */
    public function getDiscordToken(): string {
        return $this->discordToken;
    }
}