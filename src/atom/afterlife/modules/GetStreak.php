<?php

/**
 *   ____          _       _  __  _   _   _     ____    _                           _    
 *  / ___|   ___  | |_    | |/ / (_) | | | |   / ___|  | |_   _ __    ___    __ _  | | __
 * | |  _   / _ \ | __|   | ' /  | | | | | |   \___ \  | __| | '__|  / _ \  / _` | | |/ /
 * | |_| | |  __/ | |_    | . \  | | | | | |    ___) | | |_  | |    |  __/ | (_| | |   < 
 *  \____|  \___|  \__|   |_|\_\ |_| |_| |_|   |____/   \__| |_|     \___|  \__,_| |_|\_\
 *
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza                                                                                      
 */

namespace atom\afterlife\modules;

use atom\afterlife\handler\DataHandler as mySQL;

class GetStreak {

    private $plugin;
    private $streak;
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
                $this->streak = $data["streak"];
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
                    $this->streak = $db[$x]['streak'];
                }
            }
        }
    }

    public function getStreak() {
        return $this->streak;
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

}
