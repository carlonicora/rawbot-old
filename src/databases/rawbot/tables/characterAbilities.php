<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class characterAbilities extends abstractDatabaseManager {
    protected $fields = [
        'characterId'=>self::INTEGER+self::PRIMARY_KEY,
        'abilityId'=>self::INTEGER+self::PRIMARY_KEY,
        'specialisation'=>self::STRING+self::PRIMARY_KEY,
        'value'=>self::INTEGER,
        'used'=>self::INTEGER,
        'wasUpdated'=>self::INTEGER
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
            'abilities.trait, ' .
            'abilities.name ' .
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

    /**
     * @param string $discordServerId
     * @return bool
     */
    public function startSession(string $discordServerId): bool {
        $sql = 'UPDATE characterAbilities ' .
            'JOIN characters on characterAbilities.characterId=characters.characterId ' .
            'JOIN servers on characters.serverId=servers.serverId ' .
            'SET characterAbilities.used=0, characterAbilities.wasUpdated=0, servers.inSession=1 ' .
            'WHERE servers.discordServerId=?;';
        $parameters = ['s', $discordServerId];

        return $this->runSql($sql, $parameters);
    }
}