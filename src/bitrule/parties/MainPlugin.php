<?php

declare(strict_types=1);

namespace bitrule\parties;

use bitrule\parties\adapter\PartyAdapter;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

final class MainPlugin extends PluginBase {
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

    /**
     * This is an adapter for our party system.
     * @var PartyAdapter|null $partyAdapter
     */
    private ?PartyAdapter $partyAdapter = null;

    public function onLoad(): void {
        self::setInstance($this);
    }

    public function onEnable(): void {
        $defaultAdapter = $this->getConfig()->get('default-adapter');
        if (is_bool($defaultAdapter) && $defaultAdapter) {
            $this->partyAdapter = new adapter\DefaultPartyAdapter();

            $this->getLogger()->info(TextFormat::GOLD . 'Using default party adapter');
        }

        $this->getServer()->getCommandMap()->register('parties', new command\DefaultPartyCommand(
            'party',
            'Manage parties across the server',
            '/party [sub-command] [args]',
            ['p']
        ));
    }

    /**
     * @return PartyAdapter|null
     */
    public function getPartyAdapter(): ?PartyAdapter {
        return $this->partyAdapter;
    }

    /**
     * Set the party adapter.
     *
     * @param PartyAdapter $partyAdapter
     */
    public function setPartyAdapter(PartyAdapter $partyAdapter): void {
        if ($this->partyAdapter !== null) {
            throw new \RuntimeException('Party adapter is already set');
        }

        $this->partyAdapter = $partyAdapter;

        $this->getLogger()->info(TextFormat::GREEN . 'Party adapter set');
    }
}