<?php

/*
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
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\traits\humanoid;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\Player;
use pocketmine\utils\UUID;

/**
 * This trait override most methods in the {@link Entity} abstract class.
 */
trait HumanoidTrait{
    /** @var UUID */
    protected $uuid;

    /** @var SkinData */
    protected $skinData;

    /** @var Item */
    protected $heldItem = null;

    /** @var Item */
    protected $offhandItem = null;

    protected function sendSpawnPacket(Player $player) : void{
        $playerListAddPacket = new PlayerListPacket();
        $playerListAddPacket->type = PlayerListPacket::TYPE_ADD;
        $playerListAddPacket->entries = [PlayerListEntry::createAdditionEntry($this->uuid, $this->getId(), $this->getNameTag(), $this->skinData)];
        $this->server->broadcastPacket([$player], $playerListAddPacket);

        $addPlayerPacket = new AddPlayerPacket();
        $addPlayerPacket->uuid = $this->uuid;
        $addPlayerPacket->username = "";
        $addPlayerPacket->entityRuntimeId = $this->id;
        $addPlayerPacket->position = $this->getSpawnPosition($this->asVector3());
        $addPlayerPacket->pitch = $this->getPitch();
        $addPlayerPacket->yaw = $this->getYaw();
        $addPlayerPacket->headYaw = $this->getHeadYaw();
        $addPlayerPacket->item = $this->getItemInHand();
        $this->propertyManager->setByte(self::DATA_COLOR, 0);
        $addPlayerPacket->metadata = $this->propertyManager->getAll();
        $this->server->broadcastPacket([$player], $addPlayerPacket);

        $playerListRemovePacket = new PlayerListPacket();
        $playerListRemovePacket->type = PlayerListPacket::TYPE_REMOVE;
        $playerListRemovePacket->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
        $this->server->broadcastPacket([$player], $playerListRemovePacket);

        $this->sendEquipment($this->getItemInOffHand(), ContainerIds::OFFHAND);
    }

    public function getHeadYaw(){
        return $this->yaw;
    }

    public function broadcastMovement(bool $teleport = true) : void{
        $pk = new MovePlayerPacket();
        $pk->entityRuntimeId = $this->id;
        $pk->position = $this->getOffsetPosition($this->asVector3());
        $pk->pitch = $this->getPitch();
        $pk->yaw = $this->getY();
        $pk->headYaw = $this->getHeadYaw();
        $pk->mode = $teleport ? MovePlayerPacket::MODE_TELEPORT : MovePlayerPacket::MODE_NORMAL;
        $this->getLevel()->broadcastPacketToViewers($this->asVector3(), $pk);
    }

    public function getBaseOffset() : float{
        return 1.62;
    }

    public function getUniqueId() : ?UUID{
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
        $pk = new PlayerSkinPacket();
        $pk->uuid = $this->getUniqueId();
        $pk->skin = $this->skinData;
        $this->server->broadcastPacket($targets ?? $this->hasSpawned, $pk);
    }

    public function getItemInHand() : Item{
        return $this->heldItem ?? ItemFactory::get(0);
    }

    public function setItemInHand(Item $item) : void{
        $this->heldItem = $item;
        $this->sendEquipment($item, ContainerIds::INVENTORY);
    }

    public function getItemInOffHand() : Item{
        return $this->offhandItem ?? ItemFactory::get(0);
    }

    public function setItemInOffHand(Item $item) : void{
        $this->offhandItem = $item;
        $this->sendEquipment($item, ContainerIds::OFFHAND);
    }

    /** @param Player[]|null $targets */
    public function sendEquipment(Item $item, int $windowId, int $inventorySlot = 0, ?array $targets = null) : void{
        $pk = new MobEquipmentPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->item = $item;
        $pk->inventorySlot = $pk->hotbarSlot = $inventorySlot;
        $pk->windowId = $windowId;

        $this->server->broadcastPacket($targets ?? $this->hasSpawned, $pk);
    }
}