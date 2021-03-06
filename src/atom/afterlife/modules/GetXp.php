<?php

/**
 *   ____          _    __  __  ____  
 *  / ___|   ___  | |_  \ \/ / |  _ \ 
 * | |  _   / _ \ | __|  \  /  | |_) |
 * | |_| | |  __/ | |_   /  \  |  __/ 
 *  \____|  \___|  \__| /_/\_\ |_|    
 *                                   
 * @author iAtomPlaza
 * @link https://twitter.com/iAtomPlaza
 */
namespace atom\afterlife\modules;

use atom\afterlife\handler\DataHandler as mySQL;

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
                    $this->xp = $db[$x]['xp'];
                    $this->totalXP = $db[$x]['totalXP'];
                }
            }
        }
    }

    public function getXp() {
        $diff = abs($this->plugin->config->get('xp-levelup-ammount') - $this->xp);
        return $diff;
    }

    public function getTotalXp() {
        return $this->totalXP;
    }

    public function getPath() {
        return $this->plugin->getDataFolder() . "players/" . $this->player . ".yml";
    }

}
