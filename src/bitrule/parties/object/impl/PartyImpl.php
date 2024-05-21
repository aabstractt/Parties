<?php

declare(strict_types=1);

namespace bitrule\parties\object\impl;

use bitrule\parties\object\Member;
use bitrule\parties\object\Party;
use bitrule\parties\object\Role;
use pocketmine\Server;
use RuntimeException;

final class PartyImpl implements Party {

    /**
     * @param string                $id
     * @param bool                  $open
     * @param array<string, Member> $members
     * @param string[]              $pendingInvites
     */
    public function __construct(
        private readonly string $id,
        private bool $open = false,
        private array $members = [],
        private array $pendingInvites = []
    ) {}

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * If the party is open, anyone can join without an invitation.
     * Else, only invited players can join.
     *
     * @return bool
     */
    public function isOpen(): bool {
        return $this->open;
    }

    /**
     * Change the party's open status.
     *
     * @param bool $open
     */
    public function setOpen(bool $open): void {
        $this->open = $open;
    }

    /**
     * Filter the members to find the owner of the party.
     *
     * @return Member
     */
    public function getOwnership(): Member {
        foreach ($this->members as $member) {
            if ($member->getRole() !== Role::OWNER) continue;

            return $member;
        }

        throw new RuntimeException('No owner found');
    }

    /**
     * @return array<string, Member>
     */
    public function getMembers(): array {
        return $this->members;
    }

    /**
     * @param Member $member
     */
    public function addMember(Member $member): void {
        $this->members[$member->getXuid()] = $member;
    }

    /**
     * @param string $xuid
     */
    public function removeMember(string $xuid): void {
        unset($this->members[$xuid]);
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function isMember(string $xuid): bool {
        return isset($this->members[$xuid]);
    }

    /**
     * @param string $xuid
     */
    public function addPendingInvite(string $xuid): void {
        $this->pendingInvites[] = $xuid;
    }

    /**
     * @param string $xuid
     */
    public function removePendingInvite(string $xuid): void {
        $key = array_search($xuid, $this->pendingInvites, true);
        if ($key === false) return;

        unset($this->pendingInvites[$key]);
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function hasPendingInvite(string $xuid): bool {
        return in_array($xuid, $this->pendingInvites, true);
    }

    /**
     * @param string $message
     */
    public function broadcastMessage(string $message): void {
        foreach ($this->members as $member) {
            // TODO: Change this to our own method to have better performance
            // because getPlayerExact iterates over all players
            $player = Server::getInstance()->getPlayerExact($member->getXuid());
            if ($player === null || !$player->isOnline()) continue;

            $player->sendMessage($message);
        }
    }
}