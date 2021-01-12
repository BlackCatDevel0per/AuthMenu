<?php

 #  _   _       _   _           _____             
 # | \ | |     | | (_)         |  __ \            
 # |  \| | __ _| |_ ___   _____| |  | | _____   __
 # | . ` |/ _` | __| \ \ / / _ \ |  | |/ _ \ \ / /
 # | |\  | (_| | |_| |\ V /  __/ |__| |  __/\ V / 
 # |_| \_|\__,_|\__|_| \_/ \___|_____/ \___| \_/  
 #
 # Больше плагинов в https://vk.com/native_dev
 # По вопросам native.dev@mail.ru

namespace blackcatdev\AuthMenu;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\PopSound;
use pocketmine\math\Vector3;
use blackcatdev\AuthMenu\libs\jojoe77777\FormAPI\CustomForm;


class AuthMenu extends PluginBase implements Listener {
    
    public function onEnable() {
        $this->auth = $this->getServer()->getPluginManager()->getPlugin("SimpleAuth");
        if (!$this->auth) {
            $this->getLogger()->error(TextFormat::RED.("Меню плагина не будет работать без плагина SimpleAuth!"));
            $this->getLogger()->error(TextFormat::RED.("Установите плагин SimpleAuth!"));
            return;
        }
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->saveDefaultConfig();
        $this->reloadConfig();
        
         $this->getLogger()->info("§2Плагин §e[AuthMenu] §2Запущен! §1https://vk.com/native_dev");
    }

    public function onDisable() {

        $this->getLogger()->info("§cПлагин §e[AuthMenu] §cВыключен §1https://vk.com/native_dev");
    }
    
    # Кеширование паролей
    private function hash($salt, $password){
    return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
    }
    public function authenticate($pl,$password) {
        $provider = $this->auth->getDataProvider();
        $data = $provider->getPlayerData($pl->getName());
        
        return hash_equals($data["hash"], $this->hash(strtolower($pl->getName()), $password));
        
    }
    
    public function loginForm($player){
        $level = $player->getLevel();
        $x = $player->getX();
        $y = $player->getY();
        $z = $player->getZ();
        $pos = new Vector3($x, $y, $z);
        
        $form = new CustomForm(function (Player $player, $data) use ($level, $pos){
            
            if (!$data[1]){
                return $player->kick(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("logmenu-empty-passwd-field"));
            }
            
            if ($this->authenticate($player, $data[1])){
                $level->addSound(new PopSound($pos));
                $this->auth->authenticatePlayer($player);
            } else {
                $level->addSound(new FizzSound($pos));
                $this->loginForm($player);
            }


        });
        $form->setTitle(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("logmenu-title"));
        $form->addLabel(TextFormat::BOLD . TextFormat::AQUA . $this->getConfig()->get("logmenu-text1"));
        $form->addInput($this->getConfig()->get("logmenu-text2"), $this->getConfig()->get("logmenu-passwd-field"));
        $form->sendToPlayer($player);
    }
    
    public function registerForm($player){
        $form = new CustomForm(function (Player $sender, $data) use ($player){
            if (!$data[1]){
                if (!$data[1]){
                    return $player->kick(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("regmenu-empty-passwd-field"));
                }
            } else {
                $this->auth->registerPlayer($sender, $data[1]);
                if ($this->auth->isPlayerRegistered($sender)){
                    $this->auth->authenticatePlayer($sender);
                };
            }
        });
        $form->setTitle(TextFormat::GREEN . TextFormat::BOLD . $this->getConfig()->get("regmenu-title"));
        $form->addLabel(TextFormat::BOLD . TextFormat::AQUA . $this->getConfig()->get("regmenu-text1"));
        $form->addInput($this->getConfig()->get("regmenu-text2"), $this->getConfig()->get("regmenu-passwd-field"));
        $form->sendToPlayer($player);
    }
    
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        
        if(!$this->auth->isPlayerRegistered($player)){
            $this->registerForm($player);
           
        } else if ($this->auth->isPlayerRegistered($player) && !$this->auth->isPlayerAuthenticated($player)){
            $this->loginForm($player);
            
        }
    }
}