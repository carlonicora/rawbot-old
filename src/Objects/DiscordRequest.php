<?php
namespace CarloNicora\RAWBot\Objects;

class DiscordRequest
{
    /** @var string */
    private string $discordServerId;

    /** @var string */
    private string $discordUserId;

    /** @var int */
    private int $serverId;

    /** @var int */
    private int $characterId;

    /**
     * @return int
     */
    public function getCharacterId(): int
    {
        return $this->characterId;
    }

    /**
     * @return string
     */
    public function getDiscordServerId(): string
    {
        return $this->discordServerId;
    }

    /**
     * @return string
     */
    public function getDiscordUserId(): string
    {
        return $this->discordUserId;
    }

    /**
     * @return int
     */
    public function getServerId(): int
    {
        return $this->serverId;
    }

    /**
     * @param int $characterId
     */
    public function setCharacterId(int $characterId): void
    {
        $this->characterId = $characterId;
    }

    /**
     * @param string $discordServerId
     */
    public function setDiscordServerId(string $discordServerId): void
    {
        $this->discordServerId = $discordServerId;
    }

    /**
     * @param string $discordUserId
     */
    public function setDiscordUserId(string $discordUserId): void
    {
        $this->discordUserId = $discordUserId;
    }

    /**
     * @param int $serverId
     */
    public function setServerId(int $serverId): void
    {
        $this->serverId = $serverId;
    }
}