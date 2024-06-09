<?php

declare(strict_types=1);

namespace Farmero\envoys;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\tile\Chest;
use pocketmine\utils\TextFormat;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

use Farmero\envoys\Envoys;
use Farmero\envoys\task\EnvoyTask;

class EnvoyManager {

    public function spawnEnvoy(array $envoyConfig) {
        $worldName = $envoyConfig["world"];
        $x = $envoyConfig["x"];
        $y = $envoyConfig["y"];
        $z = $envoyConfig["z"];

        $world = Envoys::getInstance()->getServer()->getWorldManager()->getWorldByName($worldName);

        if (!$world instanceof World) {
            Envoys::getInstance()->getLogger()->error("World '{$worldName}' not found.");
            return;
        }

        $position = new Position($x, $y, $z, $world);
        $chest = $world->getTile($position);

        if (!$chest instanceof Chest) {
            $world->setBlock($position, VanillaBlocks::CHEST());
            $chest = $world->getTile($position);
        }

        if ($chest instanceof Chest) {
            $this->fillChestWithLoot($chest);
            Envoys::getInstance()->getServer()->broadcastMessage(TextFormat::GREEN . "An envoy chest has been placed at {$x}, {$y}, {$z} in world {$worldName}.");

            $floatingTextPosition = new Vector3($x + 0.5, $y + 1.5, $z + 0.5);
            $floatingText = new FloatingTextParticle($floatingTextPosition, "", "Envoy Chest");
            $world->addParticle($floatingTextPosition, $floatingText);
        }

        Envoys::getInstance()->scheduleEnvoyDespawn($envoyConfig);
    }

    public function fillChestWithLoot(Chest $chest) {
        $rewards = Envoys::getInstance()->getRewards()->getAll();
        $minItems = $rewards["min_items"];
        $maxItems = $rewards["max_items"];

        $tiers = ["common", "uncommon", "rare", "epic", "legendary", "mythic"];
        $numItems = rand($minItems, $maxItems);

        for ($i = 0; $i < $numItems; $i++) {
            $tier = $tiers[array_rand($tiers)];
            $itemConfig = $rewards[$tier][array_rand($rewards[$tier])];

            $item = StringToItemParser::getInstance()->parse($itemConfig["item"]);
            if ($item !== null) {
                $item->setCount($itemConfig["count"]);
                if (isset($itemConfig["name"])) {
                    $item->setCustomName($itemConfig["name"]);
                }
                $chest->getInventory()->addItem($item);
            }
        }
    }

    public function despawnEnvoy(array $envoyConfig) {
        $worldName = $envoyConfig["world"];
        $x = $envoyConfig["x"];
        $y = $envoyConfig["y"];
        $z = $envoyConfig["z"];

        $world = Envoys::getInstance()->getServer()->getWorldManager()->getWorldByName($worldName);

        if ($world instanceof World) {
            $position = new Position($x, $y, $z, $world);
            $block = $world->getBlock($position);
            if ($block->getTypeId() === BlockTypeIds::CHEST) {
                $world->setBlock($position, VanillaBlocks::AIR());
                Envoys::getInstance()->getServer()->broadcastMessage(TextFormat::RED . "The envoy at {$x}, {$y}, {$z} in world {$worldName} has despawned.");
            }
        }
        $spawnTime = Envoys::getInstance()->parseTime($envoyConfig["time"]);
        Envoys::getInstance()->getScheduler()->scheduleDelayedTask(new EnvoyTask(EnvoyTask::TYPE_SPAWN, $envoyConfig, $spawnTime), $spawnTime);
    }

    public function isEnvoyChest(Position $position, array $envoyConfig) : bool{
        return $envoyConfig["world"] === $position->getWorld()->getFolderName() &&
               $envoyConfig["x"] == $position->getX() &&
               $envoyConfig["y"] == $position->getY() &&
               $envoyConfig["z"] == $position->getZ();
    }
}