<?php

namespace atom\afterlife\events;

use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class SetUpEvent implements Listener {

    private $plugin;
    private $player = null;
    private $database;

    private $names = [];

    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
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
        
        $this->setText($this->player);
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

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

    public function save() {
        if ($this->plugin->config->get('type') !== "online") {
            yaml_emit_file($this->getPath(), ["name" => $this->player, "level" => 0, "xp" => 0, "kills" => 0, "deaths" => 0, "kill-streak" => 0, "kill/death-ratio" => 0]);
        } else {
            $sql = "INSERT INTO afterlife(name, kills, deaths, ratio, xp, level) VALUES ('$this->player', '0', '0', '0', '0', '0')";
            mysqli_query($this->database, $sql);
            array_push($this->names, $this->player);
        }
    }

}
