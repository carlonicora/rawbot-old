<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldInterface;

class AbilitiesTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'abilities';

    /** @var array  */
    protected array $fields = [
        'abilityId'         => FieldInterface::INTEGER
                            +  FieldInterface::PRIMARY_KEY
                            +  FieldInterface::AUTO_INCREMENT,
        'trait'             => FieldInterface::STRING,
        'name'              => FieldInterface::STRING,
        'canChallenge'      => FieldInterface::INTEGER,
        'canBeOpposed'      => FieldInterface::INTEGER,
        'definesInitiative' => FieldInterface::INTEGER
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