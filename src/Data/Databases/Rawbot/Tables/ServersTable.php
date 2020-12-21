<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldInterface;

class ServersTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'servers';

    /** @var array  */
    protected array $fields = [
        'serverId'          => FieldInterface::INTEGER
                            +  FieldInterface::PRIMARY_KEY
                            +  FieldInterface::AUTO_INCREMENT,
        'discordServerId'   => FieldInterface::STRING,
        'discordUserId'     => FieldInterface::STRING,
        'campaignName'      => FieldInterface::STRING,
        'inSession'         => FieldInterface::INTEGER
    ];

    /**
     * @param string $discordServerId
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadByDiscordServerId(string $discordServerId): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE discordServerId=?;';
        $this->parameters = ['s',$discordServerId];

        return $this->functions->runReadSingle();
    }
}