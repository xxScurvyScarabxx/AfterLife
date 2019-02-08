<?php

/**
 *   ____          _     ____                   _     _           
 *  / ___|   ___  | |_  |  _ \    ___    __ _  | |_  | |__    ___ 
 * | |  _   / _ \ | __| | | | |  / _ \  / _` | | __| | '_ \  / __|
 * | |_| | |  __/ | |_  | |_| | |  __/ | (_| | | |_  | | | | \__ \
 *  \____|  \___|  \__| |____/   \___|  \__,_|  \__| |_| |_| |___/
 *     
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza                                                           
 */

namespace atom\afterlife\modules;

use atom\afterlife\handler\DataHandler as mySQL;

class GetDeaths {

    private $plugin;
    private $deaths;
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
                $this->deaths = $data["deaths"];
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
                    $this->deaths = $db[$x]['deaths'];
                }
            }
        }
    }

    public function getDeaths() {
        return $this->deaths;
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

}
