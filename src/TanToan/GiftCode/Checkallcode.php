<?php

namespace TanToan\GiftCode;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;

class Checkallcode extends Task
{

    private $code, $type, $plugin;

    public function __construct(Main $plugin)
    {
        $this->code = new Config(Server::getInstance()->getPluginManager()->getPlugin("Giftcode")->getDataFolder() . "code.yml", Config::YAML);
        $this->type = new Config(Server::getInstance()->getPluginManager()->getPlugin("Giftcode")->getDataFolder() . "type.yml", Config::YAML);
    }
    public function onRun($currentTick)
    {
        $t = $this->code->getAll();
        $ty = $this->type->getAll();
        foreach (array_keys($this->code->getAll()) as $code) {
            if (!isset($t[$code]) || (isset($t[$code]) && !isset($ty[$t[$code]["Type"]]))) {
                $this->code->remove($code);
                $this->code->save();
            }

        }
    }
}
