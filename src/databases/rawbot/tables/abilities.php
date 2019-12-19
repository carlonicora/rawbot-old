<?php
namespace carlonicora\rawbot\databases\rawbot\tables;

use carlonicora\minimalism\database\abstractDatabaseManager;
use carlonicora\minimalism\exceptions\dbRecordNotFoundException;

class abilities extends abstractDatabaseManager {
    protected $fields = [
        'abilityId'=>self::INTEGER+self::PRIMARY_KEY+self::AUTO_INCREMENT,
        'trait'=>self::STRING,
        'name'=>self::STRING
    ];

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