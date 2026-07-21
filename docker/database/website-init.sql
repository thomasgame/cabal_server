SET NOCOUNT ON;

IF DB_ID(N'Account') IS NULL
    THROW 51000, N'Account database is required before website initialization', 1;
GO

USE [Account];
GO

IF OBJECT_ID(N'dbo.DailyLoginRewards', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.DailyLoginRewards (
        DayNumber int NOT NULL PRIMARY KEY,
        ItemName nvarchar(100) NULL,
        ItemID int NULL,
        ItemOpt int NULL
            CONSTRAINT DF_DailyLoginRewards_ItemOpt DEFAULT (0),
        Duration int NULL
            CONSTRAINT DF_DailyLoginRewards_Duration DEFAULT (0)
    );
END;
GO

IF OBJECT_ID(N'dbo.DailyUserProgress', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.DailyUserProgress (
        UserNum int NOT NULL PRIMARY KEY,
        CurrentStreak int NULL
            CONSTRAINT DF_DailyUserProgress_CurrentStreak DEFAULT (0),
        LastClaimDate date NULL,
        TotalClaims int NULL
            CONSTRAINT DF_DailyUserProgress_TotalClaims DEFAULT (0),
        LastPlayTimeSnapshot int NULL
            CONSTRAINT DF_DailyUserProgress_PlayTime DEFAULT (0)
    );
END;
GO

IF OBJECT_ID(N'dbo.OnlineRewardLogs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.OnlineRewardLogs (
        ID int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        UserNum int NULL,
        CharacterName nvarchar(50) NULL,
        ItemName nvarchar(100) NULL,
        WonAt datetime NULL
            CONSTRAINT DF_OnlineRewardLogs_WonAt DEFAULT (GETDATE())
    );
END;
GO

IF OBJECT_ID(N'dbo.OnlineRandomRewards', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.OnlineRandomRewards (
        RewardID int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        ItemName nvarchar(100) NULL,
        ItemID int NULL,
        ItemOpt int NULL
            CONSTRAINT DF_OnlineRandomRewards_ItemOpt DEFAULT (0),
        Duration int NULL
            CONSTRAINT DF_OnlineRandomRewards_Duration DEFAULT (0),
        IsActive bit NULL
            CONSTRAINT DF_OnlineRandomRewards_IsActive DEFAULT (1)
    );
END;
GO

USE [Server01];
GO

CREATE OR ALTER PROCEDURE dbo.AddFgems
    @Fgems int,
    @UserNum int
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    UPDATE dbo.cabal_forcegem_table WITH (UPDLOCK, ROWLOCK)
    SET
        ForcegemHave = ForcegemHave + @Fgems,
        ForcegemEarn = ForcegemEarn + CASE WHEN @Fgems > 0 THEN @Fgems ELSE 0 END,
        ForcegemUsed = ForcegemUsed + CASE WHEN @Fgems < 0 THEN -@Fgems ELSE 0 END,
        Reserved1 = (ForcegemHave + @Fgems) ^ 2050497356
    WHERE UserNum = @UserNum
      AND ForcegemHave + @Fgems >= 0;

    IF @@ROWCOUNT = 0
        THROW 51001, N'Force Gem account was not found or has insufficient balance', 1;
END;
GO

USE [master];
GO

IF DB_ID(N'$(WebsiteDatabase)') IS NULL
BEGIN
    DECLARE @createDatabase nvarchar(max) =
        N'CREATE DATABASE ' + QUOTENAME(N'$(WebsiteDatabase)');
    EXEC sys.sp_executesql @createDatabase;
END;
GO

USE [$(WebsiteDatabase)];
GO

IF OBJECT_ID(N'dbo.PlinkoLogs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.PlinkoLogs (
        LogID int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        UserNum int NULL,
        AccountID varchar(50) NULL,
        BetAmount int NULL,
        TargetRisk varchar(20) NULL,
        ResultMultiplier float NULL,
        WinAmount int NULL,
        LogDate datetime NULL
            CONSTRAINT DF_PlinkoLogs_LogDate DEFAULT (GETDATE())
    );
END;
GO

IF OBJECT_ID(N'dbo.PlinkoSettings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.PlinkoSettings (
        RiskType varchar(20) NOT NULL PRIMARY KEY,
        RTP_Percentage int NULL
            CONSTRAINT DF_PlinkoSettings_RTP DEFAULT (95),
        HouseBias float NULL
            CONSTRAINT DF_PlinkoSettings_HouseBias DEFAULT (0.05),
        IsEnabled tinyint NULL
            CONSTRAINT DF_PlinkoSettings_IsEnabled DEFAULT (1)
    );
END;
GO

MERGE dbo.PlinkoSettings AS target
USING (VALUES
    ('x8', 95, 0.05, 1),
    ('x12', 95, 0.05, 1),
    ('x100', 95, 0.05, 1)
) AS source (RiskType, RTP_Percentage, HouseBias, IsEnabled)
ON target.RiskType = source.RiskType
WHEN NOT MATCHED THEN
    INSERT (RiskType, RTP_Percentage, HouseBias, IsEnabled)
    VALUES (
        source.RiskType,
        source.RTP_Percentage,
        source.HouseBias,
        source.IsEnabled
    );
GO

IF OBJECT_ID(N'dbo.LimboLogs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.LimboLogs (
        LogID int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        UserNum int NULL,
        AccountID varchar(50) NULL,
        Currency varchar(20) NULL,
        BetAmount int NULL,
        TargetMultiplier float NULL,
        ResultMultiplier float NULL,
        WinAmount int NULL,
        Status varchar(10) NULL,
        LogDate datetime NULL
            CONSTRAINT DF_LimboLogs_LogDate DEFAULT (GETDATE())
    );
END;
GO

IF OBJECT_ID(N'dbo.LimboSettings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.LimboSettings (
        SettingID int NOT NULL PRIMARY KEY,
        HouseEdge float NULL
            CONSTRAINT DF_LimboSettings_HouseEdge DEFAULT (0.20),
        IsEnabled tinyint NULL
            CONSTRAINT DF_LimboSettings_IsEnabled DEFAULT (1)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.LimboSettings WHERE SettingID = 1)
BEGIN
    INSERT dbo.LimboSettings (SettingID, HouseEdge, IsEnabled)
    VALUES (1, 0.20, 1);
END;
GO
