<?php

declare(strict_types=1);

namespace Farmero\envoys;

use pocketmine\plugin\PluginBase;
use pocketmine\block\tile\Chest;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat;

use Farmero\envoys\event\EnvoyListener;
use Farmero\envoys\task\EnvoyTask;
use Farmero\envoys\EnvoyManager;

class Envoys extends PluginBase {

    private $envoyManager;
    public static $instance;

    public function onLoad() : void{
        self::$instance = $this;
    }

    public function onEnable() : void{
        $this->saveDefaultConfig();
        $this->saveResource("rewards.yml");
        $this->envoyManager = new EnvoyManager();
        $this->scheduleEnvoySpawns();
        $this->getServer()->getPluginManager()->registerEvents(new EnvoyListener(), $this);
    }

    public static function getInstance() : self{
        return self::$instance;
    }

    public function getEnvoyManager() : EnvoyManager{
        return $this->envoyManager;
    }

    public function getRewards() : Config{
        return new Config($this->getDataFolder() . "rewards.yml", Config::YAML);
    }

    public function scheduleEnvoySpawns() {
        $envoys = $this->getConfig()->get("envoys", []);
        foreach ($envoys as $envoyConfig) {
            $spawnTime = $this->parseTime($envoyConfig["time"]);
            $this->getScheduler()->scheduleDelayedTask(new EnvoyTask(EnvoyTask::TYPE_SPAWN, $envoyConfig, $spawnTime), $spawnTime);
        }
    }

    public function scheduleEnvoyDespawn(array $envoyConfig) {
        $despawnTime = $this->parseTime($envoyConfig["despawn_time"]);
        $this->getScheduler()->scheduleDelayedTask(new EnvoyTask(EnvoyTask::TYPE_DESPAWN, $envoyConfig, $despawnTime), $despawnTime);
    }

    public function parseTime(string $time) : int{
        $units = explode(" ", $time);
        $seconds = 0;

        foreach ($units as $unit) {
            $value = substr($unit, 0, -1);
            $type = substr($unit, -1);

            switch ($type) {
                case "s":
                    $seconds += (int)$value; break;
                case "m":
                    $seconds += (int)$value * 60; break;
                case "h":
                    $seconds += (int)$value * 3600; break;
                case "d":
                    $seconds += (int)$value * 86400; break;
            }
        }
        return $seconds * 20;
    }
}