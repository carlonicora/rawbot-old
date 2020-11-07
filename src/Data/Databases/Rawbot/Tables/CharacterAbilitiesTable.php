<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;

class CharacterAbilitiesTable extends AbstractTable
{
    /** @var string  */
    protected string $tableName = 'characterAbilities';

    /** @var array  */
    protected array $fields = [
        'characterId'       => TableInterface::INTEGER
                            +  TableInterface::PRIMARY_KEY,
        'abilityId'         => TableInterface::INTEGER
                            +  TableInterface::PRIMARY_KEY,
        'specialisation'    => TableInterface::STRING
                            +  TableInterface::PRIMARY_KEY,
        'value'             => TableInterface::INTEGER,
        'used'              => TableInterface::INTEGER,
        'wasUpdated'        => TableInterface::INTEGER
    ];

    /**
     * @param int $characterId
     * @param int $abilityId
     * @param string $specialisation
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function loadByCharacterIdAbilityIdSpecialisation(int $characterId, int $abilityId, string $specialisation): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE characterId=? AND abilityId=? and specialisation=?;';
        $this->parameters = ['iis',$characterId, $abilityId, $specialisation];

        return $this->functions->runReadSingle();
    }

    /**
     * @param int $characterId
     * @return array
     * @throws DbSqlException
     */
    public function loadUsed(int $characterId): array
    {
        $this->sql = 'SELECT abilities.abilityId, abilities.name, abilities.trait, characterAbilities.value, characterAbilities.specialisation'
            . ' FROM characterAbilities'
            . ' JOIN abilities ON characterAbilities.abilityId=abilities.abilityId'
            . ' WHERE characterId=? AND used=?;';
        $this->parameters = ['ii',$characterId, 1];

        return $this->functions->runRead();
    }

    /**
     * @param int $characterId
     * @return array
     * @throws DbSqlException
     */
    public function loadUpdated(int $characterId): array
    {
        $this->sql = 'SELECT abilities.abilityId, abilities.name, abilities.trait, characterAbilities.value, characterAbilities.specialisation'
            . ' FROM characterAbilities'
            . ' JOIN abilities ON characterAbilities.abilityId=abilities.abilityId'
            . ' WHERE characterId=? AND wasUpdated=?;';
        $this->parameters = ['ii',$characterId, 1];

        return $this->functions->runRead();
    }

    /**
     * @param int $characterId
     * @param int $abilityId
     * @param string $specialisation
     * @param int $addedValue
     * @throws DbSqlException
     */
    public function updateCharacterAbility(int $characterId, int $abilityId, string $specialisation, int $addedValue): void
    {
        $this->sql = $this->query->UPDATE()
            . ' value=value+?'
            . ' WHERE characterId=? AND abilityId=? and specialisation=?;';
        $this->parameters = ['iiis', $addedValue,$characterId, $abilityId, $specialisation];

        $this->functions->runSql();
    }

    /**
     * @param int $characterId
     * @param int $abilityId
     * @param string $specialisation
     * @throws DbSqlException
     */
    public function setAbilityUpdated(int $characterId, int $abilityId, string $specialisation): void
    {
        $this->sql = $this->query->UPDATE()
            . ' wasUpdated=?'
            . ' WHERE characterId=? AND abilityId=? and specialisation=?;';
        $this->parameters = ['iiis', 1,$characterId, $abilityId, $specialisation];

        $this->functions->runSql();
    }

    /**
     * @param int $characterId
     * @return array
     * @throws DbSqlException
     */
    public function loadByCharacterId(int $characterId): array
    {
        $this->sql = 'SELECT characterAbilities.*, abilities.name, abilities.trait'
            . ' FROM characterAbilities'
            . ' JOIN abilities ON characterAbilities.abilityId=abilities.abilityId'
            . ' WHERE characterId=?;';
        $this->parameters = ['i',$characterId];

        return $this->functions->runRead();
    }

    /**
     * @param int $serverId
     * @throws DbSqlException
     */
    public function resetAbilityUsage(int $serverId): void
    {
        $this->sql = 'UPDATE characterAbilities'
	        . ' JOIN characters ON characters.characterId=characterAbilities.characterId'
            . ' SET	wasUpdated=?, used=?'
            . ' WHERE characters.serverId=?;';
        $this->parameters = ['iii', 0, 0, $serverId];

        $this->functions->runSql();
    }

    /**
     * @param int $characterId
     * @return array
     * @throws DbSqlException
     */
    public function loadUsedByCharacterId(int $characterId): array
    {
        $this->sql = $this->query->SELECT()
            . ' WHERE characterId=? AND used=?;';
        $this->parameters = ['ii', $characterId, 1];

        return $this->functions->runRead();
    }
}