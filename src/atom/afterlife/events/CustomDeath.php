<?php

namespace atom\afterlife\events;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as color;
use pocketmine\math\Vector3;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class CustomDeath implements Listener {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onDamage(EntityDamageEvent $event) {

        if ($this->plugin->config->get("death-method") == "custom") {

            $victim = $event->getEntity();
            if ($event->getFinalDamage() >= $victim->getHealth()) {
                $event->setCancelled();
                if ($victim instanceof Player) {
                    $victim->setHealth($victim->getMaxHealth());
                    $victim->setFood(20);
                    $victim->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn(), 0, 0);
                    $victim->getInventory()->setHeldItemIndex(1);
                }

                if ($event instanceof EntityDamageByEntityEvent) {
                    $killer = $event->getDamager();
                    $this->plugin->addKill($killer->getName());
                    $this->plugin->addDeath($victim->getName());
                    $this->plugin->getServer()->broadcastMessage(color::GRAY.$victim->getName().color::WHITE." Was Killed by ".color::GRAY.$killer->getName());
                    if ($this->plugin->config->get("use-levels") == true) {
                        $this->plugin->addXp($killer->getName(), $this->plugin->config->get("add-level-xp-amount"));
                        $this->plugin->removeXp($victim->getName(), $this->plugin->config->get("loose-level-xp-amount"));
                        if ($this->plugin->getXp($killer->getName()) == $this->plugin->config->get("xp-levelup-ammount")) {
                            $this->plugin->addLevel($killer->getName(), $this->plugin->getLevel() + 1);
                        }
                    }
                } else {
                    $this->plugin->addDeath($victim->getName());
                    if ($this->plugin->config->get("use-levels") == true) {
                        $this->plugin->removeXp($victim->getName(), $this->plugin->config->get("loose-level-xp-amount"));
                    }
                }
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $players) {
                    /*
                    sets the floating text 10 times in an attempt 
                    to overrite the existing text leader boards
                    as there is no function in pmmp to remove floating text

                    There should be `removeParticle();` but there isnt :(
                    */
                    for ($i=0; $i <= 10; $i++) { 
                        $this->sendText($players->getName());
                    }
                }
            }
        }
    }

    public function sendText(string $name) {
        foreach($this->plugin->texts->getAll() as $loc => $type) {
        $pos = explode("_", $loc);
            if(isset($pos[1])) {
                $v3 = new Vector3((float) $pos[0],(float) $pos[1],(float) $pos[2]);
                $this->plugin->addText($v3, $type, [$this->plugin->getServer()->getPlayerExact($name)]);
            }
        }
    }
}
