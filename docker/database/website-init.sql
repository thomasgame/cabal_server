SET NOCOUNT ON;

IF DB_ID(N'Account') IS NULL
    THROW 51000, N'Account database is required before website initialization', 1;
GO

USE [Account];
GO

IF COL_LENGTH(N'dbo.cabal_auth_table', N'FirstName') IS NULL
    ALTER TABLE dbo.cabal_auth_table ADD FirstName nvarchar(50) NULL;
IF COL_LENGTH(N'dbo.cabal_auth_table', N'LastName') IS NULL
    ALTER TABLE dbo.cabal_auth_table ADD LastName nvarchar(50) NULL;
IF COL_LENGTH(N'dbo.cabal_auth_table', N'Birthday') IS NULL
    ALTER TABLE dbo.cabal_auth_table ADD Birthday date NULL;
IF COL_LENGTH(N'dbo.cabal_auth_table', N'Gender') IS NULL
    ALTER TABLE dbo.cabal_auth_table ADD Gender nvarchar(10) NULL;
IF COL_LENGTH(N'dbo.cabal_auth_table', N'Phone') IS NULL
    ALTER TABLE dbo.cabal_auth_table ADD Phone nvarchar(30) NULL;
GO

IF OBJECT_ID(N'dbo.InviteTable', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.InviteTable (
        UserNum int NOT NULL,
        InviteCode nvarchar(64) NOT NULL,
        ip_address nvarchar(45) NOT NULL,
        CreatedAt datetime2(0) NOT NULL
            CONSTRAINT DF_InviteTable_CreatedAt DEFAULT (SYSDATETIME()),
        CONSTRAINT PK_InviteTable PRIMARY KEY (UserNum),
        CONSTRAINT UQ_InviteTable_InviteCode UNIQUE (InviteCode)
    );
    CREATE INDEX IX_InviteTable_IpCreatedAt
        ON dbo.InviteTable (ip_address, CreatedAt DESC);
END;
GO

IF OBJECT_ID(N'dbo.InviteRedemptions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.InviteRedemptions (
        RedemptionId int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        InviteCode nvarchar(64) NOT NULL,
        InviterUserNum int NOT NULL,
        InvitedUserNum int NOT NULL,
        RedeemedAt datetime2(0) NOT NULL
            CONSTRAINT DF_InviteRedemptions_RedeemedAt DEFAULT (SYSDATETIME()),
        Status nvarchar(20) NOT NULL
            CONSTRAINT DF_InviteRedemptions_Status DEFAULT (N'Pending')
    );
    CREATE INDEX IX_InviteRedemptions_Inviter
        ON dbo.InviteRedemptions (InviterUserNum);
END;
GO

IF OBJECT_ID(N'dbo.VoteLogs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.VoteLogs (
        VoteLogId bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
        UserID nvarchar(64) NOT NULL,
        IPAddress nvarchar(45) NOT NULL,
        HWID nvarchar(128) NOT NULL
            CONSTRAINT DF_VoteLogs_HWID DEFAULT (N''),
        VoteSite nvarchar(32) NOT NULL,
        VoteTime datetime2(0) NOT NULL,
        NextVoteTime datetime2(0) NOT NULL
    );
    CREATE INDEX IX_VoteLogs_SiteTime
        ON dbo.VoteLogs (VoteSite, VoteTime DESC);
END;
GO

IF OBJECT_ID(N'dbo.RewardQueue', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.RewardQueue (
        QueueId bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
        UserID nvarchar(64) NOT NULL,
        UserNum int NOT NULL,
        RewardType nvarchar(64) NOT NULL,
        Amount int NOT NULL,
        AddedAt datetime2(0) NOT NULL
            CONSTRAINT DF_RewardQueue_AddedAt DEFAULT (SYSDATETIME())
    );
    CREATE INDEX IX_RewardQueue_User
        ON dbo.RewardQueue (UserNum, AddedAt DESC);
END;
GO

IF OBJECT_ID(N'dbo.InviteRankClaims', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.InviteRankClaims (
        UserNum int NOT NULL,
        RankName nvarchar(20) NOT NULL,
        ClaimedAt datetime2(0) NOT NULL
            CONSTRAINT DF_InviteRankClaims_ClaimedAt DEFAULT (SYSDATETIME()),
        CONSTRAINT PK_InviteRankClaims PRIMARY KEY (UserNum, RankName)
    );
END;
GO

IF OBJECT_ID(N'dbo.group_chat_messages', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.group_chat_messages (
        id bigint IDENTITY(1,1) NOT NULL PRIMARY KEY,
        username nvarchar(64) NOT NULL,
        message nvarchar(500) NOT NULL,
        reply_to nvarchar(64) NULL,
        [timestamp] datetime2(0) NOT NULL
            CONSTRAINT DF_GroupChat_Timestamp DEFAULT (SYSDATETIME())
    );
    CREATE INDEX IX_GroupChat_Timestamp
        ON dbo.group_chat_messages ([timestamp] DESC);
END;
GO

IF OBJECT_ID(N'dbo.downloads', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.downloads (
        id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        filename nvarchar(255) NOT NULL,
        filepath nvarchar(2048) NOT NULL,
        size bigint NOT NULL
            CONSTRAINT DF_Downloads_Size DEFAULT (0),
        uploaded_at datetime2(0) NOT NULL
            CONSTRAINT DF_Downloads_UploadedAt DEFAULT (SYSDATETIME())
    );
    CREATE INDEX IX_Downloads_UploadedAt
        ON dbo.downloads (uploaded_at DESC);
END;
GO

CREATE OR ALTER PROCEDURE dbo.GetUserInviteCode
    @usernum int
AS
BEGIN
    SET NOCOUNT ON;
    SELECT InviteCode
    FROM dbo.InviteTable
    WHERE UserNum = @usernum;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_GetInviteCountByUser
    @usernum int
AS
BEGIN
    SET NOCOUNT ON;
    SELECT InviteCount = COUNT(*)
    FROM dbo.InviteRedemptions
    WHERE InviterUserNum = @usernum;
END;
GO

CREATE OR ALTER PROCEDURE dbo.get_cabal_auth_table
    @UserNum int
AS
BEGIN
    SET NOCOUNT ON;
    SELECT UserNum, ID, Email, Phone, FirstName, LastName, Birthday, Gender
    FROM dbo.cabal_auth_table
    WHERE UserNum = @UserNum;
END;
GO

CREATE OR ALTER PROCEDURE dbo.update_cabal_auth_table
    @UserNum int,
    @Email varchar(200),
    @Phone nvarchar(30),
    @First_Name nvarchar(50),
    @Last_Name nvarchar(50),
    @Birthday date,
    @Gender nvarchar(10)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.cabal_auth_table
    SET Email = @Email,
        Phone = @Phone,
        FirstName = @First_Name,
        LastName = @Last_Name,
        Birthday = @Birthday,
        Gender = @Gender
    WHERE UserNum = @UserNum;
END;
GO

CREATE OR ALTER PROCEDURE dbo.usp_ClaimInviteRankReward
    @UserNum int,
    @CurrentRank nvarchar(20),
    @Reward nvarchar(100)
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE @RequiredInvites int;
    DECLARE @RewardAmount int;
    DECLARE @UserID nvarchar(64);

    SELECT
        @RequiredInvites = RequiredInvites,
        @RewardAmount = RewardAmount
    FROM (VALUES
        (N'Bronze', 10, 3000),
        (N'Silver', 25, 6050),
        (N'Gold', 50, 9000),
        (N'Platinum', 100, 10000)
    ) ranks(RankName, RequiredInvites, RewardAmount)
    WHERE RankName = @CurrentRank;

    IF @RequiredInvites IS NULL
        RETURN;

    IF (SELECT COUNT(*) FROM dbo.InviteRedemptions WHERE InviterUserNum = @UserNum) < @RequiredInvites
        RETURN;

    SELECT @UserID = ID
    FROM dbo.cabal_auth_table
    WHERE UserNum = @UserNum;

    IF @UserID IS NULL
        RETURN;

    BEGIN TRY
        BEGIN TRANSACTION;

        IF EXISTS (
            SELECT 1
            FROM dbo.InviteRankClaims WITH (UPDLOCK, HOLDLOCK)
            WHERE UserNum = @UserNum AND RankName = @CurrentRank
        )
        BEGIN
            COMMIT TRANSACTION;
            RETURN;
        END;

        UPDATE CabalCash.dbo.CashAccount
        SET Cash = Cash + @RewardAmount
        WHERE UserNum = @UserNum;

        IF @@ROWCOUNT = 0
            THROW 51002, N'Cash account not found', 1;

        INSERT dbo.InviteRankClaims (UserNum, RankName)
        VALUES (@UserNum, @CurrentRank);

        INSERT dbo.RewardQueue (UserID, UserNum, RewardType, Amount)
        VALUES (@UserID, @UserNum, N'InviteRank:' + @CurrentRank, @RewardAmount);

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        THROW;
    END CATCH;
END;
GO
