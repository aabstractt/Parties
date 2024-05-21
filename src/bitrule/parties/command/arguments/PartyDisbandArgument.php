<?php

declare(strict_types=1);

namespace bitrule\parties\command\arguments;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\parties\MainPlugin;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class PartyDisbandArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $partyAdapter = MainPlugin::getInstance()->getPartyAdapter();
        if ($partyAdapter === null) {
            $sender->sendMessage(TextFormat::RED . 'Parties are not enabled');

            return;
        }

        $party = $partyAdapter->getPartyByPlayer($sender);
        if ($party === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not in a party');

            return;
        }

        $ownership = $party->getOwnership();
        if ($ownership->getXuid() !== $sender->getXuid()) {
            $sender->sendMessage(TextFormat::RED . 'You are not the owner of the party');

            return;
        }

        $partyAdapter->disbandParty($sender, $party);
    }
}