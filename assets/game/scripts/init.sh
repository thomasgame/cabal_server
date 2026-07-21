#!/bin/bash
set -Eeuo pipefail

chmod 0700 /etc/cabal_bin/AgentShop
chmod 0700 /etc/cabal_bin/ChatNode
chmod 0700 /etc/cabal_bin/DBAgent
chmod 0700 /etc/cabal_bin/EventMgrSvr
chmod 0700 /etc/cabal_bin/GlobalMgrSvr
chmod 0700 /etc/cabal_bin/LoginSvr
chmod 0700 /etc/cabal_bin/PartySvr
chmod 0700 /etc/cabal_bin/RockAndRoll
chmod 0700 /etc/cabal_bin/StunSvr
chmod 0700 /etc/cabal_bin/WorldSvr

ln -sf /etc/cabal_bin/AgentShop /usr/bin/AgentShop_01
ln -sf /etc/cabal_bin/ChatNode /usr/bin/ChatNode_01
ln -sf /etc/cabal_bin/DBAgent /usr/bin/DBAgent_01
ln -sf /etc/cabal_bin/DBAgent /usr/bin/ChatDBAgent_01
ln -sf /etc/cabal_bin/DBAgent /usr/bin/CashDBAgent_01
ln -sf /etc/cabal_bin/DBAgent /usr/bin/GlobalDBAgent
ln -sf /etc/cabal_bin/DBAgent /usr/bin/EventDBAgent
ln -sf /etc/cabal_bin/DBAgent /usr/bin/PCBangDBAgent
ln -sf /etc/cabal_bin/EventMgrSvr /usr/bin/EventMgrSvr
ln -sf /etc/cabal_bin/GlobalMgrSvr /usr/bin/GlobalMgrSvr
ln -sf /etc/cabal_bin/LoginSvr /usr/bin/LoginSvr_01
ln -sf /etc/cabal_bin/PartySvr /usr/bin/PartySvr_01
ln -sf /etc/cabal_bin/StunSvr /usr/bin/StunSvr
ln -sf /etc/cabal_bin/RockAndRoll /usr/bin/RockAndRoll

ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_01
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_02
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_03
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_04
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_05
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_06
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_07
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_08
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_09
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_10
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_11
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_12
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_13
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_14
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_15
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_16
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_17
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_18
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_19
ln -sf /etc/cabal_bin/WorldSvr /usr/bin/WorldSvr_01_20
