# Cabal 进程控制与 cabalctl 扩展指南

本文提炼自源服务器的旧版 `sh` 管理体系，用于理解 `assets/game/bin` 的运行方式，并指导后续安全扩展 `bin/cabalctl`。旧脚本依赖 `/home/data_main`、`cabal_main/sql_main` 和宿主机 `/usr/bin` 包装命令，已不适用于当前 Compose 架构。

## 1. 核心运行模型

当前控制链路：

```text
bin/cabalctl
  └─ docker compose exec game game-control
       └─ supervisorctl start|stop|restart <program>
            └─ /usr/bin/<program> -c
                 └─ assets/game/bin 中的真实 ELF
                      ├─读取 runtime/game-config
                      └─加载 runtime-libs、runtime-hooks、hook-deps
```

脚本控制的是 Supervisor 进程，不直接修改 `bin`：

- `assets/game/bin`：不可变 ELF 二进制。
- `assets/game/config`：只读配置基线。
- `runtime/game-config`：实际运行配置，可人工修改。
- `assets/game/scripts/cabal_services`：Supervisor 程序定义。
- `assets/game/structure`：服务分组和启动顺序。
- `docker/game/entrypoint.sh`：首次复制配置、应用 `.env` 并启动 Supervisor。
- `docker/game/game-control.sh`：容器内进程控制。
- `bin/cabalctl`：宿主机统一入口。

## 2. 二进制与 Supervisor 程序映射

`assets/game/scripts/init.sh` 创建 `/usr/bin` 软链接，使同一 ELF 可以按不同角色启动。

| ELF | Supervisor 程序 |
| --- | --- |
| `DBAgent` | `DBAgent_01`、`ChatDBAgent_01`、`CashDBAgent_01`、`GlobalDBAgent`、`EventDBAgent`、`PCBangDBAgent` |
| `WorldSvr` | `WorldSvr_01_01` 至 `WorldSvr_01_20` |
| `LoginSvr` | `LoginSvr_01` |
| `ChatNode` | `ChatNode_01` |
| `PartySvr` | `PartySvr_01` |
| `AgentShop` | `AgentShop_01` |
| `EventMgrSvr` | `EventMgrSvr` |
| `GlobalMgrSvr` | `GlobalMgrSvr` |
| `RockAndRoll` | `RockAndRoll` |
| `StunSvr` | `StunSvr` |

同一个 `WorldSvr` ELF 根据软链接名称读取对应 INI，例如：

```text
/usr/bin/WorldSvr_01_05
  → /etc/cabal_bin/WorldSvr
  → /etc/cabal/WorldSvr_01_05.ini
```

不要复制二进制来创建新频道。正确做法是：

1. 添加 `WorldSvr_XX_YY.ini`。
2. 确认 `init.sh` 创建对应软链接。
3. 在 `cabal_services/*.conf` 添加 Supervisor program。
4. 将程序名加入适当结构列表。

## 3. 默认进程与顺序

`assets/game/structure/world_list` 定义默认启动的 19 个进程：

```text
DBAgent_01
GlobalDBAgent
RockAndRoll
ChatDBAgent_01
CashDBAgent_01
GlobalMgrSvr
EventDBAgent
PartySvr_01
PCBangDBAgent
ChatNode_01
StunSvr
EventMgrSvr
AgentShop_01
WorldSvr_01_01
WorldSvr_01_02
WorldSvr_01_03
WorldSvr_01_04
WorldSvr_01_05
LoginSvr_01
```

启动按正序执行，确保数据库代理和公共服务先于世界服、登录服启动。停止必须反序执行，避免 Login/World 仍接收请求时先关闭 DB Agent。

旧系统还定义了以下可选分组：

| 列表 | 进程 |
| --- | --- |
| `channels_list` | `WorldSvr_01_01` 至 `WorldSvr_01_05` |
| `tech_channel` | `WorldSvr_01_14` |
| `war_list` | `WorldSvr_01_15` 至 `WorldSvr_01_20` |

当前项目固定为 Server 01；Server 02、Server 03 和 GMS Proxy 的 Supervisor 定义、软链接及脚本已删除。

不在 `world_list` 中的 Supervisor program 不会默认启动，但仍可单独执行：

```bash
./bin/cabalctl game start WorldSvr_01_15
```

## 4. 配置生命周期

配置分为两层：

```text
assets/game/config       镜像构建时复制的基线
runtime/game-config      容器实际读写的持久化配置
```

首次启动时，`entrypoint.sh` 发现 `LoginSvr_01.ini` 不存在，才复制基线。之后只更新明确键：

- `IPAddress`、`AddrForClient`
- `client_version`
- `NormalClientMagicKey`
- `DBId`、`DBPwd`
- ODBC `Address`、`Port`

扩展 `cabalctl` 时不要直接修改 `assets/game/config` 后假设正在运行的容器会立即生效。运行时操作应修改 `.env` 或 `runtime/game-config`，然后选择：

- 支持 HUP 的进程：reload。
- 不支持 HUP 的进程：restart。
- 容器环境变量变化：Compose recreate。

## 5. 旧运维能力映射

| 旧能力 | 当前命令 | 状态 |
| --- | --- | --- |
| 全服启停 | `cabalctl game start|stop|restart` | 已实现 |
| 查看状态 | `cabalctl status` / `game status` | 已实现 |
| 单频道控制 | `cabalctl channel N ACTION` | 已实现 |
| 单进程控制 | `cabalctl game ACTION PROGRAM` | 已实现 |
| 修改世界 IP | `cabalctl world-ip ADDRESS` | 已实现 |
| 客户端版本/MagicKey | `cabalctl client VERSION KEY` | 已实现 |
| 全库备份 | `cabalctl db-backup` | 已实现，包含 `VERIFYONLY` |
| 人工恢复 | `cabalctl db-restore SNAPSHOT [--yes]` | 已实现 |
| `reload`/SIGHUP | 无 | 建议扩展 |
| channels/war/tech 分组 | 只能逐进程操作 | 建议扩展 |
| DB 在线标记清理 | 无 | 建议扩展 |
| checker/watchdog | 仅 Supervisor `autorestart` | 建议现代化实现 |
| anti-crash iptables | 无 | 应作为可选宿主机功能 |
| 定时备份/日志清理 | 无内置 cron | 建议由宿主 cron/systemd timer 调用 |
| 数据库 shrink/log check | 无 | 仅故障维护时提供 |

## 6. 扩展 cabalctl 的推荐结构

本节是尚未全部实现的设计草案。只有第 5 节标记为“已实现”的命令可直接使用；`group`、`db-reset-online`、`firewallctl` 等示例需先完成对应代码。

### 6.1 先在容器内实现原子操作

进程操作优先放入 `docker/game/game-control.sh`：

```bash
game-control start WorldSvr_01_05
game-control stop WorldSvr_01_05
game-control restart WorldSvr_01_05
game-control status WorldSvr_01_05
```

宿主机 `cabalctl` 只负责参数解析和 Compose 调用：

```bash
compose exec -T game game-control "$action" "$service"
```

这样不会把 Supervisor socket、容器路径和实现细节泄漏到宿主机。

### 6.2 添加 reload

旧脚本通过向二进制发送 `SIGHUP` 实现 reload。建议在 `game-control.sh` 增加：

```bash
reload)
  supervisorctl -c /etc/supervisord.conf pid "$target" >/dev/null
  pkill -HUP -f "/usr/bin/${target}"
  ;;
```

实现前必须逐类验证进程是否支持 HUP。不能确认时使用 restart。

### 6.3 添加服务分组

建议统一命令：

```text
cabalctl group channels start
cabalctl group war restart
cabalctl group tech status
```

实现规则：

1. 只允许已知列表名，禁止用户直接拼接文件路径。
2. start 正序，stop 反序。
3. 任一进程失败时返回非零。
4. 输出每个程序的结果。

示例映射：

```bash
case "$group" in
  channels) list=channels_list ;;
  war)      list=war_list ;;
  tech)     list=tech_channel ;;
  *) exit 2 ;;
esac
```

### 6.4 恢复 reset-online

旧系统在 DBAgent 启停前后清除异常在线状态：

- `GlobalDBAgent`：`Account.dbo.cabal_auth_table.Login = 0`
- `DBAgent_01`：`Server01.dbo.cabal_character_table.Login = 0`

建议提供显式命令，不要在每次普通 restart 时无条件更新全表：

```text
cabalctl db-reset-online account
cabalctl db-reset-online character
```

执行要求：

1. 先停止 LoginSvr/WorldSvr 或整个 game。
2. 使用固定 SQL 模板，不接受任意表名。
3. 显示受影响行数。
4. 要求 `--yes` 或交互确认。
5. 失败时不自动重新启动游戏。

### 6.5 checker/watchdog

旧 checker 每分钟使用 nmap 探测端口并重启失败进程。当前 Supervisor 已配置：

```text
autorestart=unexpected
```

推荐顺序：

1. 扩展容器 healthcheck，检查 `world_list` 全部进程。
2. 让 Supervisor 处理异常退出。
3. 仅对“进程仍存在但端口失效”的情况增加 watchdog。
4. 记录连续失败次数，避免无限重启。

不建议原样恢复旧 cron+nmap+标记文件方案。

### 6.6 anti-crash

旧实现向宿主机 `DOCKER-USER` iptables 链写入特征包过滤规则。它不是游戏容器内部功能。

如果恢复：

- 放到独立 `bin/firewallctl`，不要混入 `game-control.sh`。
- 默认只输出规则，使用 `--apply` 才修改防火墙。
- 必须幂等检查 `iptables -C`。
- WSL2、nftables 和 Docker Desktop 行为需要分别测试。

### 6.7 定时任务

不要把 cron 守护进程加入游戏容器。使用宿主机 cron 或 systemd timer：

```cron
0 4 * * * /path/server/bin/cabalctl db-backup
```

国战频道定时重启可以调用未来的：

```text
cabalctl group war restart
```

## 7. 编写命令的安全规范

每个新命令应满足：

- 使用 `set -Eeuo pipefail`。
- 参数使用白名单或严格正则验证。
- SQL 标识符不能直接接受未经验证的用户输入。
- 破坏性数据库操作必须二次确认。
- start/restart 前检查 database health。
- stop 使用反序。
- 把真实退出码返回给调用者。
- 支持重复执行，不把“already started/not running”视为致命错误。
- 不在命令行或日志中打印 SQL 密码。
- 不直接覆盖 `assets/game/config`。
- 不复制运行中的 MDF/LDF。
- 不依赖旧容器名 `cabal_main`、`sql_main`。
- 不依赖 `/home/data_main`。

## 8. 开发和验收流程

修改 `cabalctl` 或进程控制后至少执行：

```bash
bash -n bin/cabalctl
bash -n docker/game/game-control.sh
docker compose config --quiet
./bin/cabalctl doctor
docker compose build game
docker compose up -d
./bin/cabalctl status
```

进程能力验收：

1. 默认 19 个程序全部 RUNNING。
2. 单频道 stop/start 后其他频道不受影响。
3. group stop 反序执行。
4. 重复 start/stop 不导致容器退出。
5. game 停止时 database 保持运行。
6. 配置修改后只影响目标进程。
7. SQL 维护失败时不会自动继续启动 game。

二进制和依赖验收：

```bash
docker run --rm --entrypoint bash cabal/game-server:local -lc \
  'for f in /etc/cabal_bin/*; do ldd "$f" | awk "/not found/{failed=1} END{exit failed}"; done'
```

## 9. 不应删除的运行文件

扩展和清理时必须保留：

- `assets/game/bin`
- `assets/game/config`
- `assets/game/runtime-libs`
- `assets/game/runtime-hooks`
- `assets/game/hook-deps`
- `assets/game/scripts/init.sh`
- `assets/game/scripts/odbc`
- `assets/game/scripts/cabal_services`
- `assets/game/structure/world_list`
- `assets/database/backups`
- `docker/game`
- `docker/database`
- `compose.yaml`
- `.env.example`

旧宿主机脚本已提炼到本文，不再作为当前运行依赖。新增能力应按当前 Compose 架构重新实现，而不是复制旧脚本中的路径和容器名。
