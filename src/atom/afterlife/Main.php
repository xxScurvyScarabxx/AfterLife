<?php

/**
 *     _       __   _                   _   _    __        
 *    / \     / _| | |_    ___   _ __  | | (_)  / _|   ___ 
 *   / _ \   | |_  | __|  / _ \ | '__| | | | | | |_   / _ \
 *  / ___ \  |  _| | |_  |  __/ | |    | | | | |  _| |  __/
 * /_/   \_\ |_|    \__|  \___| |_|    |_| |_| |_|    \___|
 * 
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza
 * @version 3.2.10
 * @copyright GNU (general public license)
 */

namespace atom\afterlife;

# Main Files
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

# calculating
use pocketmine\level\Position;

#commands
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

# utils
use pocketmine\utils\TextFormat as color;
use pocketmine\level\particle\FloatingTextParticle;

# plugin files
use atom\afterlife\handler\DataHandler as mySQLi;
use atom\afterlife\handler\FormHandler as Form;
use atom\afterlife\handler\FloatingTextHandler as Leaderboard;

use atom\afterlife\events\SetUpEvent;
use atom\afterlife\events\LevelChangeEvent;
use atom\afterlife\events\KillEvent;
use atom\afterlife\events\CustomDeathEvent;
use atom\afterlife\modules\GetStreak;
use atom\afterlife\modules\GetKills;
use atom\afterlife\modules\GetDeaths;
use atom\afterlife\modules\GetRatio;
use atom\afterlife\modules\DeathCounter;
use atom\afterlife\modules\KillCounter;
use atom\afterlife\modules\xpCalculator;
use atom\afterlife\modules\GetXp;
use atom\afterlife\modules\GetLevel;
use atom\afterlife\modules\GetData;
use atom\afterlife\modules\PvpEvent;
use atom\afterlife\modules\LevelCounter;

class Main extends PluginBase {

	/** config data */
	public $config;

	/** @var $this */
	public static $instance;

	/** @var array[FloatingTextParticle] */
	public $ftps = [];
	
	public function onEnable():void {
		# Registers plugin instance
		self::$instance = $this;

		# Registers the plugin events.
		Server::getInstance()->getPluginManager()->registerEvents(new SetUpEvent($this), $this);
		Server::getInstance()->getPluginManager()->registerEvents(new KillEvent($this), $this);
		Server::getInstance()->getPluginManager()->registerEvents(new CustomDeathEvent($this), $this);
		Server::getInstance()->getPluginManager()->registerEvents(new LevelChangeEvent($this), $this);
		Server::getInstance()->getPluginManager()->registerEvents(new PvpEvent($this), $this);

		$this->saveDefaultConfig();
		$this->reloadConfig();

		# Creats config files to store plugin settings.
        @mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . 'players/');
		@mkdir($this->getDataFolder() . 'leaderboards/');
        $this->config = $this->getConfig();
		
		# loads mysqli database
		if ($this->config->get('type') === "online") {
			mySQLi::connect();
			mySQLi::$database->query("CREATE TABLE IF NOT EXISTS `afterlife`(`id` int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` TINYTEXT NOT NULL, `kills` int(5) NOT NULL, `deaths` int(5) NOT NULL, `ratio` FLOAT NOT NULL, `totalXP` int(5) NOT NULL, `xp` int(5) NOT NULL, `level` int(5) NOT NULL, `streak` int(5) NOT NULL)");			
			// $this->mysqlConnect();
			// $this->mysqli->query("CREATE TABLE IF NOT EXISTS `afterlife`(`id` int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` TINYTEXT NOT NULL, `kills` int(5) NOT NULL, `deaths` int(5) NOT NULL, `ratio` FLOAT NOT NULL, `totalXP` int(5) NOT NULL, `xp` int(5) NOT NULL, `level` int(5) NOT NULL, `streak` int(5) NOT NULL)");
		}
	}

	public function onDisable():void {
		# closes mysqli connection if set
		if (isset($this->mysqli)) {
			$this->mysqli->close();
			$this->getLogger()->notice("Connection to database terminated!");
		}
	}

	/**
	 * Retrieves the this plugins instance
	 * @return Plugin
	 */
	public static function getInstance():Plugin {
		return self::$instance;
	}











	public function onCommand (CommandSender $player, Command $cmd, string $label, array $args):bool {
		if ($player instanceof Player) {
			if ($cmd == "stats") {
				if (!isset($args[0])) {
					$this->getStats($player);
				} else {
					$target = Server::getInstance()->getPlayerExact($args[0]);
                   if($target !== null) {
                       $this->getStats($target);
                   } else {
						$player->sendMessage(color::RED . "Player is not online");
					}
				}
			}

			if ($this->config->get("texts-enabled") == true) {
				if ($player->hasPermission('afterlife.admin')) {
					if ($cmd == "setleaderboard") {
						if (isset($args[0])) {
							if (in_array($args[0], ["levels", "kills", "kdr", "streaks"])) {
								if (!isset($this->ftps[$args[0]][$player->getLevel()->getName()])) {
									$level = $player->getLevel()->getName();
									$x = round($player->getX(), 1);
									$y = round($player->getY(), 1) + 1.7;
									$z = round($player->getZ(), 1);
									yaml_emit_file($this->getDataFolder() . "leaderboards/" . $args[0] . "_" . $level . ".yml", ['level'=>$level, 'type'=>$args[0], 'xx'=>$x, 'yy'=>$y, 'zz'=>$z]);
									$possition = new Position($player->getX(), $player->getY() + 1.7, $player->getZ(), $player->getLevel());
									Leaderboard::addText($possition, $player->getLevel()->getName(), $args[0], $player);
									$player->sendMessage(color::RED.$args[0].color::YELLOW." leaderboard created!");
								} else {
									$player->sendMessage('Error:'.' '.$args[0].' '.'Floating text already exists in'.' '.$player->getLevel()->getName());
								}
							} elseif ((in_array($args[0], ["del", "remove", "delete"]))) {
                                // coming soon
							} elseif (in_array($args[0], ["debug"])) {
								//
							}
						} else {
							$player->sendMessage(color::RED . "Please choose \n ---kills, \n ---levels, \n ---kdr, \n ---streaks");
						}
					}
				} else {
					$player->sendMessage(color::RED."You donot have permission to run this command!");
				}
			} else {
				$player->sendMessage(color::RED."leaderboards are not enabled... edit config");
			}
		} else {
			$player->sendMessage("Run commands in-game");
		}

		return true;
	}

/**
 * =========
 * =========
 * ===API===
 * =========
 * =========
 */


	public function getStats (Player $player) {
		switch ($this->config->get("profile-method")) {
			case "form":
				Form::statsUi($player);
				break;

			case "standard":
				$player->sendMessage(color::LIGHT_PURPLE."--------------------");
				$player->sendMessage($player->getName()." stats\n\n");
				$player->sendMessage(color::YELLOW."Current Win Streak ".color::BLUE.$this->getStreak($player->getName())."\n\n");
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
	 * Returns Players Win Streek
	 * @param type $name
	 * @param GetStreak
	 */
	public function getStreak($name) {
		$streak = new GetStreak($this, $name);
		return $streak->getStreak();
	}


	/**
     * Returns Players kills
     * @param type $name
     * @return GetKills
     */
	public function getKills ($name) {
		$kills = new GetKills($this, $name);
		return $kills->getKills();
	}


	/**
     * Adds to the number of kills
     * @param type $name
     * @return KillCounter
     */
	public function addKill ($name) {
		$counter = new KillCounter($this, $name);
		return $counter->addKill();
	}


	/**
     * Returns Players deaths
     * @param type $name
     * @return GetDeaths
     */
	public function getDeaths ($name) {
		$deaths = new GetDeaths($this, $name);
		return $deaths->getDeaths();
	}


	/**
     * Adds to the number of deaths
     * @param type $name
     * @return DeathCounter
     */
	public function addDeath ($name) {
        $counter = new DeathCounter($this, $name);
        return $counter->addDeath();
    }


	/**
     * Returns Players kills to death ratio
     * @param type $name
     * @return GetRatio
     */
	public function getKdr ($name) {
		$ratio = new GetRatio($this, $name);
		return $ratio->getRatio();
	}



	/**
	 * Returns Player Xp till level up
	 * @param type $type
	 * @return GetXp
	 */
	public function getXp ($name) {
		$data = new GetXp($this, $name);
		return $data->getXp($name);
	}

	/**
	 * Returns Player curent xp
	 * @param type $type
	 * @return GetXp
	 */
	public function getTotalXp($name) {
		$data = new GetXp($this, $name);
		return $data->getTotalXp($name);
	}


	/**
	 * Adds xp to player
	 * @param type $amount
	 * @param type $name
	 * @return xpCalculator
	 */
		public function addXp ($name, $amount) {
			$xp = new xpCalculator($this, $name);
			$xp->addXp($amount);
		}


	/**
	 * removes xp to player
	 * @param type $name
	 * @param type $amount
	 * @return xpCalculator
	 */
	public function removeXp ($name, $amount) {
		$xp = new xpCalculator($this, $name);
		$xp->removeXp($amount);
	}


	public function getLevel ($name) {
		$level = new GetLevel($this, $name);
		return $level->getLevel();
	}


	public function addLevel ($name, $amount) {
		$level = new LevelCounter($this, $name);
		return $level->addLevel($amount);
	}

	public function removeLevel ($name, $amount) {
		$level = new LevelCounter($this, $name);
		return $level->removeLevel($amount);
	}
	/**
	 * Returns Player stats to display
	 * @param type $type
	 * @return GetData
	 */
	public function getData ($type) {
		$data = new GetData($this, $type);
		return $data->getData($type);
	}


	/**
     * 
     * @param string $text
     * @return type
     */
    public function colorize(string $text) {
        $color = str_replace("&", "§", $text);
		return $color;
    }
}
