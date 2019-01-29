<?php

namespace atom\afterlife;


# Main Files
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;

# events
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;

# calculating
use pocketmine\math\Vector3;
use pocketmine\level\Position;

#commands
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

# utils
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as color;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\level\particle\FloatingTextParticle;

# customui
use xenialdan\customui\API as Form;
use xenialdan\customui\elements\Button;
use xenialdan\customui\windows\SimpleForm;

# plugin files
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



use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\BlockFactory;

class Main extends PluginBase implements Listener {

	public $mysqli;

	public $config;
	public $texts;
	public $playerData = [];
	public $particles = [];
	public $ftps = [];
	
	/** @var int[] **/
	public static $uis = [];
	
	public function onEnable() {

		# Registers the plugin events.
		$this->getServer()->getPluginManager()->registerEvents(new SetUpEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new KillEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new CustomDeath($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new NoPvP($this), $this);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->saveDefaultConfig();
		$this->reloadConfig();

		# Creats config files to store plugin settings for easy editing.
        @mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . 'players/');
		@mkdir($this->getDataFolder() . 'leaderboards/');
        $this->config = $this->getConfig();
		
		# loads mysqli database
		if ($this->config->get('type') === "online") {
			$this->mysqlConnect();
			$this->mysqli->query("CREATE TABLE IF NOT EXISTS `afterlife`(`id` int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` TINYTEXT NOT NULL, `kills` int(5) NOT NULL, `deaths` int(5) NOT NULL, `ratio` FLOAT NOT NULL, `totalXP` int(5) NOT NULL, `xp` int(5) NOT NULL, `level` int(5) NOT NULL, `streak` int(5) NOT NULL)");
		}
	}

	private function statsUI(Player $player){
		switch ($this->getServer()->getName()) {
			case 'PocketMine-MP':
				$ui = new SimpleForm('Player Stats',
				color::YELLOW."\nCurrent Win Streak ".color::BLUE.$this->getStreak($player->getName())."\n\n".
				color::RED."\nKills: ".color::GREEN.$this->getKills($player->getName()).
				color::RED."\nDeaths: ".color::GREEN.$this->getDeaths($player->getName()).
				color::RED."\nK/D Ratio: ".color::GREEN.$this->getKdr($player->getName()).
				color::RED."\n\n\nLevel: ".color::GREEN.$this->getLevel($player->getName()).
				color::RED."\nExperience: ".color::GREEN.$this->getXp($player->getName())."\n\n\n"
				);
				$button = new Button(color::RED.'Close'); 
				$button->addImage(Button::IMAGE_TYPE_PATH, "textures/items/stick");
				$ui->addButton($button);
				self::$uis['statsui'] = Form::addUI($this, $ui);
				// var_dump($button);
				break;
				
			default;
				$player->sendMessage("Forms are not *YET* supported on this fork... please choose 'standard in config'");
				break;
		}
	}


	/**
     * Initializes Floating Texts.
     * @param Vector3 $location
     * @param string $type
     * @param array $player
     */
	public function addText(Vector3 $location, $level, string $type = "title", $player) {
		switch ($this->getServer()->getName()) {
			case 'PocketMine-MP':
				$title = $this->config->get("texts-title")[$type];
				$particle = new FloatingTextParticle($location, $this->colorize($title) . "\n" . $this->getData($type));
				$player->getLevel()->addParticle($particle, [$player]);
				$this->ftps[$type][$level] = $particle;
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

	public function levelChangeEvent(EntityLevelChangeEvent $action):void {
		$player = $action->getEntity();
		$target = $action->getTarget();
		$files = scandir($this->getDataFolder() . 'leaderboards/');
        foreach ($files as $file) {
            $path = $this->getDataFolder(). 'leaderboards/' . $file;
            if (is_file($path)) {
                $data = yaml_parse_file($path);
				$level = $data['level'];
				$type = $data['type'];
                if (!isset($this->ftps[$type][$target->getName()])) {
                    $ftp = $this->ftps[$type][$level];
                    $ftp->setInvisible();
                    $player->getLevel()->addParticle($ftp, [$player]);
                } else {
                    $ftp = $this->ftps[$type][$level];
                    $ftp->setInvisible(false);
                    $player->getLevel()->addParticle($ftp, [$player]);
                }
            }
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
								if (!isset($this->ftps[$args[0]][$player->getLevel()->getName()])) {
									$level = $player->getLevel()->getName();
									$x = round($player->getX(), 1);
									$y = round($player->getY(), 1) + 1.7;
									$z = round($player->getZ(), 1);
									yaml_emit_file($this->getDataFolder() . "leaderboards/" . $args[0] . "_" . $level . ".yml", ['level'=>$level, 'type'=>$args[0], 'xx'=>$x, 'yy'=>$y, 'zz'=>$z]);
									$possition = new Position($player->getX(), $player->getY() + 1.7, $player->getZ(), $player->getLevel());
									$this->addText($possition, $player->getLevel()->getName(), $args[0], $player);
									$player->sendMessage(color::RED.$args[0].color::YELLOW." leaderboard created!");
								} else {
									$player->sendMessage('Error:'.' '.$args[0].' '.'Floating text already exists in'.' '.$player->getLevel()->getName());
								}
							} elseif ((in_array($args[0], ["del", "remove", "delete"]))) {
                                // coming soon
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

	public function getStats (Player $player) {
		switch ($this->config->get("profile-method")) {
			case "form":
				$this->statsUI($player);
				Form::showUIbyID($this, self::$uis['statsui'], $player);
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
				$this->getLogger()->notice("Loaded Database");
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
		$ratio = new GetRatio($this, $name);
		return $ratio->getRatio();
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
