<?php

declare(strict_types=1);

namespace bitrule\parties\object;

use InvalidArgumentException;
use function strtoupper;

enum Role {
    case OWNER;
    case MEMBER;

    /**
     * @param string $name
     *
     * @return Role
     */
    public static function valueOf(string $name): Role {
        return match (strtoupper($name)) {
            'OWNER' => Role::OWNER,
            'MEMBER' => Role::MEMBER,
            default => throw new InvalidArgumentException("Invalid role name: $name")
        };
    }
}