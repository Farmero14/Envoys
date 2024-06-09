<?php

declare(strict_types=1);

namespace Farmero\envoys\event;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\Position;
use pocketmine\block\tile\Chest;
use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\TextFormat;

use Farmero\envoys\Envoys;

class EnvoyListener implements Listener {

    public function onPlayerInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $world = $block->getPosition()->getWorld();
        $position = new Position($block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ(), $world);

        foreach (Envoys::getInstance()->getConfig()->get("envoys", []) as $envoyConfig) {
            if (Envoys::getInstance()->getEnvoyManager()->isEnvoyChest($position, $envoyConfig)) {
                $tile = $world->getTile($position);
                if ($tile instanceof Chest) {
                    $inventory = $tile->getInventory();
                    foreach ($inventory->getContents() as $item) {
                        $world->dropItem($position, $item);
                    }
                    $inventory->clearAll();
                    $world->setBlock($position, VanillaBlocks::AIR());
                    Envoys::getInstance()->getServer()->broadcastMessage(TextFormat::RED . "The envoy at {$position->getX()}, {$position->getY()}, {$position->getZ()} in world {$world->getFolderName()} has been claimed by {$player->getName()}.");
                    $event->cancel();
                }
            }
        }
    }
}