<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class HitLocationTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'hitLocations';

    /** @var array  */
    protected array $fields = [
        'hitLocationId'     => TableInterface::INTEGER
                            +  TableInterface::PRIMARY_KEY
                            +  TableInterface::AUTO_INCREMENT,
        'name'              => TableInterface::STRING,
        'damageMultiplier'    => TableInterface::DOUBLE,
        'minRange'          => TableInterface::INTEGER,
        'maxRange'          => TableInterface::INTEGER,
        'difficultyIncrese' => TableInterface::INTEGER
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