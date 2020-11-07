<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class CharactersTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'characters';

    /** @var array  */
    protected array $fields = [
        'characterId'                   => TableInterface::INTEGER
                                        +  TableInterface::PRIMARY_KEY
                                        +  TableInterface::AUTO_INCREMENT,
        'serverId'                      => TableInterface::INTEGER,
        'discordUserId'                 => TableInterface::STRING,
        'discordUserName'               => TableInterface::STRING,
        'isNPC'                         => TableInterface::INTEGER,
        'shortName'                     => TableInterface::STRING,
        'name'                          => TableInterface::STRING,
        'body'                          => TableInterface::INTEGER,
        'mind'                          => TableInterface::INTEGER,
        'spirit'                        => TableInterface::INTEGER,
        'bonusPoints'                   => TableInterface::INTEGER,
        'damages'                       => TableInterface::INTEGER,
        'description'                   => TableInterface::STRING,
        'automaticallyAcceptChallenges' => TableInterface::INTEGER,
        'thumbnail'                     => TableInterface::STRING
    ];

    /**
     * @param int $serverId
     * @param string $discordUserId
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadByDiscordUserId(int $serverId, string $discordUserId): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE serverId=? AND discordUserId=?;';
        $this->parameters = ['is',$serverId,$discordUserId];

        return $this->functions->runReadSingle();
    }

    /**
     * @param int $serverId
     * @param string $characterShortName
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadByCharacterShortName(int $serverId, string $characterShortName): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE serverId=? AND shortName=?;';
        $this->parameters = ['is',$serverId,$characterShortName];

        return $this->functions->runReadSingle();
    }

    /**
     * @param int $serverId
     * @return array
     * @throws DbSqlException
     */
    public function loadAllCharactersByServerId(int $serverId): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE serverId=?;';
        $this->parameters = ['i', $serverId];

        return $this->functions->runRead();
    }

    /**
     * @param int $serverId
     * @return array
     * @throws DbSqlException
     */
    public function loadPlayerCharactersByServerId(int $serverId): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE serverId=? and isNPC=?;';
        $this->parameters = ['ii', $serverId, 0];

        return $this->functions->runRead();
    }

    /**
     * @param int $serverId
     * @return array
     * @throws DbSqlException
     */
    public function loadNonPlayerCharactersByServerId(int $serverId): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE serverId=? AND isNPC=?;';
        $this->parameters = ['ii', $serverId, 1];

        return $this->functions->runRead();
    }
}