<?php

declare(strict_types=1);

namespace kim\present\traits\humanoid\tests;

use kim\present\traits\humanoid\HumanoidTrait;
use PHPUnit\Framework\TestCase;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use Ramsey\Uuid\Uuid;
use function class_exists;

/** A bare entity that uses the trait. It is never constructed, only the pure parts of the trait are exercised. */
final class DummyHumanoid extends Entity{
    use HumanoidTrait;

    public static function getNetworkTypeId() : string{
        return "minecraft:player";
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function getInitialDragMultiplier() : float{
        return 0.02;
    }

    protected function getInitialGravity() : float{
        return 0.08;
    }

    public function __destruct(){
        // The entity is never constructed, so there is nothing to close
    }

    public function getName() : string{
        return "dummy";
    }
}

final class HumanoidTraitTest extends TestCase{
    private function create() : DummyHumanoid{
        /** @noinspection PhpUnhandledExceptionInspection */
        return (new \ReflectionClass(DummyHumanoid::class))->newInstanceWithoutConstructor();
    }

    public function testClassLoads() : void{
        self::assertTrue(class_exists(DummyHumanoid::class));
    }

    public function testOffsetPositionAddsBaseOffset() : void{
        $entity = $this->create();
        $result = $entity->getOffsetPosition(new Vector3(1, 64, -2));
        self::assertEquals(1.0, $result->x);
        self::assertEqualsWithDelta(64 + 1.62, $result->y, 1e-9);
        self::assertEquals(-2.0, $result->z);
    }

    public function testSpawnPositionUndoesOffset() : void{
        $entity = $this->create();
        $result = $entity->getSpawnPosition(new Vector3(1, 64, -2));
        self::assertEqualsWithDelta(64.0, $result->y, 1e-9);
    }

    public function testUniqueId() : void{
        $entity = $this->create();
        $uuid = Uuid::uuid4();
        (new \ReflectionProperty(DummyHumanoid::class, "uuid"))->setValue($entity, $uuid);
        self::assertSame($uuid, $entity->getUniqueId());
    }

    public function testItemsAreKept() : void{
        $entity = $this->create();
        $held = $this->createStub(Item::class);
        $offhand = $this->createStub(Item::class);
        (new \ReflectionProperty(DummyHumanoid::class, "heldItem"))->setValue($entity, $held);
        (new \ReflectionProperty(DummyHumanoid::class, "offhandItem"))->setValue($entity, $offhand);
        self::assertSame($held, $entity->getItemInHand());
        self::assertSame($offhand, $entity->getItemInOffHand());
    }
}
