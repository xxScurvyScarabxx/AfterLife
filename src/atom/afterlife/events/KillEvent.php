<?php

namespace atom\afterlife\events;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;


class KillEvent implements Listener {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onDeath(PlayerDeathEvent $event) {
        if ($this->plugin->config->get("death-method") == "default") {

            $victim = $event->getPlayer();
            if ($victim->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
                $killer = $victim->getLastDamageCause()->getDamager();
                $this->plugin->addKill($killer->getName());
                $this->plugin->addDeath($victim->getName());
                if ($this->plugin->config->get("use-levels") == true) {
                    $this->plugin->addXp($killer->getName(), $this->plugin->config->get("add-level-xp-amount"));
                    $this->plugin->removeXp($victim->getName(), $this->plugin->config->get("loose-level-xp-amount"));
                    if ($this->plugin->getXp($killer->getName()) > $this->plugin->config->get("xp-levelup-ammount")) {
                        $this->plugin->addLevel($killer->getName(), $this->plugin->getLevel($killer->getName()) + 1);
                    }
                }
            } else {
                $this->plugin->addDeath($victim->getName());
                if ($this->plugin->config->get("use-levels") == true) {
                    $this->plugin->removeXp($victim->getName(), $this->plugin->config->get("loose-level-xp-amount"));
                }
            }
        }
    }

    public function setText(string $name) {
        foreach($this->plugin->texts->getAll() as $loc => $type) {
        $pos = explode("_", $loc);
            if(isset($pos[1])) {
                $v3 = new Vector3((float) $pos[0],(float) $pos[1],(float) $pos[2]);
                $this->plugin->addText($v3, $type, [$this->plugin->getServer()->getPlayerExact($name)]);
            }
        }
    }
}
