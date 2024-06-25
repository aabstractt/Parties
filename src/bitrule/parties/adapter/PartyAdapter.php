<?php

declare(strict_types=1);

namespace bitrule\parties\adapter;

use bitrule\parties\event\PartyCreateEvent;
use bitrule\parties\event\PartyDisbandEvent;
use bitrule\parties\event\PartyTransferEvent;
use bitrule\parties\object\impl\MemberImpl;
use bitrule\parties\object\Member;
use bitrule\parties\object\Party;
use bitrule\parties\object\Role;
use bitrule\parties\PartiesPlugin;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

abstract class PartyAdapter {

    /**
     * All parties on this server
     *
     * @var array<string, Party>
     */
    protected array $parties = [];
    /**
     * The id of the party that the player is in
     * @var array<string, string>
     */
    protected array $playersParties = [];

    /**
     * @param string $id
     *
     * @return Party|null
     */
    public function getPartyById(string $id): ?Party {
        return $this->parties[$id] ?? null;
    }

    /**
     * @param Player $player
     *
     * @return Party|null
     */
    public function getPartyByPlayer(string $xuid): ?Party {
        $partyId = $this->playersParties[$xuid] ?? null;
        if ($partyId === null) return null;

        return $this->parties[$partyId] ?? null;
    }

    /**
     * Adapt the method to create a party.
     *
     * @param Player $source
     */
    abstract public function createParty(Player $source): void;

    /**
     * Cache the member and the party
     *
     * @param Player $source
     * @param Party  $party
     */
    protected function postCreate(Player $source, Party $party): void {
        $this->cacheMember($source->getXuid(), $party->getId());
        $this->cache($party);

        $source->sendMessage(PartiesPlugin::prefix() . TextFormat::GREEN . 'You have created a party');

        (new PartyCreateEvent($source, $party))->call();
    }

    /**
     * Add the party to the local cache
     *
     * @param Party $party
     */
    protected function cache(Party $party): void {
        $this->parties[$party->getId()] = $party;
    }

    /**
     * @param string $id
     */
    public function remove(string $id): void {
        unset($this->parties[$id]);
    }

    /**
     * @param string $xuid
     * @param string $partyId
     */
    protected function cacheMember(string $xuid, string $partyId): void {
        $this->playersParties[$xuid] = $partyId;
    }

    /**
     * Remove the party id from the player's cache
     *
     * @param string $xuid
     */
    public function clearMember(string $xuid): void {
        unset($this->playersParties[$xuid]);
    }

    /**
     * @param Player $source
     * @param string $playerName
     * @param Party  $party
     */
    abstract public function onPlayerInvite(Player $source, string $playerName, Party $party): void;

    /**
     * @param Player $source
     * @param string $playerName
     */
    abstract public function onPlayerAccept(Player $source, string $playerName): void;

    /**
     * @param Player $source
     * @param string $playerName
     * @param Party  $party
     */
    abstract public function onPlayerKick(Player $source, string $playerName, Party $party): void;

    /**
     * @param Player $source
     * @param Party  $party
     */
    abstract public function onPlayerLeave(Player $source, Party $party): void;

    /**
     * @param Player $source
     * @param string $playerName
     * @param Party  $party
     */
    abstract public function onPartyTransfer(Player $source, string $playerName, Party $party): void;

    /**
     * @param Player $source
     * @param Member $targetMember
     * @param Party  $party
     */
    protected function postTransferParty(Player $source, Member $targetMember, Party $party): void {
        $party->addMember(new MemberImpl(
            $targetMember->getXuid(),
            $targetMember->getName(),
            Role::OWNER
        ));
        $party->addMember(
            new MemberImpl(
                $source->getXuid(),
                $source->getName(),
                Role::MEMBER
            )
        );

        $party->broadcastMessage(PartiesPlugin::prefix() . TextFormat::YELLOW . $targetMember->getName() . ' is now the owner of the party');

        (new PartyTransferEvent($source, $party))->call();
    }

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     * @param Party  $party
     */
    abstract public function disbandParty(Player $source, Party $party): void;

    /**
     * @param Party  $party
     * @param Member $ownership
     */
    public function postDisbandParty(Party $party, Member $ownership): void {
        $disbandedMessage = PartiesPlugin::prefix() . TextFormat::YELLOW . $ownership->getName() . TextFormat::GOLD . ' has disbanded the party!';
        foreach ($party->getMembers() as $member) {
            $this->clearMember($member->getXuid());

            $player = Server::getInstance()->getPlayerExact($member->getName());
            if ($player === null || !$player->isOnline()) continue;

            $player->sendMessage($disbandedMessage);
        }

        $this->remove($party->getId());

        // TODO: Execute the event after the party is removed because that have some problems
        $source = Server::getInstance()->getPlayerExact($ownership->getName());
        if ($source === null || !$source->isOnline()) return;

        (new PartyDisbandEvent($source, $party))->call();
    }

    /**
     * @param Player $source
     */
    abstract public function onPlayerQuit(Player $source): void;
}