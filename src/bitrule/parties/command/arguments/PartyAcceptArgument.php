<?php

declare(strict_types=1);

namespace bitrule\parties\command\arguments;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\parties\PartiesPlugin;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class PartyAcceptArgument extends Argument {
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
            $sender->sendMessage(TextFormat::RED . 'Usage: /party accept <player>');

            return;
        }

        $party = $partyAdapter->getPartyByPlayer($sender->getXuid());
        if ($party !== null) {
            $sender->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You are already in a party');

            return;
        }

        $partyAdapter->onPlayerAccept($sender, $args[0]);
    }
}