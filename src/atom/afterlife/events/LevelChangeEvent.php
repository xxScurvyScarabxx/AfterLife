<?php

namespace atom\afterlife\events;

use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityLevelChangeEvent;

class LevelChangeEvent implements Listener {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function levelChangeEvent(EntityLevelChangeEvent $action):void {
		$player = $action->getEntity();
		$target = $action->getTarget();
		$files = scandir($this->plugin->getDataFolder() . 'leaderboards/');
        foreach ($files as $file) {
            $path = $this->plugin->getDataFolder(). 'leaderboards/' . $file;
            if (is_file($path)) {
                $data = yaml_parse_file($path);
				$level = $data['level'];
				$type = $data['type'];
                if (!isset($this->plugin->ftps[$type][$target->getName()])) {
                    $ftp = $this->plugin->ftps[$type][$level];
                    $ftp->setInvisible();
                    $player->getLevel()->addParticle($ftp, [$player]);
                } else {
                    $ftp = $this->plugin->ftps[$type][$level];
                    $ftp->setInvisible(false);
                    $player->getLevel()->addParticle($ftp, [$player]);
                }
            }
        }
	}

}
