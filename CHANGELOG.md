# Changelog

## 2.0.0 (2026-11-27)

The Poggit virion version for the original [pmmp/PocketMine-MP](https://github.com/pmmp/PocketMine-MP) is kept on the
[`legacy/pmmp-5.44`](https://github.com/presentkim-pm/humanoid-trait/tree/legacy/pmmp-5.44) branch.

### ⚠️ Breaking changes
- Targets [axolotl-pm/PocketMine-MP](https://github.com/axolotl-pm/PocketMine-MP) (protocol 64) and **PHP 8.4+**.
- Distributed with Composer: the Poggit virion files (`.poggit.yml`, `virion.yml`) are removed.
- `getUniqueId()` returns `UuidInterface` (never `null`).

### 🐛 Fixes
- `broadcastMovement()` sent the entity's Y coordinate as its yaw.
- `sendSpawnPacket()` crashed for an entity with no held item (`coreItemStackToNet(null)`).
- Equipment was converted with the global type converter, which no longer exists; each viewer's converter is used.

### 🔧 Changes
- Packets are built with the current `AddPlayerPacket`, `PlayerListPacket`, `PlayerSkinPacket` and
  `MovePlayerPacket` factories; broadcasting goes through `NetworkBroadcastUtils`.

### 🧪 Tests
- PHPUnit tests and GitHub Actions CI on PHP 8.4 / 8.5.
