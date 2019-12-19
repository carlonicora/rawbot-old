<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class servers extends abstractDatabaseManager {
    protected $fields = [
        'serverId'=>self::INTEGER+self::PRIMARY_KEY+self::AUTO_INCREMENT,
        'discordServerId'=>self::STRING,
        'discordUserId'=>self::STRING,
        'inSession'=>self::INTEGER
    ];

    /**
     * Loads a single server matching the identifier of the Discord Guild
     *
     * @param string $discordServerId
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function loadFromDiscordServerId(string $discordServerId): array {
        $sql = 'SELECT * FROM servers WHERE discordServerId=?;';
        $parameters = ['s',$discordServerId];

        return $this->runReadSingle($sql,$parameters);
    }
}