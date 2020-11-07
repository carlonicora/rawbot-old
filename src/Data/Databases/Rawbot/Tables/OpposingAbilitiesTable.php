<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class OpposingAbilitiesTable extends AbstractTable
{
    /** @var string */
    protected string $tableName = 'opposingAbilities';

    /** @var array */
    protected array $fields = [
        'weaponId' => TableInterface::INTEGER
            + TableInterface::PRIMARY_KEY
            + TableInterface::AUTO_INCREMENT,
        'name' => TableInterface::STRING,
        'damage' => TableInterface::INTEGER,
        'description' => TableInterface::STRING
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