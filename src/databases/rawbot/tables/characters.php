<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class characters extends abstractDatabaseManager {
    protected $dbToUse = 'rawbot';

    protected $fields = [
        'characterId'=>self::PARAM_TYPE_INTEGER,
        'serverId'=>self::PARAM_TYPE_INTEGER,
        'discordUserId'=>self::PARAM_TYPE_STRING,
        'discordUserName'=>self::PARAM_TYPE_STRING,
        'name'=>self::PARAM_TYPE_STRING,
        'body'=>self::PARAM_TYPE_INTEGER,
        'mind'=>self::PARAM_TYPE_INTEGER,
        'spirit'=>self::PARAM_TYPE_INTEGER
    ];

    protected $primaryKey = [
        'characterId'=>self::PARAM_TYPE_INTEGER
    ];

    protected $autoIncrementField = 'characterId';

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

        return $this->runReadSingle($sql,$parameters);
    }
}