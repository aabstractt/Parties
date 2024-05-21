<?php

declare(strict_types=1);

namespace bitrule\parties\adapter;

use bitrule\parties\object\Party;
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
     * @param Player $target
     * @param Party  $party
     */
    abstract public function onPlayerKick(Player $source, Player $target, Party $party): void;

    /**
     * @param Player $source
     * @param Party  $party
     */
    abstract public function onPlayerLeave(Player $source, Party $party): void;

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     * @param Party  $party
     */
    abstract public function disbandParty(Player $source, Party $party): void;

    /**
     * @param Party $party
     */
    public function postDisbandParty(Party $party): void {
        $this->remove($party->getId());

        $disbandedMessage = PartiesPlugin::prefix() . TextFormat::YELLOW . $party->getOwnership()->getName() . TextFormat::GOLD . ' has disbanded the party!';
        foreach ($party->getMembers() as $member) {
            $this->clearMember($member->getXuid());

            $player = Server::getInstance()->getPlayerExact($member->getName());
            if ($player === null || !$player->isOnline()) continue;

            $player->sendMessage($disbandedMessage);
        }
    }

    /**
     * @param Player $source
     */
    abstract public function onPlayerQuit(Player $source): void;
}