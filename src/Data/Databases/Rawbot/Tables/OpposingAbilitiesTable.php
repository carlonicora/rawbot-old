<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldInterface;

class OpposingAbilitiesTable extends AbstractTable
{
    /** @var string */
    protected string $tableName = 'opposingAbilities';

    /** @var array */
    protected array $fields = [
        'weaponId'      => FieldInterface::INTEGER
                        + FieldInterface::PRIMARY_KEY
                        + FieldInterface::AUTO_INCREMENT,
        'name'          => FieldInterface::STRING,
        'damage'        => FieldInterface::INTEGER,
        'description'   => FieldInterface::STRING
    ];

    /**
     * @param int $abilityId
     * @return array
     * @throws DbSqlException
     */
    public function loadByAbilityId(int $abilityId): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE abilityId=?;';
        $this->parameters = ['i', $abilityId];

        return $this->functions->runRead();
    }
}