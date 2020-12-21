<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldInterface;

class WeaponsTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'weapons';

    /** @var array  */
    protected array $fields = [
        'weaponId'      => FieldInterface::INTEGER
                        +  FieldInterface::PRIMARY_KEY
                        +  FieldInterface::AUTO_INCREMENT,
        'name'          => FieldInterface::STRING,
        'damage'        => FieldInterface::INTEGER,
        'description'   => FieldInterface::STRING
    ];

    /**
     * @param string $name
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadByName(string $name): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE name=?;';
        $this->parameters = ['s', $name];

        return $this->functions->runReadSingle();
    }
}