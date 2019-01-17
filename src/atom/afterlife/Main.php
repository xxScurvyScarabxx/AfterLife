<?php

namespace atom\afterlife;


#Main Files
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;

#events
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

#calculating
use pocketmine\math\Vector3;

#commands
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

#utils
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as color;

#other
use pocketmine\item\Item;
use pocketmine\level\particle\FloatingTextParticle;

#plugin files
use atom\afterlife\events\SetUpEvent;
use atom\afterlife\events\KillEvent;
use atom\afterlife\events\CustomDeath;
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
use atom\afterlife\modules\NoPvP;
use atom\afterlife\modules\LevelCounter;

class Main extends PluginBase implements Listener {

	public $mysqli;

	public $config;
	public $texts;
	public $playerData = [];
    public $particles = [];
	
	public function onEnable() {

		#Registers the plugin events.
		$this->getServer()->getPluginManager()->registerEvents(new SetUpEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new KillEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new CustomDeath($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new NoPvP($this), $this);
		$this->saveDefaultConfig();
		$this->reloadConfig();

		#Creats config files to store plugin settings for easy editing.
        @mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "players/");
        $this->config = $this->getConfig();
		$this->texts = new Config($this->getDataFolder() . "texts.yml", Config::YAML);
		$this->texts->save();
		
		#loads mysqli database
		if ($this->config->get('type') === "online") {
			$this->mysqlConnect();
			$this->mysqli->query("CREATE TABLE IF NOT EXISTS `afterlife`(`id` int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` TINYTEXT NOT NULL, `kills` int(5) NOT NULL, `deaths` int(5) NOT NULL, `ratio` int(5) NOT NULL, `xp` int(5) NOT NULL, `level` int(5) NOT NULL, `streak` int(5) NOT NULL)");
		}
	}

	/**
     * Initializes Floating Texts.
     * @param Vector3 $location
     * @param string $type
     * @param array $player
     */
	public function addText(Vector3 $location, string $type = "title", $player = null) {
		switch ($this->getServer()->getName()) {
			case 'PocketMine-MP':
				$typetitle = $this->config->get("texts-title")[$type];
				$id = implode("_", [$location->getX(), $location->getY(), $location->getZ()]);
				$particle = new FloatingTextParticle($location, color::GOLD . "<<<<<>>>>>", $this->colorize($typetitle) . "\n" . $this->getData($type));
				$this->getServer()->getLevelByName($this->config->get("texts-world"))->addParticle($particle, $player);
				$this->particles[$id] = $particle;
				break;

			case 'Altay':
				$typetitle = $this->config->get("texts-title")[$type];
				$id = implode("_", [$location->getX(), $location->getY(), $location->getZ()]);
				$particle = new FloatingTextParticle(color::GOLD . "<<<<<>>>>>", $this->colorize($typetitle) . "\n" . $this->getData($type), $location);
				$this->getServer()->getLevelByName($this->config->get("texts-world"))->addParticle($location, $particle);
				$this->particles[$id] = $particle;
				break;
		}
    }

	public function onCommand (CommandSender $player, Command $cmd, string $label, array $args):bool {
		if ($player instanceof Player) {
			if ($cmd == "stats") {
				if (!isset($args[0])) {
					$this->getStats($player);
				} else {
					$target = $this->getServer()->getPlayerExact($args[0]);
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

								$possition = implode("_", [round($player->getX(), 2), round($player->getY(), 2) + 1.7, round($player->getZ(), 2)]);
								$this->texts->set($possition, $args[0]);
								$this->texts->save();
								$possition = $player->asVector3();
								if ($player->getLevel() === $this->getServer()->getLevelByName($this->config->get("texts-world"))) {
									$this->addText($possition, $args[0], null);
									$player->sendMessage(color::RED.$args[0].color::YELLOW." leaderboard created!");
								} else {
									$player->sendMessage(color::RED."You are not in the world spesified in the config to spawn floating texts...");
									$player->sendMessage(color::RED."Pleae edit config");
								}
							} elseif ((in_array($args[0], ["del", "remove", "delete"]))) {
								// comming soon
							}
						} else {
							$player->sendMessage(color::RED . "Please choose \n ---kills, \n ---levels, \n ---kdr, \n ---streaks, \nor delete");
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
	
	public function getStats (Player $player) {
		switch ($this->config->get("profile-method")) {
			case "form":
				if (($api = $this->getServer()->getPluginManager()->getPlugin("FormAPI")) !== null) {
					$form = $api->createSimpleForm(function (Player $player, ?int $result = null) {
						if ($result === null) {
							return true;
						}

						switch ($result) {
							case 0:
								return true;
								break;
						}
					});

					$form->setTitle(color::BOLD.color::LIGHT_PURPLE.$player." Profile");
					$form->setContent(
						color::YELLOW."\nCurrent Win Streak ".color::GREEN.$this->getStreak($player->getName())."\n\n".
						color::RED."\nKills: ".color::GREEN.$this->getKills($player->getName()).
						color::RED."\nDeaths: ".color::GREEN.$this->getDeaths($player->getName()).
						color::RED."\nK/D Ratio: ".color::BLUE.$this->getKdr($player->getName()).
						color::RED."\n\n\nLevel: ".color::BLUE.$this->getLevel($player->getName()).
						color::RED."\nExperience: ".color::BLUE.$this->getXp($player->getName())."\n\n\n\n\n");
					$form->addButton(color::BOLD. "Exit");
					$form->sendToPlayer($player);
				} else {
					$player->sendMessage(color::LIGHT_PURPLE."Please enable FormAPI else use 'stardard' in config!");
				}
				break;

			case "standard":
				$player->sendMessage(color::LIGHT_PURPLE."*************");
				$player->sendMessage(color::RED."Name: ". color::WHITE.$player->getName());
				$player->sendMessage(color::RED."Kils: ".color::GREEN.$this->getKills($player->getName()));
				$player->sendMessage(color::RED."Deaths: ".color::GREEN.$this->getDeaths($player->getName()));
				$player->sendMessage(color::RED."kdr: ".color::BLUE.$this->getKdr($player->getName()));
				$player->sendMessage(color::GRAY."Win Streak: ".color::BLUE.$this->getStreak($player->getName()));
				$player->sendMessage(color::LIGHT_PURPLE."*************");
				break;
		}
	}


	public function mysqlConnect () {
		$server = $this->config->get('server');
		$username = $this->config->get('username');
		$password = $this->config->get('password');
		$database = $this->config->get('database');

		if (empty($server) || empty($username) || empty($database)) {
			$this->getLogger()->warning("Please verify your SQL Credentials!");
		} else {
			$connection = mysqli_connect($server, $username, $password, $database);
		
			if (!$connection) {
				$this->getLogger()->warning("Unable to connect to MySQL");
				$this->getServer()->getPluginManager()->disablePlugin($this);
				exit();
			} else {
				$this->mysqli = $connection;
				$this->getLogger()->notice("connected to MySQL");
			}
		}
	}


	public function onDisable(): void {
		if (isset($this->mysqli)) {
			$this->mysqli->close();
			$this->getLogger()->notice("Connection to database terminated!");
		}
	}

/**
 * =========
 * =========
 * ===API===
 * =========
 * =========
 */

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
		$deaths = new GetRatio($this, $name);
		return $deaths->getRatio();
	}



	/**
	 * Returns Player Xp
	 * @param type $type
	 * @return GetXp
	 */
	public function getXp ($name) {
		$data = new GetXp($this, $name);
		return $data->getXp($name);
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
