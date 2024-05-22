<?php

declare(strict_types=1);

namespace bitrule\parties\command;

use abstractplugin\command\BaseCommand;
use bitrule\parties\command\arguments\PartyAcceptArgument;
use bitrule\parties\command\arguments\PartyCreateArgument;
use bitrule\parties\command\arguments\PartyDisbandArgument;
use bitrule\parties\command\arguments\PartyInviteArgument;
use bitrule\parties\command\arguments\PartyLeaveArgument;

final class DefaultPartyCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('party', 'Manage your party across our network!', '/party <subcommand>', ['p']);

        $this->setPermission($this->getPermission());

        $this->registerParent(
            new PartyCreateArgument('create'),
            new PartyInviteArgument('invite'),
            new PartyLeaveArgument('leave'),
            new PartyAcceptArgument('accept'),
            new PartyDisbandArgument('disband')
        );
    }

    public function getPermission(): ?string {
        return 'parties.command.party';
    }
}