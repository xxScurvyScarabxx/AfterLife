<?php

namespace atom\afterlife\events;

use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class SetUpEvent implements Listener {

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
            $this->database = $this->plugin->mysqli;
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
                $this->plugin->addText($possition, $level, $type, $this->plugin->getServer()->getPlayerExact($name));
                if (!isset($this->plugin->ftps[$type][$player->getLevel()->getName()])) {
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

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

    public function save() {
        if ($this->plugin->config->get('type') !== "online") {
            yaml_emit_file($this->getPath(), ["name" => $this->player, "level" => 0, "xp" => 0, "kills" => 0, "deaths" => 0, "streak" => 0, "ratio" => 0]);
        } else {
            $sql = "INSERT INTO afterlife(name, kills, deaths, ratio, xp, level, streak) VALUES ('$this->player', '0', '0', '0', '0', '0', '0')";
            mysqli_query($this->database, $sql);
            array_push($this->names, $this->player);
        }
    }

}
