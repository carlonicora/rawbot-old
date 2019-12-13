<?php
namespace carlonicora\rawbot\helpers;

use carlonicora\minimalism\database\databaseFactory;
use carlonicora\rawbot\databases\rawbot\tables\abilities;
use carlonicora\rawbot\databases\rawbot\tables\characterAbilities;
use carlonicora\rawbot\databases\rawbot\tables\characters;
use carlonicora\rawbot\databases\rawbot\tables\servers;

class tables {
    /** @var abilities */
    static private $abilities;

    /** @var characterAbilities */
    static private $characterAbilities;

    /** @var characters */
    static private $characters;

    /** @var servers */
    static private $servers;

    /**
     * @return abilities
     */
    public static function getAbilities(): abilities {
        if (!isset(self::$abilities)){
            self::$abilities = databaseFactory::create(abilities::class);
        }
        return self::$abilities;
    }

    /**
     * @return characterAbilities
     */
    public static function getCharacterAbilities(): characterAbilities {
        if (!isset(self::$characterAbilities)){
            self::$characterAbilities = databaseFactory::create(characterAbilities::class);
        }
        return self::$characterAbilities;
    }

    /**
     * @return characters
     */
    public static function getCharacters(): characters {
        if (!isset(self::$characters)){
            self::$characters = databaseFactory::create(characters::class);
        }
        return self::$characters;
    }

    /**
     * @return servers
     */
    public static function getServers(): servers {
        if (!isset(self::$servers)){
            self::$servers = databaseFactory::create(servers::class);
        }
        return self::$servers;
    }
}