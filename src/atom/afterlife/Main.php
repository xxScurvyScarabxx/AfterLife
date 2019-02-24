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
 * @version 4.0.0
 * @copyright GNU (general public license)
 */

namespace atom\afterlife;

# Main Files
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

#commands
use atom\afterlife\commands\StatsCommand;
use atom\afterlife\commands\LeaderboardCommand;

# events
use atom\afterlife\events\InitEvent;
use atom\afterlife\events\DeathEvent;
use atom\afterlife\events\CustomDeathEvent;
use atom\afterlife\events\LevelChangeEvent;
use atom\afterlife\events\PlayerDamageEvent;

# plugin files
use atom\afterlife\API;
use atom\afterlife\handler\DataHandler as mySQL;


class Main extends PluginBase {

	/** config data */
	public $config;

	/** @var $this */
	public static $instance;

	/** @var array */
	public $ftps = [];

	/** @api */
	private $api;


	/**
	 * Registers commands
	 * @return void
	 */
	public function onLoad():void {
		$map = $this->getServer()->getCommandMap();
		$map->register("afterlife", new StatsCommand($this));
		$map->register("afterlife", new LeaderboardCommand($this));
	}
	
	public function onEnable():void {
		# Registers plugin instance
		self::$instance = $this;

		# registers API
		$this->api = new API();

		# Registers the plugin events.
		$this->getServer()->getPluginManager()->registerEvents(new InitEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new DeathEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new CustomDeathEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new LevelChangeEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new PlayerDamageEvent($this), $this);

		$this->saveDefaultConfig();
		$this->reloadConfig();

		# Creats config files to store plugin settings.
        @mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . 'players/');
		@mkdir($this->getDataFolder() . 'leaderboards/');
        $this->config = $this->getConfig();
		
		# loads mysqli database
		if ($this->config->get('database-type') === "online") {
			mySQL::connect();
			mySQL::$database->query("CREATE TABLE IF NOT EXISTS `afterlife`(`id` int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` TINYTEXT NOT NULL, `kills` int(5) NOT NULL, `deaths` int(5) NOT NULL, `ratio` FLOAT NOT NULL, `totalXP` int(5) NOT NULL, `xp` int(5) NOT NULL, `level` int(5) NOT NULL, `streak` int(5) NOT NULL)");			
		}
	}

	public function onDisable():void {
		# closes mysqli connection if set
		if (isset($this->mysqli)) {
			mySQL::$database->close();
			// $this->getLogger()->notice("Connection to database terminated!");
		}
	}

	/**
	 * Retrieves this plugins instance
	 * @return Plugin
	 */
	public static function getInstance():Plugin {
		return self::$instance;
	}


	/**
	 * Returns the plugin api
	 * @api
	 * @return API 
	 */
	public function getAPI():API {
		return $this->api;
	}
}
