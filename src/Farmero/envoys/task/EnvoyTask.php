<?php

declare(strict_types=1);

namespace Farmero\envoys\task;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

use Farmero\envoys\Envoys;

class EnvoyTask extends Task {

    public const TYPE_SPAWN = 0;
    public const TYPE_DESPAWN = 1;

    private $type;
    private $envoyConfig;
    private $remainingTicks;

    public function __construct(int $type, array $envoyConfig, int $remainingTicks) {
        $this->type = $type;
        $this->envoyConfig = $envoyConfig;
        $this->remainingTicks = $remainingTicks;
    }

    public function onRun() : void{
        $timeLeft = $this->remainingTicks / 20;
        $messages = $this->getMessages($timeLeft);

        foreach ($messages as $message) {
            Envoys::getInstance()->getServer()->broadcastMessage(TextFormat::YELLOW . $message);
        }

        if ($this->remainingTicks <= 0) {
            switch ($this->type) {
                case self::TYPE_SPAWN:
                    Envoys::getInstance()->getEnvoyManager()->spawnEnvoy($this->envoyConfig);
                    break;
                case self::TYPE_DESPAWN:
                    Envoys::getInstance()->getEnvoyManager()->despawnEnvoy($this->envoyConfig);
                    break;
            }
        } else {
            $this->remainingTicks -= 20;
            Envoys::getInstance()->getScheduler()->scheduleDelayedTask(new self($this->type, $this->envoyConfig, $this->remainingTicks), 20);
        }
    }

    private function getMessages(int $timeLeft) : array{
        $messages = [];
        switch ($timeLeft) {
            case 5 * 60 * 60:
                $messages[] = $this->getCountdownMessage(5, "hours");
                break;
            case 3 * 60 * 60:
                $messages[] = $this->getCountdownMessage(3, "hours");
                break;
            case 60 * 60:
                $messages[] = $this->getCountdownMessage(1, "hour");
                break;
            case 30 * 60:
                $messages[] = $this->getCountdownMessage(30, "minutes");
                break;
            case 15 * 60:
                $messages[] = $this->getCountdownMessage(15, "minutes");
                break;
            case 10 * 60:
                $messages[] = $this->getCountdownMessage(10, "minutes");
                break;
            case 5 * 60:
                $messages[] = $this->getCountdownMessage(5, "minutes");
                break;
            case 60:
                $messages[] = $this->getCountdownMessage(1, "minute");
                break;
            case 30:
                $messages[] = $this->getCountdownMessage(30, "seconds");
                break;
            case 15:
                $messages[] = $this->getCountdownMessage(15, "seconds");
                break;
            case 10:
                $messages[] = $this->getCountdownMessage(10, "seconds");
                break;
            case 5:
            case 4:
            case 3:
            case 2:
            case 1:
                $messages[] = $this->getCountdownMessage($timeLeft, "seconds");
                break;
            case 0:
                $messages[] = $this->getFinalMessage();
                break;
        }
        return $messages;
    }

    private function getCountdownMessage(int $time, string $unit) : string{
        switch ($this->type) {
            case self::TYPE_SPAWN:
                return "Envoys will spawn in {$time} {$unit}";
            case self::TYPE_DESPAWN:
                return "Envoys will despawn in {$time} {$unit}";
            default:
                return "";
        }
    }

    private function getFinalMessage() : string{
        switch ($this->type) {
            case self::TYPE_SPAWN:
                return "Envoys have spawned!";
            case self::TYPE_DESPAWN:
                return "Envoys have despawned!";
            default:
                return "";
        }
    }
}