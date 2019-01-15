# AfterLife Features
Fully featured kill/death scoring plugin plus custom death event


![Poggit](https://poggit.pmmp.io/ci.shield/electrode-MP/AfterLife/AfterLife)
[![Total views](http://hits.dwyl.io/Atomization/Afterlife.svg)](http://hits.dwyl.io/Atomization/Afterlife)

 - [x] Score points on Kill! `(+ gain xp)`
 - [x] Losse xp on Death!
 - [x] Calculates kill/death ratio 
 - [x] Level up when acheved spesified amount of XP `(see config)`
 - [x] Commands to see your or another players' stats `(suports formAPI)`
 - [x] Enable floating texts to see leaderboard of stats `(see commands)`
 - [x] Custom eventing for kills/deaths `(see Custom Event)`
 - [ ] Add commands to easialy change settings in config
 - [ ] Add Level up timer `(level up over time, so stay online to level up!)`
 - [ ] Add Top XP Leaderboards
 - [ ] Add Longest Bow Kills & Hits Leaderboards
 - [ ] Add Display Levels beside name in chat and nametag
 
### Custom Event
The custom event is simple, it disables the title screen to prevent accedendal quit to menu ;)
```yml
# config.yml
#choose between 'custom' or 'default'
death-method: "custom"
```

### Commands
| Command | Usage | Description |
| ------- | ----- | ----------- |
| `/stats` | `/stats <player>` | Shows yours or another players stats. |
| `/setlearderboard` | `/setleaderboard <type>` | Creates a floating text at players location. |
| ----------------------------- |
| Floating Text Types | 
| `levels` |
| `kills` |
| `kdr` |
| `streaks` |

### Full Config
```yml
#Set world to what world you want the texts to spawn in. Currently only supports one world.
#enable floating texts.
#true: false:
texts-enabled: true
texts-world: "lobby"

#how many players to display
texts-top: 5

#setts the title for each leaderboard
texts-title:
  levels: "&b< PvP Levels Leaderboard >"
  kills: "&b< Kills Leaderboard >"
  kdr: "&b< K/D Ratio Leaderboard >"
  streaks: "&b< Top Killstreaks >"

#Disables PvP at spawn... uses server default level, 
#if want to use custom level set this to false and use (no-PvP-in-level)
no-PvP-at-spawn: true

#disables PvP in spesified world
#works if no-PvP-at-spawn: is set to false
#may add worlds!
no-PvP-in-level:
  - "world1"
  - "world2"
  - "world3"

#choose to use unique "form" or "standard" message to display stats
#form requires FormApi plugin
#methods => "form" "standard"
profile-method: "form"

#choose between 'custom' or 'default'
#custom bypasses the death 'main menu' screen and default does not
death-method: "custom"

#use built in level up system that adds levels on kill and removes level on death
#choose 'false' if you alredy have a level up plugin
#true: false:
use-levels: true

#use level up timer
#adds xp over time
#(example) stay online to gain xp
use-level-up-timer: true

#amount of xp to be given on kill
add-level-xp-amount: 50

#amout of xp to be lost on death
loose-level-xp-amount: 10

#how much xp is required for level up
xp-levelup-ammount: 1000

#-------------------------------------------------------------------------------------------------------------------------
# DATA STORING!!!
#
# How you want to store data
#
# - online database (use if you have more than one server and want to sync kill score across all servers)
# - local dadabase (DEFAULT) (use if you only have one single server)
#
# online database is complex to setup, use only if you know actly what is mysql is and how to operate a online database
# online database totorial is coming soon to help in-experienced users!
#-------------------------------------------------------------------------------------------------------------------------

# - local - online
database: "local"

#if database is online... please enter credentials
server: "localhost"
username: ""
password: ""
database: ""
```
## 💰 Credits
Icon made by Freepik from www.flaticon.com is licensed by CC 3.0 BY
