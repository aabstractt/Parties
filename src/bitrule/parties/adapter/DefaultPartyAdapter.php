<?php

declare(strict_types=1);

namespace bitrule\parties\adapter;

use bitrule\parties\object\impl\MemberImpl;
use bitrule\parties\object\impl\PartyImpl;
use bitrule\parties\object\Party;
use bitrule\parties\object\Role;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;

final class DefaultPartyAdapter extends PartyAdapter {

    /**
     * Adapt the method to create a party.
     *
     * @param Player $source
     */
    public function createParty(Player $source): void {
        if ($this->getPartyByPlayer($source->getXuid()) !== null) {
            $source->sendMessage(TextFormat::RED . 'You are already in a party');

            return;
        }

        $party = new PartyImpl(Uuid::uuid4()->toString());
        $party->addMember(new MemberImpl($source->getXuid(), $source->getName(), Role::OWNER));

        $this->cache($party);
        $this->cacheMember($source->getXuid(), $party->getId());

        $source->sendMessage(TextFormat::GREEN . 'You have created a party');
    }

    /**
     * @param Player $source
     * @param string $playerName
     * @param Party  $party
     */
    public function processInvitePlayer(Player $source, string $playerName, Party $party): void {
        $target = Server::getInstance()->getPlayerByPrefix($playerName);
        if ($target === null || !$target->isOnline()) {
            $source->sendMessage(TextFormat::RED . $playerName . ' not is online');

            return;
        }

        if ($party->isMember($target->getXuid())) {
            $source->sendMessage(TextFormat::RED . $target->getName() . ' is already in your party');

            return;
        }

        if ($this->getPartyByPlayer($target->getXuid()) !== null) {
            $source->sendMessage(TextFormat::RED . $target->getName() . ' is already in a party');

            return;
        }

        if ($party->hasPendingInvite($target->getXuid())) {
            $source->sendMessage(TextFormat::RED . 'You have already invited ' . $target->getName());

            return;
        }

        $party->addPendingInvite($target->getXuid());

        $source->sendMessage(TextFormat::GREEN . 'You have invited ' . $target->getName() . ' to your party');
        $target->sendMessage(TextFormat::GREEN . 'You have been invited to ' . $source->getName() . '\'s party');

        $party->broadcastMessage(TextFormat::YELLOW . $source->getName() . ' has invited ' . $target->getName() . ' to the party');
    }

    /**
     * @param Player $source
     * @param Player $target
     * @param Party  $party
     */
    public function processKickPlayer(Player $source, Player $target, Party $party): void {
        $party = $this->getPartyByPlayer($source->getXuid());
        if ($party === null) {
            $source->sendMessage(TextFormat::RED . 'You are not in a party');

            return;
        }

        if (!$party->isMember($target->getXuid())) {
            $source->sendMessage(TextFormat::RED . $target->getName() . ' is not in your party');

            return;
        }

        $party->removeMember($target->getXuid());
        $this->clearMember($target->getXuid());

        $party->broadcastMessage(TextFormat::YELLOW . $target->getName() . ' has been kicked from the party');
    }

    /**
     * @param Player $source
     * @param Party  $party
     */
    public function processLeavePlayer(Player $source, Party $party): void {
        $party->removeMember($source->getXuid());
        $this->clearMember($source->getXuid());

        $party->broadcastMessage(TextFormat::YELLOW . $source->getName() . ' has left the party');
    }

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     * @param Party  $party
     */
    public function disbandParty(Player $source, Party $party): void {
        foreach ($party->getMembers() as $member) {
            $this->clearMember($member->getXuid());

            // TODO: Change this to our own method to have better performance
            // because getPlayerExact iterates over all players
            $player = Server::getInstance()->getPlayerExact($member->getXuid());
            if ($player === null || !$player->isOnline()) continue;

            $player->sendMessage(TextFormat::RED . 'Your party has been disbanded');
        }

        $this->remove($party->getId());
    }

    /**
     * @param Player $source
     */
    public function onPlayerQuit(Player $source): void {
        $party = $this->getPartyByPlayer($source->getXuid());
        if ($party === null) return;

        $this->disbandParty($source, $party);
    }
}