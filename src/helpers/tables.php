<?php
namespace carlonicora\rawbot\helpers;

use carlonicora\minimalism\database\databaseFactory;
use carlonicora\minimalism\exceptions\dbConnectionException;
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
            try {
                self::$abilities = databaseFactory::create(abilities::class);
            } catch (dbConnectionException $e) {
                self::$abilities = null;
            }
        }
        return self::$abilities;
    }

    /**
     * @return characterAbilities
     */
    public static function getCharacterAbilities(): characterAbilities {
        if (!isset(self::$characterAbilities)){
            try {
                self::$characterAbilities = databaseFactory::create(characterAbilities::class);
            } catch (dbConnectionException $e) {
                self::$characterAbilities = null;
            }
        }
        return self::$characterAbilities;
    }

    /**
     * @return characters
     */
    public static function getCharacters(): characters {
        if (!isset(self::$characters)){
            try {
                self::$characters = databaseFactory::create(characters::class);
            } catch (dbConnectionException $e) {
                self::$characters = null;
            }
        }
        return self::$characters;
    }

    /**
     * @return servers
     */
    public static function getServers(): servers {
        if (!isset(self::$servers)){
            try {
                self::$servers = databaseFactory::create(servers::class);
            } catch (dbConnectionException $e) {
                self::$servers = null;
            }
        }
        return self::$servers;
    }
}