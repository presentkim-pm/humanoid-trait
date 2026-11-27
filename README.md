<!-- PROJECT BADGES -->
<div align="center">

[![Packagist][packagist-badge]][packagist-url]
[![CI][ci-badge]][ci-url]
[![Stars][stars-badge]][stars-url]
[![License][license-badge]][license-url]

</div>


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <h3>humanoid-trait</h3>
  <p align="center">
    Make a custom entity look like a player: skin, name tag and held items.

[Packagist][packagist-url] · [Changelog](CHANGELOG.md) · [Report a bug][issues-url] · [Request a feature][issues-url]

  </p>
</div>

```php
final class NpcEntity extends Entity{
    use HumanoidTrait;
}
```

-----

## 🌟 Why humanoid-trait?

A plain `Entity` cannot be sent to the client as a player. `HumanoidTrait` overrides the spawn, movement,
skin and equipment packets of `Entity` so that any entity class can be shown as a player model.

- **Skin** : `setSkin()` / `sendSkin()` with a `SkinData`
- **Equipment** : `setItemInHand()` / `setItemInOffHand()`, converted with each viewer's own type converter
- **Movement** : sent as `MovePlayerPacket` with the player eye offset
- **Tested** : on PHP 8.4 / 8.5 against the real `Entity` class

-----

## 📦 Installation

```sh
composer require presentkim/humanoid-trait
```

| Requirement | Version                                                           |
|-------------|-------------------------------------------------------------------|
| Server      | [axolotl-pm/PocketMine-MP][axolotl-url] (fork of PocketMine-MP 5) |
| PHP         | **8.4** or newer                                                  |

> The Poggit virion for the original PocketMine-MP API 4 is kept on the
> [`legacy/pmmp-5.44`][legacy-url] branch (no longer maintained).

-----

## 🚀 Quick start

The entity class must provide `getName()`, and set `$uuid` and `$skinData` before it is spawned.

```php
use kim\present\traits\humanoid\HumanoidTrait;
use pocketmine\entity\Entity;
use Ramsey\Uuid\Uuid;

final class NpcEntity extends Entity{
    use HumanoidTrait;

    public static function getNetworkTypeId() : string{
        return EntityIds::PLAYER;
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);
        $this->uuid = Uuid::uuid4();
        $this->skinData = $skinData; // a SkinData
    }

    public function getName() : string{
        return "NPC";
    }

    // getInitialSizeInfo(), getInitialDragMultiplier() and getInitialGravity() as for any Entity
}
```

-----

## 📖 API reference

| Method                                             | Description                                              |
|----------------------------------------------------|----------------------------------------------------------|
| `getSkinData() : SkinData`                         | The skin shown to viewers                                |
| `setSkin(SkinData $skinData) : void`               | Change the skin and send it to all viewers               |
| `sendSkin(?array $targets = null) : void`          | Send the skin to some players (default: all viewers)     |
| `getItemInHand() / setItemInHand(Item $item)`      | The main hand item                                       |
| `getItemInOffHand() / setItemInOffHand(Item $item)`| The off hand item                                        |
| `sendEquipment(Item $item, int $windowId, int $inventorySlot = 0, ?array $targets = null)` | Send an equipment packet |
| `getUniqueId() : UuidInterface`                    | The player list UUID of the entity                       |

-----

## 🧪 Development

```sh
composer install --ignore-platform-req='ext-*'
composer test
```

-----

## 📄 License

Distributed under the **MIT** License. See [LICENSE][license-url] for more information.


[packagist-badge]: https://img.shields.io/packagist/v/presentkim/humanoid-trait.svg?style=for-the-badge

[packagist-url]: https://packagist.org/packages/presentkim/humanoid-trait

[ci-badge]: https://img.shields.io/github/actions/workflow/status/presentkim-pm/humanoid-trait/ci.yml?branch=main&style=for-the-badge

[ci-url]: https://github.com/presentkim-pm/humanoid-trait/actions/workflows/ci.yml

[stars-badge]: https://img.shields.io/github/stars/presentkim-pm/humanoid-trait.svg?style=for-the-badge

[license-badge]: https://img.shields.io/github/license/presentkim-pm/humanoid-trait.svg?style=for-the-badge

[stars-url]: https://github.com/presentkim-pm/humanoid-trait/stargazers

[issues-url]: https://github.com/presentkim-pm/humanoid-trait/issues

[license-url]: https://github.com/presentkim-pm/humanoid-trait/blob/main/LICENSE

[legacy-url]: https://github.com/presentkim-pm/humanoid-trait/tree/legacy/pmmp-5.44

[axolotl-url]: https://github.com/axolotl-pm/PocketMine-MP
