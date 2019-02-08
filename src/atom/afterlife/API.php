<?php

/**
 *     _       __   _                   _   _    __               _      ____    ___ 
 *    / \     / _| | |_    ___   _ __  | | (_)  / _|   ___       / \    |  _ \  |_ _|
 *   / _ \   | |_  | __|  / _ \ | '__| | | | | | |_   / _ \     / _ \   | |_) |  | | 
 *  / ___ \  |  _| | |_  |  __/ | |    | | | | |  _| |  __/    / ___ \  |  __/   | | 
 * /_/   \_\ |_|    \__|  \___| |_|    |_| |_| |_|    \___|   /_/   \_\ |_|     |___|                                                                                                                                                            
 *                                                                                    
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza
 */

namespace atom\afterlife;

# player instance
use pocketmine\Player;

# utils
use pocketmine\utils\TextFormat as color;

# plugin instance - Main::getInstance()
use atom\afterlife\Main;
use atom\afterlife\handler\FormHandler as Form;

# api
use atom\afterlife\modules\GetData;
use atom\afterlife\modules\GetKills;
use atom\afterlife\modules\GetStreak;
use atom\afterlife\modules\GetXp;
use atom\afterlife\modules\GetLevel;
use atom\afterlife\modules\GetDeaths;
use atom\afterlife\modules\GetRatio;
use atom\afterlife\modules\KillCounter;
use atom\afterlife\modules\xpCalculator;
use atom\afterlife\modules\LevelCounter;
use atom\afterlife\modules\DeathCounter;



class API {

	public static $instance;

	public static function getInstance(): API{
		return self::$instance;
	}

	public function __construct() {
		self::$instance = $this;
	}

    public function getStats (Player $player):void {
		switch (Main::getInstance()->config->get("profile-method")) {
			case "form":
				Form::statsUi($player);
				break;

			case "standard":
				$player->sendMessage(color::LIGHT_PURPLE."--------------------");
				$player->sendMessage($player->getName()." stats\n\n");
				$player->sendMessage(color::YELLOW."Current Win Streak ".color::GREEN.$this->getStreak($player->getName())."\n\n");
				$player->sendMessage(color::RED."Kils: ".color::GREEN.$this->getKills($player->getName()));
				$player->sendMessage(color::RED."Deaths: ".color::GREEN.$this->getDeaths($player->getName()));
				$player->sendMessage(color::RED."K/D Ratio: ".color::GREEN.$this->getKdr($player->getName()));
				$player->sendMessage(color::RED."Level: ".color::GREEN.$this->getLevel($player->getName()));
				$player->sendMessage(color::RED."Total XP: ".color::GREEN.$this->getTotalXp($player->getName()));
				$player->sendMessage(color::RED."Xp needed to level up: ".color::GREEN.$this->getXp($player->getName()));
				$player->sendMessage(color::LIGHT_PURPLE."--------------------");
				break;
		}
	}

    /**
	 * Returns Player Data for leaderboards
     * @api
	 * @param type $type
	 * @return GetData
	 */
	public function getData ($type):string {
		$data = new GetData(Main::getInstance());
		return $data->getData($type);
    }
    
    /**
     * Returns Players kills
     * @api
     * @param type $player
     * @return GetKills
     */
	public function getKills (string $player): ?int {
		$data = new GetKills(Main::getInstance(), $player);
		return $data->getKills();
    }
    
    /**
	 * Returns Players Win Streek
     * @api
	 * @param type $player
	 * @return GetStreak
	 */
	public function getStreak(string $player): ?int {
		$data = new GetStreak(Main::getInstance(), $player);
		return $data->getStreak();
    }
    
    /**
	 * Returns Player Xp till level up
     * @api
	 * @param type $player
	 * @return GetXp
	 */
	public function getXp(string $player): ?int {
		$data = new GetXp(Main::getInstance(), $player);
		return $data->getXp($player);
	}

	/**
	 * Returns Player total xp
     * @api
	 * @param type $player
	 * @return GetXp
	 */
	public function getTotalXp(string $player): ?int {
		$data = new GetXp(Main::getInstance(), $player);
		return $data->getTotalXp($player);
    }
    
    /**
     * Returns player level
     * @api
     * @param type $player
     * @return GetLevel
     */
    public function getLevel(string $player): ?int {
		$data = new GetLevel(Main::getInstance(), $player);
		return $data->getLevel();
    }
    
    /**
     * Returns Player death count
     * @api
     * @param type $player
     * @return GetDeaths
     */
	public function getDeaths(string $player): ?int {
		$data = new GetDeaths(Main::getInstance(), $player);
		return $data->getDeaths();
    }
    
    /**
     * Returns kills/death ratio
     * @api
     * @param type $player
     * @return GetRatio
     */
    public function getKdr(string $player): ?int {
		$data = new GetRatio(Main::getInstance(), $player);
		return $data->getRatio();
    }
    
    /**
     * Adds 1 to the number of kills
     * @api
     * @param type $player
     */
	public function addKill(string $player):void {
		$data = new KillCounter(Main::getInstance(), $player);
		$data->addKill();
    }
    
    /**
	 * Adds xp to player
     * @api
	 * @param type $amount
	 * @param type $player
	 */
	public function addXp (string $player, ?int $amount):void {
		$data = new xpCalculator(Main::getInstance(), $player);
		$data->addXp($amount);
	}

	/**
	 * removes xp to player
     * @api
	 * @param type $player
	 * @param type $amount
	 */
	public function removeXp (string $player, ?int $amount):void {
		$data = new xpCalculator(Main::getInstance(), $player);
		$data->removeXp($amount);
    }
    
    /**
     * adds amount of levels to player
     * @api
     * @param type $player
     * @param type $amount
     */
    public function addLevel (string $player, ?int $amount):void {
		$data = new LevelCounter(Main::getInstance(), $player);
		$data->addLevel($amount);
	}

    /**
     * removes amount of levels to player
     * @api
     * @param type $player
     * @param type $amount
     */
	public function removeLevel (string $player, ?int $amount):void {
		$data = new LevelCounter(Main::getInstance(), $player);
		$data->removeLevel($amount);
    }
    
    /**
     * Adds to the number of deaths
     * @param type $player
     * @return DeathCounter
     */
	public function addDeath (string $player):void {
        $data = new DeathCounter(Main::getInstance(), $player);
        $data->addDeath();
	}
}
