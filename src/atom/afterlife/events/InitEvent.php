<?php

/**
 * ___           _   _     _           _   _                  _____                 _         
 *|_ _|  _ __   (_) | |_  (_)   __ _  | | (_)  ____   ___    |_   _|   ___  __  __ | |_   ___ 
 * | |  | '_ \  | | | __| | |  / _` | | | | | |_  /  / _ \     | |    / _ \ \ \/ / | __| / __|
 * | |  | | | | | | | |_  | | | (_| | | | | |  / /  |  __/     | |   |  __/  >  <  | |_  \__ \
 *|___| |_| |_| |_|  \__| |_|  \__,_| |_| |_| /___|  \___|     |_|    \___| /_/\_\  \__| |___/
 *
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza                                                                                            
 */

namespace atom\afterlife\events;

# player instance
use pocketmine\Player;

# position
use pocketmine\level\Position;

#events
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

# data handler
use atom\afterlife\handler\DataHandler as mySQL;
use atom\afterlife\handler\FloatingTextHandler as Leaderboard;

class InitEvent implements Listener {

    private $plugin;
    private $player = null;
    private $playerOBJ = null;
    private $database;

    private $names = [];

    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->playerOBJ = $event->getPlayer();
        $this->player = $player->getName();

        $files = scandir($this->plugin->getDataFolder() . "players/");
        if ($this->plugin->config->get('type') === "online") {
            $this->database = mySQL::$database;
            $sql = "SELECT * FROM afterlife";
            $stmt = mysqli_stmt_init($this->database);
            $result = mysqli_query($this->database, $sql);
            $array = [];
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $array[] = $row;
                }

                foreach ($array as $data) {
                    array_push($this->names, $data['name']);
                }

                if (!in_array($this->player, $this->names)) {
                    $this->save();
                }
            } else {
                $this->save();
            }
        } else {
            if (!in_array($player->getName().".yml", $files)) {
                $this->save();
            }
        }
        $this->setText($player);
    }

    public function setText(Player $player) {
        $name = $player->getName();
        $files = scandir($this->plugin->getDataFolder() . 'leaderboards/');
        foreach ($files as $file) {
            $path = $this->plugin->getDataFolder(). 'leaderboards/' . $file;
            if (is_file($path)) {
                $data = yaml_parse_file($path);
                $level = $data['level'];
                $type = $data['type'];
                $xx = $data['xx'];
                $yy = $data['yy'];
                $zz = $data['zz'];
                $possition = new Position($xx, $yy, $zz, $this->plugin->getServer()->getLevelByName($level));
                Leaderboard::addText($possition, $level, $type, $this->plugin->getServer()->getPlayerExact($name));
                switch ($this->plugin->getServer()->getName()) {
                    case 'PocketMine-MP':
                        if (!isset($this->plugin->ftps[$type][$player->getLevel()->getName()])) {
                            $ftp = $this->plugin->ftps[$type][$level];
                            $ftp->setInvisible();
                            $player->getLevel()->addParticle($ftp, [$player]);
                        } else {
                            $ftp = $this->plugin->ftps[$type][$level];
                            $ftp->setInvisible(false);
                            $player->getLevel()->addParticle($ftp, [$player]);
                        }
                        break;

                    default:
				        $player->sendMessage("FloatingTextParticle not supported on this server!");
				        break;
                }
            }
        }
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

    public function save() {
        if ($this->plugin->config->get('type') !== "online") {
            yaml_emit_file($this->getPath(), ["name" => $this->player, "level" => 0, "totalXP" => 0, "xp" => 0, "kills" => 0, "deaths" => 0, "streak" => 0, "ratio" => 0]);
        } else {
            $sql = "INSERT INTO afterlife(name, kills, deaths, ratio, totalXP, xp, level, streak) VALUES ('$this->player', '0', '0', '0', '0', '0', '0', '0')";
            mysqli_query($this->database, $sql);
            array_push($this->names, $this->player);
        }
    }

}
