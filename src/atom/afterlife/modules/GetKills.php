<?php

/**
 *   ____          _     _  __  _   _   _       
 *  / ___|   ___  | |_  | |/ / (_) | | | |  ___ 
 * | |  _   / _ \ | __| | ' /  | | | | | | / __|
 * | |_| | |  __/ | |_  | . \  | | | | | | \__ \
 *  \____|  \___|  \__| |_|\_\ |_| |_| |_| |___/
 *                
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza                              
 */

namespace atom\afterlife\modules;

use atom\afterlife\handler\DataHandler as mySQL;

class GetKills{

    private $plugin;
    private $kills;
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
                $this->kills = $data["kills"];
            } else {
                return;
            }
        } else {
            $sql = "SELECT * FROM afterlife;";
            $result = mysqli_query(mySQL::$database, $sql);
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
                }
            }
        }
    }

    public function getKills() {
        return $this->kills;
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

}
