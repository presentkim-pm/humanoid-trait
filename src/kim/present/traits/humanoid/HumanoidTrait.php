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
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use Ramsey\Uuid\UuidInterface;

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
        $session->sendDataPacket(PlayerListPacket::add([
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
            ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($this->heldItem)),
            GameMode::SURVIVAL,
            $this->getAllNetworkData(),
            new UpdateAbilitiesPacket(),
            [], // entity links
            "",
            DeviceOS::UNKNOWN
        ));
        $session->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($this->uuid)]));

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
        $pk = new MovePlayerPacket();
        $pk->actorRuntimeId = $this->id;
        $pk->position = $this->getOffsetPosition($this->location);
        $pk->pitch = $this->location->pitch;
        $pk->yaw = $this->location->y;
        $pk->headYaw = $this->getHeadYaw();
        $pk->mode = $teleport ? MovePlayerPacket::MODE_TELEPORT : MovePlayerPacket::MODE_NORMAL;
        $this->location->world->broadcastPacketToViewers($this->location, $pk);
    }

    public function getBaseOffset() : float{
        return 1.62;
    }

    public function getUniqueId() : ?UuidInterface{
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
        $pk = new PlayerSkinPacket();
        $pk->uuid = $this->uuid;
        $pk->skin = $this->skinData;
        $this->server->broadcastPackets($targets ?? $this->hasSpawned, [$pk]);
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
        $this->server->broadcastPackets($targets ?? $this->hasSpawned, [
            MobEquipmentPacket::create(
                $this->id,
                ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($item)),
                $inventorySlot,
                0,
                $windowId
            )
        ]);
    }
}
