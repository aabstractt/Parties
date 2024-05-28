<?php

declare(strict_types=1);

namespace bitrule\parties\event;

use bitrule\parties\object\Party;
use pocketmine\event\Event;
use pocketmine\player\Player;

abstract class PartyEvent extends Event {

    /**
     * @param Player $ownership
     * @param Party  $party
     */
    public function __construct(
        private readonly Player $ownership,
        private readonly Party  $party
    ) {}

    /**
     * @return Player
     */
    public function getOwnership(): Player {
        return $this->ownership;
    }

    /**
     * @return Party
     */
    public function getParty(): Party {
        return $this->party;
    }
}