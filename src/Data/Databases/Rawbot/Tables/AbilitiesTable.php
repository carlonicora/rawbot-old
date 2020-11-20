<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class AbilitiesTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'abilities';

    /** @var array  */
    protected array $fields = [
        'abilityId'         => TableInterface::INTEGER
                            +  TableInterface::PRIMARY_KEY
                            +  TableInterface::AUTO_INCREMENT,
        'trait'             => TableInterface::STRING,
        'name'              => TableInterface::STRING,
        'canChallenge'      => TableInterface::INTEGER,
        'canBeOpposed'      => TableInterface::INTEGER,
        'definesInitiative' => TableInterface::INTEGER
    ];

    /**
     * @param string $abilityName
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadByName(string $abilityName): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE name=?;';
        $this->parameters = ['s',$abilityName];

        return $this->functions->runReadSingle();
    }

    /**
     * @return array
     * @throws DbSqlException
     */
    public function loadChallengers(): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE canChallenge=?;';
        $this->parameters = ['i',1];

        return $this->functions->runRead();
    }

    /**
     * @return array
     * @throws DbSqlException
     */
    public function loadInitiativeDefiners(): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE definesInitiative=?;';
        $this->parameters = ['i',1];

        return $this->functions->runRead();
    }
}