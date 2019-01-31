<?php

namespace atom\afterlife\modules;

use pocketmine\Player;
use pocketmine\utils\TextFormat as color;
use atom\afterlife\handler\DataHandler as mySQL;


class GetData {

    private $plugin;

    public function __construct($plugin, $player) {
        $this->plugin = $plugin;
    }

    public function getData(string $type) {
        $files = scandir($this->plugin->getDataFolder() . "players/");
        $stats = [];
        switch($type) {
            case "levels":
                $string = "level";
                break;
            case "kills":
                $string = "kills";
                break;
            case "kdr":
                $string = "ratio";
                break;
            case "streaks":
                $string = "streak";
        }
        if ($this->plugin->config->get('type') !== "online") {
            foreach($files as $file) {
                if(pathinfo($file, PATHINFO_EXTENSION) == "yml") {
                    $yaml = file_get_contents($this->plugin->getDataFolder() . "players/" . $file);
                    $rawData = yaml_parse($yaml);
                    if(isset($rawData[$string])) {
                        $stats[$rawData["name"]] = $rawData[$string];
                    }
                }
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
                foreach ($db as $key => $rawData) {
                    array_shift($rawData);
                    if(isset($rawData[$string])) {
                        $stats[$rawData["name"]] = $rawData[$string];
                    }
                }
            }
        }
        arsort($stats, SORT_NUMERIC);
        $finalRankings = "";
        $integer = 1;
        foreach($stats as $name => $number) {
            $finalRankings .= color::YELLOW . $integer . ".) " . $name . ": " . $number . "\n";
            if($integer > $this->plugin->config->get("texts-top")) {
                return $finalRankings;
            }
            if(count($stats) <= $integer) {
                return $finalRankings;
            }
            $integer++;
        }
        return "";
    }
}
 
