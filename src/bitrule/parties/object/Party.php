<?php

declare(strict_types=1);

namespace bitrule\parties\object;

interface Party {

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * If the party is open, anyone can join without an invitation.
     * Else, only invited players can join.
     *
     * @return bool
     */
    public function isOpen(): bool;

    /**
     * Change the party's open status.
     *
     * @param bool $open
     */
    public function setOpen(bool $open): void;

    /**
     * Filter the members to find the owner of the party.
     *
     * @return Member
     */
    public function getOwnership(): Member;

    /**
     * @return array<string, Member>
     */
    public function getMembers(): array;

    /**
     * @param Member $member
     */
    public function addMember(Member $member): void;

    /**
     * @param string $xuid
     */
    public function removeMember(string $xuid): void;

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function isMember(string $xuid): bool;

    /**
     * @param string $xuid
     *
     * @return Member|null
     */
    public function getMemberByXuid(string $xuid): ?Member;

    /**
     * @param string $name
     *
     * @return Member|null
     */
    public function getMemberByName(string $name): ?Member;

    /**
     * @param string $xuid
     */
    public function addPendingInvite(string $xuid): void;

    /**
     * @param string $xuid
     */
    public function removePendingInvite(string $xuid): void;

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function hasPendingInvite(string $xuid): bool;

    /**
     * @param string $message
     */
    public function broadcastMessage(string $message): void;
}