<?php

declare(strict_types=1);

namespace bitrule\parties\listener;

use bitrule\parties\PartiesPlugin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

final class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        PartiesPlugin::getInstance()->getPartyAdapter()?->onPlayerQuit($ev->getPlayer());
    }
}