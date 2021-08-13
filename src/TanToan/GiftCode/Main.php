<?php

namespace TanToan\GiftCode;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
//use jojoe77777\FormAPI\FormAPI as FAPI;
class Main extends PluginBase implements Listener
{
    /**
     * @var Config
     */
    private $code, $type, $form;

    public function onEnable()
    {
        if (!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        }
        $this->saveResource("type.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->form = $this->getServer()->getPluginManager()->getPlugin('FormAPI');
        $this->code = new Config($this->getDataFolder() . "code.yml", Config::YAML);
        $this->type = new Config($this->getDataFolder() . "type.yml", Config::YAML);
        //check code lỗi
        $this->getScheduler()->scheduleTask(new Checkallcode($this));
    }

    public function onjoin(PlayerJoinEvent $event)
    {
        $t = $this->code->getAll();
        foreach (array_keys($this->code->getAll()) as $code) {
            if ($t[$code]['Player'] === strtolower($event->getPlayer()->getName())) {
                $event->getPlayer()->sendMessage("§l§f•§a Unused giftcodes are:§c ".$code." §7(§atype:§c ".$t[$code]['Type']."§7)");
                
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch (strtolower($command->getName())) {
            case "giftcode":
                if (!isset($args[0])) {
                    $sender->sendMessage("§l§f•§c Please use the command §a/mycode");
                    return true;
                }
                switch (strtolower($args[0])) {
                    case "help":
                        $sender->sendMessage("§l§f• §c/mycode§a: to use and test your code");
                        break;
                    case "give":
                        if (count($args) !== 3) {
                            $sender->sendMessage("/giftcode give <player> <type> : Give the code to the player");
                            return true;
                        }
                        if (!$sender->isOp()) {
                            $sender->sendMessage("You are not authorized to use this command !");
                            return true;
                        }
                        //check type code
                        $type = $this->type->getAll();
                        if (!isset($type[$args[2]])) {
                            $sender->sendMessage("§cInvalid code type, press §a/giftcode type §cto see");
                            return true;
                        }
                        $code = $this->createcode();
                        $t = $this->code->getAll();
                        $t[$code]["Player"] = strtolower($args[1]);
                        $t[$code]["Type"] = $args[2];
                        $this->code->setAll($t);
                        $this->code->save();
                        $sender->sendMessage("Create code success $code - $args[1] - $args[2]");
                        $player = Server::getInstance()->getPlayer($args[1]);
                        if ($player !== null) {
                            $player->sendMessage("You get the code§c $code §etype§c $args[2] §e. Press §c/mycode <code> §eto use.");
                        }
                        break;
                    case "all":
                        if (!$sender->isOp()) {
                            $sender->sendMessage("You are not authorized to use this command !");
                            return true;
                        }
                        $sender->sendMessage("Code Member has not used yet");
                        $t = $this->code->getAll();
                        foreach (array_keys($this->code->getAll()) as $code) {
                            $sender->sendMessage("- Code " . $code . " - Player: " . $t[$code]['Player'] . "- Type: " . $t[$code]['Type']);
                        }
                        break;
                    case "type":
                        $sender->sendMessage("Types of codes");
                        foreach (array_keys($this->type->getAll()) as $code) {
                            $sender->sendMessage("- Code " . $code);
                        }
                        break;
                    case "get":
                        if (!$sender instanceof Player) {
                            $sender->sendMessage("Using the ingame command ! ");
                            return true;
                        }
						if(!isset($args[1])){
							return true;
						}
                        $code = strtolower($args[1]);
                        $t = $this->code->getAll();
                        if (isset($t[$code])) {
                            if ($t[$code]["Player"] !== strtolower($sender->getName())) {
                                $sender->sendMessage("This is not your GiftCode! Please try again.");
                                return true;
                            }
                            //check slot item
                            $inv = $sender->getInventory();
                            foreach ($this->type->get($t[$code]["Type"]) as $command) {
                                Server::getInstance()->dispatchCommand($sender, str_replace("{player}", $sender->getName(), $command));
                            }
                            $this->code->remove($code);
                            $this->code->save();
                        } else {
                            $sender->sendMessage("GiftCode is not valid! Please try again.");
                        }
                        break;
                    default:
                        $sender->sendMessage("Invalid syntax! Please try again.");
                        return true;
                }
                break;
            case "mycode":
              $form = $this->form->createCustomForm(function(Player $player, $data){
              	if($data == null){
					
              	}
			  if(isset($data[0])){
                 $this->getServer()->getCommandMap()->dispatch($player, "giftcode get ".$data[0]);
			  }
              	});
                $form->setTitle("Giftcode system");
                $form->addInput("Please enter GiftCode");
                $t = $this->code->getAll();
                $mess = "";
                foreach (array_keys($this->code->getAll()) as $code) {
                    if ($t[$code]['Player'] === strtolower($sender->getName())) {
						$mess = "True";
						$form->addLabel("GiftCode you haven't used yet:§c ".$code." §7(§atype:§c ".$t[$code]['Type']."§7)");
                    }
                }
               if ($mess === "") {
                    $form->addLabel("You currently do not have a GiftCode to display");
                }
                $form->sendToPlayer($sender);
                break;
        }
        return true;
    }

    private function createcode()
    {
        $t = $this->code->getAll();
        $code = strtolower("gift" . substr(md5(uniqid(mt_rand(), true)), 0, 3));
        if (isset($t[$code])) {
            // code trùng tạo lại
            $this->createcode();
        }
        return $code;
    }
}