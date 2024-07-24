<?php

declare(strict_types=1);

namespace bitrule\parties\command\arguments;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\parties\PartiesPlugin;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class PartyKickArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $partyAdapter = PartiesPlugin::getInstance()->getPartyAdapter();
        if ($partyAdapter === null) {
            $sender->sendMessage(TextFormat::RED . 'Parties are not enabled');

            return;
        }

        if (count($args) < 1) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /party kick <player>');

            return;
        }

        $party = $partyAdapter->getPartyByPlayer($sender->getXuid());
        if ($party === null) {
            $sender->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You are not in a party');

            return;
        }

        $ownership = $party->getOwnership();
        if ($ownership->getXuid() !== $sender->getXuid()) {
            $sender->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You are not the owner of the party');

            return;
        }

        $playerName = $args[0];
        $targetPlayer = $sender->getServer()->getPlayerByPrefix($playerName);
        if ($targetPlayer === null || !$targetPlayer->isOnline()) {
            $sender->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $playerName . ' is not online');

            return;
        }

        if (!$party->isMember($targetPlayer->getXuid())) {
            $sender->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $targetPlayer->getName() . ' is not in your party');

            return;
        }

        $partyAdapter->onPlayerKick($sender, $playerName, $party);
    }
}
