<?php

/**
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\traits\humanoid;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\player\Player;
use Ramsey\Uuid\UuidInterface;
use function array_fill;

/** This trait override most methods in the {@link Entity} abstract class. */
trait HumanoidTrait{
    protected UuidInterface $uuid;
    protected SkinData $skinData;
    protected ?Item $heldItem = null;
    protected ?Item $offhandItem = null;

    protected function sendSpawnPacket(Player $player) : void{
        /** @var Entity $this */
        $this->getNetworkProperties()->setByte(EntityMetadataProperties::COLOR, 0);

        $session = $player->getNetworkSession();
        $session->sendDataPacket(PlayerListPacket::create([
            PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), $this->skinData)
        ]));
        $session->sendDataPacket(AddPlayerPacket::create(
            $this->uuid,
            $this->getName(),
            $this->id,
            "",
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->getHeadYaw(),
            ItemStackWrapper::legacy($session->getTypeConverter()->coreItemStackToNet($this->getItemInHand())),
            GameMode::SURVIVAL,
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            new AbilitiesData(CommandPermissions::NORMAL, PlayerPermissions::VISITOR, $this->id, [
                new AbilitiesLayer(
                    AbilitiesLayer::LAYER_BASE,
                    array_fill(0, AbilitiesLayer::NUMBER_OF_ABILITIES, false),
                    0.0,
                    0.0,
                    0.0
                )
            ]),
            [], // entity links
            null, // passenger block data
            "",
            DeviceOS::UNKNOWN
        ));
        $session->sendDataPacket(PlayerListPacket::create([PlayerListEntry::createRemovalEntry($this->uuid)]));

        $this->sendData([$player],
            [EntityMetadataProperties::NAMETAG => new StringMetadataProperty($this->getNameTag())]);
        if($this->heldItem !== null){
            $this->sendEquipment($this->heldItem, ContainerIds::INVENTORY, 0, [$player]);
        }
        if($this->offhandItem !== null){
            $this->sendEquipment($this->offhandItem, ContainerIds::OFFHAND, 0, [$player]);
        }
    }

    public function getHeadYaw() : float{
        /** @var Entity $this */
        return $this->location->yaw;
    }

    public function broadcastMovement(bool $teleport = false) : void{
        /** @var Entity $this */
        NetworkBroadcastUtils::broadcastPackets($this->hasSpawned, [MovePlayerPacket::simple(
            $this->id,
            $this->getOffsetPosition($this->location),
            $this->location->pitch,
            $this->location->yaw,
            $this->getHeadYaw(),
            $teleport ? MovePlayerPacket::MODE_TELEPORT : MovePlayerPacket::MODE_NORMAL,
            $this->onGround,
            0,
            0
        )]);
    }

    public function getBaseOffset() : float{
        return 1.62;
    }

    public function getUniqueId() : UuidInterface{
        return $this->uuid;
    }

    public function getOffsetPosition(Vector3 $vector3) : Vector3{
        return $vector3->add(0, $this->getBaseOffset(), 0);
    }

    public function getSpawnPosition(Vector3 $vector3) : Vector3{
        return $this->getOffsetPosition($vector3)->subtract(0, $this->getBaseOffset(), 0);
    }

    public function getSkinData() : SkinData{
        return $this->skinData;
    }

    public function setSkin(SkinData $skinData) : void{
        $this->skinData = $skinData;
        $this->sendSkin();
    }

    /** @param Player[]|null $targets */
    public function sendSkin(?array $targets = null) : void{
        /** @var Entity $this */
        NetworkBroadcastUtils::broadcastPackets($targets ?? $this->hasSpawned, [
            PlayerSkinPacket::create($this->uuid, "", "", $this->skinData)
        ]);
    }

    public function getItemInHand() : Item{
        return $this->heldItem ?? VanillaItems::AIR();
    }

    public function setItemInHand(Item $item) : void{
        $this->heldItem = $item;
        $this->sendEquipment($item, ContainerIds::INVENTORY);
    }

    public function getItemInOffHand() : Item{
        return $this->offhandItem ?? VanillaItems::AIR();
    }

    public function setItemInOffHand(Item $item) : void{
        $this->offhandItem = $item;
        $this->sendEquipment($item, ContainerIds::OFFHAND);
    }

    /** @param Player[]|null $targets */
    public function sendEquipment(Item $item, int $windowId, int $inventorySlot = 0, ?array $targets = null) : void{
        /** @var Entity $this */
        foreach($targets ?? $this->hasSpawned as $player){
            if(!$player->isConnected()){
                continue;
            }

            // The item is converted with each session's own type converter, as it can differ per protocol
            $session = $player->getNetworkSession();
            $session->sendDataPacket(MobEquipmentPacket::create(
                $this->id,
                ItemStackWrapper::legacy($session->getTypeConverter()->coreItemStackToNet($item)),
                $inventorySlot,
                0,
                $windowId
            ));
        }
    }
}
