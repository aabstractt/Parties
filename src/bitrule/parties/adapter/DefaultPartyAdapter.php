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

        $this->cacheMember($source->getXuid(), $party->getId());
        $this->cache($party);

        $source->sendMessage(PartiesPlugin::prefix() . TextFormat::GREEN . 'You have created a party');
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
        // TODO: Implement onPlayerAccept() method.
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

        // TODO: Change the signature of target to string $playerName
        $party = $this->getPartyByPlayer($source->getXuid());
        if ($party === null) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . 'You are not in a party');

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

        if ($party->getMemberByXuid($target->getXuid()) === null) {
            $source->sendMessage(PartiesPlugin::prefix() . TextFormat::RED . $target->getName() . ' is not in your party');

            return;
        }

        $party->addMember(new MemberImpl(
            $target->getXuid(),
            $target->getName(),
            Role::OWNER
        ));
        $party->addMember(
            new MemberImpl(
                $source->getXuid(),
                $source->getName(),
                Role::MEMBER
            )
        );

        $party->broadcastMessage(PartiesPlugin::prefix() . TextFormat::YELLOW . $target->getName() . ' is now the owner of the party');
    }

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     * @param Party  $party
     */
    public function disbandParty(Player $source, Party $party): void {
        $this->postDisbandParty($party);

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