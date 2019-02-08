<?php

/**
 *  ____    _             _            ____                                                       _ 
 * / ___|  | |_    __ _  | |_   ___   / ___|   ___    _ __ ___    _ __ ___     __ _   _ __     __| |
 * \___ \  | __|  / _` | | __| / __| | |      / _ \  | '_ ` _ \  | '_ ` _ \   / _` | | '_ \   / _` |
 *  ___) | | |_  | (_| | | |_  \__ \ | |___  | (_) | | | | | | | | | | | | | | (_| | | | | | | (_| |
 * |____/   \__|  \__,_|  \__| |___/  \____|  \___/  |_| |_| |_| |_| |_| |_|  \__,_| |_| |_|  \__,_|
 *
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza                                                                                                 
 */

namespace atom\afterlife\commands;

# player instance
use pocketmine\Player;

# commands
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;

# main
use atom\afterlife\Main;

class StatsCommand extends PluginCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("stats", $plugin);
        $this->setDescription("shows your or another players stats");
        $this->setAliases(["st"]);
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $player, string $cmd, array $args) {
        if ($player instanceof Player) {
			if (!isset($args[0])) {
				$this->plugin->getAPI()->getStats($player);
			} else {
			    $target = Server::getInstance()->getPlayerExact($args[0]);
                if($target !== null) {
				    $this->plugin->getAPI()->getStats($player);
                } else {
					$player->sendMessage(color::RED . "Player is not online");
				}
			}        
        } else {
            $player->sendMessage("Run commands in-game");
        }
    }
}
