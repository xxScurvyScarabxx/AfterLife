<?php

namespace atom\afterlife\modules;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;

class PvpEvent implements Listener {

    private $plugin;


    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onDamage(EntityDamageEvent $event) {
        $nopvpAtSpawn = $this->plugin->config->get("no-PvP-at-spawn");
        $nopvpInLevel = $this->plugin->config->get("no-PvP-in-level");

        if ($nopvpAtSpawn == true) {
            if ($event->getEntity()->getLevel() == $this->plugin->getServer()->getDefaultLevel()) {
                $event->setCancelled();
            } 
        }
        if ($nopvpAtSpawn == false) {
            foreach ($nopvpInLevel as $levels) {
                if ($event->getEntity()->getLevel() == $this->plugin->getServer()->getLevelByName($levels)) {
                    $event->setCancelled();
                }
            }
        }
    }

}
