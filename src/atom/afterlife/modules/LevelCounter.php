<?php

namespace atom\afterlife\modules;

use pocketmine\Player;

class LevelCounter {

    private $plugin;
    private $level;
    private $xp;
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
                $this->level = $data["level"];
                $this->xp = $data["xp"];
                $this->kills = $data["kills"];
                $this->deaths = $data["deaths"];
                $this->killStreak = $data["kill-streak"];
                $this->ratio = $data["kill/death-ratio"];
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
                    $this->kills = $db[$x]['kills'];
                    $this->deaths = $db[$x]['deaths'];
                    $this->ratio = $db[$x]['ratio'];
                    $this->xp = $db[$x]['xp'];
                    $this->level = $db[$x]['level'];
                    $this->killStreak = $db[$x]['streak'];
                }
            }
        }
    }

    public function addlevel($amount) {
        $this->level += $amount;
        $this->xp = 0;
        $this->save();
    }

    public function removelevel($amount) {
        $this->level -= $amount;
        $this->save();
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

    public function save() {
        if ($this->plugin->config->get('type') !== "online") {
            yaml_emit_file($this->getPath(), ["name" => $this->player, "level" => $this->level, "xp" => $this->xp, "kills" => $this->kills, "deaths" => $this->deaths, "kill-streak" => $this->killStreak, "kill/death-ratio" => $this->ratio]);
        } else {
            $sql = "UPDATE afterlife SET level='$this->level' WHERE name='$this->player'";
            mysqli_query($this->plugin->mysqli, $sql);
        }
    }
}
