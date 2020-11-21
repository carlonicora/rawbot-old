<?php
namespace CarloNicora\RAWBot\Data\Databases\Rawbot;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\MySQL;
use CarloNicora\RAWBot\Data\Databases\Rawbot\Tables\AbilitiesTable;
use CarloNicora\RAWBot\Data\Databases\Rawbot\Tables\CharacterAbilitiesTable;
use CarloNicora\RAWBot\Data\Databases\Rawbot\Tables\CharactersTable;
use CarloNicora\RAWBot\Data\Databases\Rawbot\Tables\HitLocationTable;
use CarloNicora\RAWBot\Data\Databases\Rawbot\Tables\OpposingAbilitiesTable;
use CarloNicora\RAWBot\Data\Databases\Rawbot\Tables\ServersTable;
use CarloNicora\RAWBot\Data\Databases\Rawbot\Tables\WeaponsTable;
use Exception;

class RAWBotTables
{
    /** @var MySQL  */
    protected MySQL $mysql;

    /** @var AbilitiesTable|null  */
    private ?AbilitiesTable $abilities=null;

    /** @var CharacterAbilitiesTable|null  */
    private ?CharacterAbilitiesTable $characterAbilities=null;

    /** @var CharactersTable|null  */
    private ?CharactersTable $characters=null;

    /** @var HitLocationTable|null  */
    private ?HitLocationTable $hitLocations=null;

    /** @var OpposingAbilitiesTable|null  */
    private ?OpposingAbilitiesTable $opposingAbilities=null;

    /** @var ServersTable|null  */
    private ?ServersTable $servers=null;

    /** @var WeaponsTable|null  */
    private ?WeaponsTable $weapons=null;

    /**
     * @param ServicesFactory $services
     * @throws Exception
     */
    public function __construct(ServicesFactory $services)
    {
        $this->mysql = $services->service(MySQL::class);
    }

    /**
     * @return AbilitiesTable
     * @throws Exception
     */
    public function getAbilities(): AbilitiesTable
    {
        if ($this->abilities === null){
            $this->abilities = $this->mysql->create(AbilitiesTable::class);
        }

        return $this->abilities;
    }

    /**
     * @return CharactersTable
     * @throws Exception
     */
    public function getCharacters(): CharactersTable
    {
        if ($this->characters === null){
            $this->characters = $this->mysql->create(CharactersTable::class);
        }
        return $this->characters;
    }

    /**
     * @return CharacterAbilitiesTable
     * @throws Exception
     */
    public function getCharacterAbilities(): CharacterAbilitiesTable
    {
        if ($this->characterAbilities === null){
            $this->characterAbilities = $this->mysql->create(CharacterAbilitiesTable::class);
        }
        return $this->characterAbilities;
    }

    /**
     * @return HitLocationTable
     * @throws Exception
     */
    public function getHitLocations(): HitLocationTable
    {
        if ($this->hitLocations === null){
            $this->hitLocations = $this->mysql->create(HitLocationTable::class);
        }
        return $this->hitLocations;
    }

    /**
     * @return OpposingAbilitiesTable
     * @throws Exception
     */
    public function getOpposingAbilities(): OpposingAbilitiesTable
    {
        if ($this->opposingAbilities === null){
            $this->opposingAbilities = $this->mysql->create(OpposingAbilitiesTable::class);
        }
        return $this->opposingAbilities;
    }

    /**
     * @return ServersTable
     * @throws Exception
     */
    public function getServers(): ServersTable
    {
        if ($this->servers === null){
            $this->servers = $this->mysql->create(ServersTable::class);
        }

        return $this->servers;
    }

    /**
     * @return WeaponsTable
     * @throws Exception
     */
    public function getWeapons(): WeaponsTable
    {
        if ($this->weapons === null){
            $this->weapons = $this->mysql->create(WeaponsTable::class);
        }

        return $this->weapons;
    }
}