# Cabal Docker Compose 服务端

本项目以 `192.168.153.200` 为游戏资产来源，包含重新构建的游戏与 SQL Server 镜像，以及运行所需的配置、数据、二进制和私有库。部署设计要求 `assets/database/backups` 中存在 11 个已校验数据库备份。支持 Docker Compose v2 的 WSL2 和标准 Linux。

## 快速部署

要求：

- x86-64 Linux 或启用 systemd 的 WSL2
- Docker Engine 28+ 与 Docker Compose v2
- 建议至少 8 GB 内存、20 GB 可用磁盘
- `assets/database/backups` 中已放入完整 `.bak` 文件
- 局域网部署前确认宿主机防火墙允许 SQL 与游戏端口

```bash
cd server
test -f .env || cp .env.example .env
# 编辑 .env，设置所有 CHANGE_ME 项
chmod +x bin/cabalctl
./bin/cabalctl doctor
./bin/cabalctl up
./bin/cabalctl status
```

本地已有 `.env` 时不要用模板覆盖；该文件可能包含实际凭据，已被 Git 和镜像构建上下文忽略。

首次启动会：

1. 构建固定 Ubuntu 24.04 与 SQL Server 2022 CU18 基础镜像。
2. 创建新的 SQL 数据卷。
3. 恢复 `assets/database/backups` 中的数据库；空目录且数据卷也为空时初始化会明确失败。
4. 重建 `adb01` 链接服务器。
5. 将只读基线配置复制到 `runtime/game-config`。
6. 幂等应用 IP、客户端版本、MagicKey 和 SQL 连接配置后启动 19 个 Cabal 进程。

数据库已存在时初始化器只检查并跳过恢复，不会覆盖现有数据。重复执行 `./bin/cabalctl up` 不会重建数据库或游戏主容器。

## 配置

所有部署参数都在 `.env`：

- `CABAL_PUBLIC_IP`：客户端能够访问的宿主机地址。
- `CLIENT_VERSION`、`NORMAL_CLIENT_MAGIC_KEY`：客户端兼容参数。
- `MSSQL_SA_PASSWORD`、`MSSQL_PORT`：SQL Server 凭据与宿主端口。
- `AUTO_START`：设为 `false` 时只启动 Supervisor，不自动启动游戏进程。
- `TZ`：两个容器的时区。

`MSSQL_SA_PASSWORD` 至少需要 8 个字符，并且应同时包含大写字母、小写字母、数字和符号。由于游戏服务自身的密码解析限制，密码不能包含 `@`、`/` 或 `\`。仅用于本地测试的可用示例：

```dotenv
MSSQL_SA_PASSWORD=CabalDb!2026X
```

生产环境不要直接使用该公开示例，应生成符合上述要求的独立强密码。全新部署时在首次执行 `up` 前设置密码；已有数据库卷还需要同步修改 SQL Server 内的 SA 密码，不能只修改 `.env`。

常用修改命令：

```bash
./bin/cabalctl world-ip 192.168.1.100
./bin/cabalctl client CLIENT_VERSION MAGIC_KEY
./bin/cabalctl channel 1 restart
./bin/cabalctl game status
```

运行时配置位于 `runtime/game-config`，基线位于 `assets/game/config`。基线使用 `{{CABAL_PUBLIC_IP}}`、`{{CLIENT_VERSION}}`、`{{NORMAL_CLIENT_MAGIC_KEY}}` 和 `{{MSSQL_SA_PASSWORD}}` 占位符，入口脚本会在服务启动前写入 `.env` 的实际值，并拒绝带有未渲染占位符的配置。游戏端口固定发布为 `35001-35100/tcp`。入口脚本只更新明确配置键，并使用原子替换；不会在每次启动时重置其他人工修改。

## 端口

- `1433/tcp`：SQL Server，可用 `MSSQL_PORT` 修改宿主端口。
- `35001-35100/tcp`：Cabal 服务范围。
- 默认实际服务包括 `35001`、`35003`、`35011-35015` 等，完整值由源配置决定。

WSL2 NAT 模式下，局域网客户端可能无法直接访问 WSL 地址。优先启用 WSL mirrored networking，或在 Windows 上配置端口转发与防火墙规则；`CABAL_PUBLIC_IP` 必须填写客户端实际访问的地址。

## 运维

```bash
./bin/cabalctl status
./bin/cabalctl logs 200 game
./bin/cabalctl game restart
./bin/cabalctl channel 5 stop
./bin/cabalctl db-backup
```

`db-backup` 对每个业务数据库执行 `COPY_ONLY`、`CHECKSUM` 和 `RESTORE VERIFYONLY`，输出到 `runtime/backups/时间戳`。

恢复人工备份：

```bash
./bin/cabalctl db-restore 20260720-160000
```

恢复会先要求确认并停止游戏服务，然后使用 `WITH REPLACE` 恢复该目录中的 `.bak`。非交互自动化必须显式追加 `--yes`。执行前应另做当前备份。

### 迁移到另一台机器

优先迁移 SQL Server 原生备份，不要复制运行中的 `cabal_sql-data` 卷或 MDF/LDF 文件。

在源机器创建并校验业务数据库备份：

```bash
./bin/cabalctl db-backup
```

将生成的 `runtime/backups/时间戳` 目录复制到目标机器项目的同一路径。目标机器应使用同一版本的项目文件，并基于 `.env.example` 创建新的 `.env`；按目标环境重新设置 SA 密码、公开 IP 和端口，不要直接照搬旧机器的 `.env`。

在目标机器初始化服务，等待初始数据库恢复完成，再用迁移快照覆盖业务数据库：

```bash
./bin/cabalctl up
docker compose wait database-init
./bin/cabalctl db-restore 20260721-100000
./bin/cabalctl status
```

新机器的 `MSSQL_SA_PASSWORD` 可以与源机器不同。若源机器对 `runtime/game-config` 有人工定制，可另行复制并在启动后检查 IP、客户端版本和数据库连接配置是否已按新 `.env` 更新。上述备份只包含业务数据库；自建 SQL 登录、Agent 作业等服务器级对象需要单独导出。

从项目自带的初始快照完全重建：

```bash
./bin/cabalctl down
docker volume rm cabal_sql-data
./bin/cabalctl up
```

删除 SQL 数据卷不可逆，只有在确认要回到项目快照时执行。上例使用默认项目名 `cabal`；修改 `COMPOSE_PROJECT_NAME` 后卷名相应变为 `{项目名}_sql-data`。

## 升级

资产职责：

- `assets/game/config`：权威基线配置和游戏数据。
- `assets/game/bin`：游戏 ELF 二进制。
- `assets/game/runtime-libs`、`runtime-hooks`、`hook-deps`：源镜像专用兼容库。
- `assets/game/scripts`、`structure`：Supervisor 和服务拓扑。

旧宿主机脚本的进程映射、启停顺序和现代化扩展方法已整理到 [`docs/cabalctl-development-guide.md`](docs/cabalctl-development-guide.md)。新增运维能力应基于当前 Compose、`game-control` 和 `cabalctl` 实现，不应重新引入旧容器名和 `/home/data_main` 路径。

直接在项目中升级游戏文件：

```bash
# 修改或替换 assets/game 下对应的 config、bin、scripts、structure
docker compose build game
docker compose up -d game
```

升级前建议复制一份 `assets/game` 作为回滚基线。不要把日志、core、PID、socket 或临时文件放进资产目录。数据库升级必须使用原生 `.bak`，不要复制运行中的 MDF/LDF。

如需让现有运行时配置重新采用升级后的基线，先停止服务并重命名 `runtime/game-config`，再执行 `./bin/cabalctl up`。保留旧目录用于人工合并自定义配置。自动配置备份位于 `runtime/game-config/.config-backups`，仅保留最近 10 份。

## 故障排查

```bash
docker compose ps
docker compose logs database database-init
docker compose logs game
docker compose exec game game-control status
docker compose exec game ldd /etc/cabal_bin/WorldSvr
```

- SQL 不健康：确认密码符合 SQL Server 复杂度要求，且没有其他服务占用 `MSSQL_PORT`。
- 游戏反复重启：检查数据库初始化是否成功、私有库是否完整，以及 `runtime/game-config` 是否被错误清空。
- 客户端无法连接：检查 `CABAL_PUBLIC_IP`、Windows/Linux 防火墙、NAT/端口转发和 `35001-35100`。
- 修改 `.env` 后执行 `docker compose up -d`，Compose 会按需要重建服务容器。

本项目不配置网络代理；如构建环境访问镜像仓库受限，应在 Docker 守护进程层临时配置代理。
