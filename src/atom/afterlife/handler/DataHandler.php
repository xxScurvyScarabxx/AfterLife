<?php

/**
 *  ____            _               _   _                       _   _               
 * |  _ \    __ _  | |_    __ _    | | | |   __ _   _ __     __| | | |   ___   _ __ 
 * | | | |  / _` | | __|  / _` |   | |_| |  / _` | | '_ \   / _` | | |  / _ \ | '__|
 * | |_| | | (_| | | |_  | (_| |   |  _  | | (_| | | | | | | (_| | | | |  __/ | |   
 * |____/   \__,_|  \__|  \__,_|   |_| |_|  \__,_| |_| |_|  \__,_| |_|  \___| |_|                                                                                  
 *                                                                                    
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza
 */

namespace atom\afterlife\handler;

# main files
use pocketmine\Player;
use pocketmine\Server;

# utils
use pocketmine\utils\TextFormat as color;

# plugin instance - Main::getInstance()
use atom\afterlife\Main;

class DataHandler {

    public static $database;

    public static function connect () {
		$server = Main::getInstance()->config->get('server');
		$username = Main::getInstance()->config->get('username');
		$password = Main::getInstance()->config->get('password');
		$name = Main::getInstance()->config->get('database');

		if (empty($server) || empty($username) || empty($name)) {
			Main::getInstance()->getLogger()->warning("Please verify your SQL Credentials!");
		} else {
			$connection = mysqli_connect($server, $username, $password, $name);
		
			if (!$connection) {
				Main::getInstance()->getLogger()->warning("Unable to connect to MySQL");
				Server::getInstance()->getPluginManager()->disablePlugin(Main::getInstance());
				exit();
			} else {
				self::$database = $connection;
				Main::getInstance()->getLogger()->notice("connected to MySQL");
				Main::getInstance()->getLogger()->notice("Loaded Database");
			}
		}
	}
}
