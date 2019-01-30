<?php

namespace atom\afterlife\modules;

use pocketmine\Player;

class GetXp {

    private $plugin;
    private $xp;
    private $totalXP;
    private $data = null;
    private $player = null;

    public function __construct($plugin, $player) {
        $this->plugin = $plugin;
        $this->player = $player;
        $path = $this->getPath();
        if ($this->plugin->config->get('type') !== "online") {
            if(is_file($path)) {
                $data = yaml_parse_file($path);
                $this->data = $data;
                $this->xp = $data["xp"];
                $this->totalXP = $data['totalXP'];
            } else {
                return;
            }
        } else {
            $sql = "SELECT * FROM afterlife;";
            $result = mysqli_query($this->plugin->mysqli, $sql);
            $check = mysqli_num_rows($result);
            $db = array();
            $names = array();
            if ($check > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $db[] = $row;
                }
                foreach ($db as $kay => $value) {
                    array_push($names, $value['name']);
                }
                if (in_array($this->player, $names)) {
                    $x = array_search($this->player, $names);
                    $this->xp = $db[$x]['xp'];
                    $this->totalXP = $db[$x]['totalXP'];
                }
            }
        }
    }

    public function getXp() {
        return $this->xp;
    }

    public function getTotalXp() {
        return $this->totalXP;
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

}
