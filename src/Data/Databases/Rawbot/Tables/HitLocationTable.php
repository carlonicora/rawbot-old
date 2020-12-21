<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldInterface;

class HitLocationTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'hitLocations';

    /** @var array  */
    protected array $fields = [
        'hitLocationId'     => FieldInterface::INTEGER
                            +  FieldInterface::PRIMARY_KEY
                            +  FieldInterface::AUTO_INCREMENT,
        'name'              => FieldInterface::STRING,
        'damageMultiplier'  => FieldInterface::DOUBLE,
        'minRange'          => FieldInterface::INTEGER,
        'maxRange'          => FieldInterface::INTEGER,
        'difficultyIncrese' => FieldInterface::INTEGER
    ];

    /**
     * @param int $hitLocation
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadByHitLocationRange(int $hitLocation): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE ? BETWEEN minRange AND maxRange;';
        $this->parameters = ['i', $hitLocation];

        return $this->functions->runReadSingle();
    }

    /**
     * @param string $name
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadHitLocationByName(string $name): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE name=?;';
        $this->parameters = ['s', $name];

        return $this->functions->runReadSingle();
    }
}