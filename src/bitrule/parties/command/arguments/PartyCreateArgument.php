<?php

declare(strict_types=1);

namespace bitrule\parties\command\arguments;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\parties\PartiesPlugin;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class PartyCreateArgument extends Argument {
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

        if ($partyAdapter->getPartyByPlayer($sender->getXuid()) !== null) {
            $sender->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You are already in a party');

            return;
        }

        $partyAdapter->createParty($sender);
    }
}