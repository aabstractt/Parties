<?php

declare(strict_types=1);

namespace bitrule\parties\adapter;

use bitrule\parties\object\impl\MemberImpl;
use bitrule\parties\object\impl\PartyImpl;
use bitrule\parties\object\Party;
use bitrule\parties\object\Role;
use bitrule\parties\PartiesPlugin;
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
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You are already in a party');

            return;
        }

        $party = new PartyImpl(Uuid::uuid4()->toString());
        $party->addMember(new MemberImpl($source->getXuid(), $source->getName(), Role::OWNER));

        $this->postCreate($source, $party);
    }

    /**
     * @param Player $source
     * @param string $playerName
     * @param Party  $party
     */
    public function onPlayerInvite(Player $source, string $playerName, Party $party): void {
        $target = Server::getInstance()->getPlayerByPrefix($playerName);
        if ($target === null || !$target->isOnline()) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $playerName . ' not is online');

            return;
        }

        if ($party->isMember($target->getXuid())) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $target->getName() . ' is already in your party');

            return;
        }

        if ($this->getPartyByPlayer($target->getXuid()) !== null) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $target->getName() . ' is already in a party');

            return;
        }

        if ($party->hasPendingInvite($target->getXuid())) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You have already invited ' . $target->getName());

            return;
        }

        $party->addPendingInvite($target->getXuid());

        $source->sendMessage(PartiesPlugin::prefix() . TextFormat::GREEN . 'You have invited ' . $target->getName() . ' to your party');
        $target->sendMessage(PartiesPlugin::prefix() . TextFormat::GREEN . 'You have been invited to ' . $source->getName() . '\'s party');

        $party->broadcastMessage(PartiesPlugin::prefix() . TextFormat::YELLOW . $source->getName() . ' has invited ' . $target->getName() . ' to the party');
    }

    /**
     * @param Player $source
     * @param string $playerName
     */
    public function onPlayerAccept(Player $source, string $playerName): void {
        $target = Server::getInstance()->getPlayerByPrefix($playerName);
        if ($target === null || !$target->isOnline()) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $playerName . ' not is online');

            return;
        }

        // TODO: Change the signature of target to string $playerName
        $party = $this->getPartyByPlayer($target->getXuid());
        if ($party === null || !$party->hasPendingInvite($source->getXuid())) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $target->getName() . ' has not invited you to a party');

            return;
        }

        $party->removePendingInvite($source->getXuid());
        $party->addMember(new MemberImpl($source->getXuid(), $source->getName(), Role::MEMBER));

        $this->cacheMember($source->getXuid(), $party->getId());

        $party->broadcastMessage(PartiesPlugin::prefix() . TextFormat::YELLOW . $source->getName() . ' has joined the party');
        $source->sendMessage(PartiesPlugin::prefix() . TextFormat::GREEN . 'You have joined ' . $target->getName() . '\'s party');
    }

    /**
     * @param Player $source
     * @param string $playerName
     * @param Party  $party
     */
    public function onPlayerKick(Player $source, string $playerName, Party $party): void {
        $target = Server::getInstance()->getPlayerByPrefix($playerName);
        if ($target === null || !$target->isOnline()) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $playerName . ' not is online');

            return;
        }

        if (!$party->isMember($target->getXuid())) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $target->getName() . ' is not in your party');

            return;
        }

        $party->removeMember($target->getXuid());
        $this->clearMember($target->getXuid());

        $party->broadcastMessage(PartiesPlugin::prefix() . TextFormat::YELLOW . $target->getName() . ' has been kicked from the party');
    }

    /**
     * @param Player $source
     * @param Party  $party
     */
    public function onPlayerLeave(Player $source, Party $party): void {
        $party->removeMember($source->getXuid());
        $this->clearMember($source->getXuid());

        $party->broadcastMessage(PartiesPlugin::prefix() . TextFormat::YELLOW . $source->getName() . ' has left the party');
    }

    /**
     * @param Player $source
     * @param string $playerName
     * @param Party  $party
     */
    public function onPartyTransfer(Player $source, string $playerName, Party $party): void {
        $target = Server::getInstance()->getPlayerByPrefix($playerName);
        if ($target === null || !$target->isOnline()) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $playerName . ' not is online');

            return;
        }

        $member = $party->getMemberByXuid($source->getXuid());
        if ($member === null || $member->getRole() !== Role::OWNER) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You are not the owner of the party');

            return;
        }

        $targetMember = $party->getMemberByXuid($target->getXuid());
        if ($targetMember === null) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $target->getName() . ' is not in your party');

            return;
        }

        $this->postTransferParty($source, $targetMember, $party);
    }

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     * @param Party  $party
     */
    public function disbandParty(Player $source, Party $party): void {
        $this->postDisbandParty($party, $party->getOwnership());

        $source->sendMessage(PartiesPlugin::prefix() . TextFormat::GREEN . 'You have disbanded the party');
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