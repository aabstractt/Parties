<?php

declare(strict_types=1);

namespace bitrule\parties\adapter;

use bitrule\parties\object\Party;
use pocketmine\player\Player;

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
    public function getPartyByPlayer(Player $player): ?Party {
        $partyId = $this->playersParties[$player->getXuid()] ?? null;
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
    abstract public function processInvitePlayer(Player $source, string $playerName, Party $party): void;

    /**
     * @param Player $source
     * @param Player $target
     * @param Party  $party
     */
    abstract public function processKickPlayer(Player $source, Player $target, Party $party): void;

    /**
     * @param Player $source
     * @param Party  $party
     */
    abstract public function processLeavePlayer(Player $source, Party $party): void;

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     * @param Party  $party
     */
    abstract public function disbandParty(Player $source, Party $party): void;

    /**
     * @param Player $source
     */
    abstract public function onPlayerQuit(Player $source): void;
}