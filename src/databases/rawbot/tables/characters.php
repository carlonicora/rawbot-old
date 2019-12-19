<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class characters extends abstractDatabaseManager {
    protected $fields = [
        'characterId'=>self::INTEGER+self::PRIMARY_KEY+self::AUTO_INCREMENT,
        'serverId'=>self::INTEGER,
        'discordUserId'=>self::STRING,
        'discordUserName'=>self::STRING,
        'name'=>self::STRING,
        'body'=>self::INTEGER,
        'mind'=>self::INTEGER,
        'spirit'=>self::INTEGER,
        'bonusPoints'=>self::INTEGER
    ];

    /**
     * Loads a single character matching the identifier of the Discord User
     *
     * @param int $serverId
     * @param string $discordUserId
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function loadFromDiscordUserId(int $serverId, string $discordUserId): array {
        $sql = 'SELECT * FROM characters WHERE serverId=? AND discordUserId=?;';
        $parameters = ['is',$serverId,$discordUserId];

        return $this->runReadSingle($sql,$parameters);
    }

    /**
     * @param int $serverId
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function loadFromServerId(int $serverId): array {
        $sql = 'SELECT * FROM characters WHERE serverId=?;';
        $parameters = ['i',$serverId];

        return $this->runRead($sql,$parameters);
    }
}