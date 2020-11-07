<?php
namespace CarloNicora\RAWBot\Helpers;

use Exception;

class DiceRoller
{
    public const CRITICAL_NONE = 0;
    public const CRITICAL_FAILURE = 1;
    public const CRITICAL_SUCCESS = 2;

    /**
     * @param int $diceSides
     * @param int $criticalRoll
     * @return int
     */
    public static function roll(int $diceSides, int &$criticalRoll): int
    {
        try {
            $result = random_int(1, $diceSides);
        } catch (Exception $e) {
            $result = $diceSides / 10 * 4;
        }

        switch ($result) {
            case 1:
                $criticalRoll = self::CRITICAL_FAILURE;
                break;
            case $diceSides:
                $criticalRoll = self::CRITICAL_SUCCESS;
                break;
            default:
                $criticalRoll = self::CRITICAL_NONE;
                break;
        }

        return $result;
    }

    /**
     * @param int $ability
     * @param int $trait
     * @param int $roll
     * @param int $delta
     * @return int
     */
    public static function calculateBonus(int $ability, int $trait, int $roll, int &$delta): int
    {
        $delta = $roll-$ability-$trait;

        $response = 0;

        if ($delta >= 0){
            $response = (int)($delta/20)+1;
        }

        if ($roll === 100){
            $response *= 2;
        }

        return $response;
    }
}