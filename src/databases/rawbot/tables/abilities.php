<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class abilities extends abstractDatabaseManager {
    protected $dbToUse = 'rawbot';

    protected $fields = [
        'abilityId'=>self::PARAM_TYPE_INTEGER,
        'trait'=>self::PARAM_TYPE_STRING,
        'name'=>self::PARAM_TYPE_STRING
    ];

    protected $primaryKey = [
        'abilityId'=>self::PARAM_TYPE_INTEGER
    ];

    protected $autoIncrementField = 'abilityId';

    /**
     * @param string $name
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function loadFromName(string $name): array {
        $sql = 'SELECT * FROM abilities WHERE name=?;';
        $parameters = ['s',$name];

        return $this->runReadSingle($sql,$parameters);
    }

    /**
     * @return array|null
     * @throws dbRecordNotFoundException
     */
    public function loadAll(): ?array {
        $sql = 'SELECT * FROM abilities ORDER BY trait,name;';

        return $this->runRead($sql);
    }
}