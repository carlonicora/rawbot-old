<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class servers extends abstractDatabaseManager {
    protected $dbToUse = 'rawbot';

    protected $fields = [
        'serverId'=>self::PARAM_TYPE_INTEGER,
        'discordServerId'=>self::PARAM_TYPE_STRING,
        'discordUserId'=>self::PARAM_TYPE_STRING
    ];

    protected $primaryKey = [
        'serverId'=>self::PARAM_TYPE_INTEGER
    ];

    protected $autoIncrementField = 'serverId';

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