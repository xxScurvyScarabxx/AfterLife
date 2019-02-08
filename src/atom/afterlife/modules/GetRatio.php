<?php

/**
 *   ____          _       _  __     __  ___       ____            _     _         
 *  / ___|   ___  | |_    | |/ /    / / |  _ \    |  _ \    __ _  | |_  (_)   ___  
 * | |  _   / _ \ | __|   | ' /    / /  | | | |   | |_) |  / _` | | __| | |  / _ \ 
 * | |_| | |  __/ | |_    | . \   / /   | |_| |   |  _ <  | (_| | | |_  | | | (_) |
 *  \____|  \___|  \__|   |_|\_\ /_/    |____     |_| \_\  \__,_|  \__| |_|  \___/ 
 *                        
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza                                                              
 */

namespace atom\afterlife\modules;

use atom\afterlife\handler\DataHandler as mySQL;

class GetRatio {

    private $plugin;
    private $kills;
    private $deaths;
    private $ratio;
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
                $this->deaths = $data["deaths"];
                $this->ratio = $data["ratio"];
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
                    $this->deaths = $db[$x]['deaths'];
                    $this->ratio = $db[$x]['ratio'];
                }
            }
        }
    }

    public function getRatio() {
        if ($this->deaths > 0){
            $this->ratio = round(($this->kills / $this->deaths), 1);
            return $this->ratio;
        } else {
            $this->ratio = 1;
            return 1;
        }
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

}
