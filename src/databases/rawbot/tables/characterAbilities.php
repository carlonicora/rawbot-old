<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class characterAbilities extends abstractDatabaseManager {
    protected $dbToUse = 'rawbot';

    protected $fields = [
        'characterId'=>self::PARAM_TYPE_INTEGER,
        'abilityId'=>self::PARAM_TYPE_INTEGER,
        'specialisation'=>self::PARAM_TYPE_STRING,
        'value'=>self::PARAM_TYPE_INTEGER,
        'used'=>self::PARAM_TYPE_INTEGER
    ];

    protected $primaryKey = [
        'characterId'=>self::PARAM_TYPE_INTEGER,
        'abilityId'=>self::PARAM_TYPE_INTEGER,
        'specialisation'=>self::PARAM_TYPE_STRING
    ];

    /**
     * Loads a single character matching the identifier of the Discord User
     *
     * @param int $characterId
     * @param string $ability
     * @param string $specialisation
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function loadFromCharacterIdAbilitySpecialisation(int $characterId, string $ability, string $specialisation): array {
        $sql = 'SELECT characterAbilities.*, ' .
            'abilities.trait ' .
            'FROM characterAbilities ' .
            'JOIN abilities ON characterAbilities.abilityId=abilities.abilityId ' .
            'WHERE characterAbilities.characterId=? ' .
            'AND abilities.name=? ';
        $parameters = ['is',$characterId,$ability];

        if ($specialisation === NULL){
            $sql .= 'AND characterAbilities.specialisation IS NULL;';
        } else {
            $sql .= 'AND characterAbilities.specialisation=?;';
            $parameters[0] .= 's';
            $parameters[] = $specialisation;
        }

        return $this->runReadSingle($sql,$parameters);
    }

    /**
     * @param int $characterId
     * @param string $trait
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function loadFromCharacterIdTrait(int $characterId, string $trait): array {
        $sql = 'SELECT characterAbilities.*, abilities.name ' .
            'FROM characterAbilities ' .
            'JOIN abilities ON characterAbilities.abilityId=abilities.abilityId ' .
            'WHERE characterId=? AND trait=? ' .
            'ORDER BY abilities.name;';
        $parameters = ['is', $characterId, $trait];

        return $this->runRead($sql, $parameters);
    }

    /**
     * @param int $characterId
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function loadUsedFromCharacterId(int $characterId): array {
        $sql = 'SELECT characterAbilities.*, abilities.name, abilities.trait ' .
            'FROM characterAbilities ' .
            'JOIN abilities ON characterAbilities.abilityId=abilities.abilityId ' .
            'WHERE characterId=? AND used=? ' .
            'ORDER BY abilities.name;';
        $parameters = ['ii', $characterId, true];

        return $this->runRead($sql, $parameters);
    }
}