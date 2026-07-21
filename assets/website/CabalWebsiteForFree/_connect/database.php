<?php
class Database {
    private $server;
    private $port;
    private $user;
    private $password;
    private $dbname;
    private $adminUsernames;

    // Site Maintenance Constants
    const WEB_MAINTENANCE = 0; 
    const WEB_SCHED = 'February 25, 2025 15:30:00';
	
	// Service Cost Constants
    const DELETION_COST = 7000;
    const NATION_CHANGE_COST = 500;
	
	// --- UPDATED CONVERSION RATES ---
    const PLAYTIME_TO_ECOIN_RATE = 0.08333333; // (50 eCoin / 600 Mins (Total ecoin /devide to Min))
    const ECOIN_TO_FG_RATE = 10;               // 10 eCoin = 1 FG
    const FG_TO_ECOIN_RATE = 10;               // 1 FG = 10 eCoin

    public function __construct() {
        $this->server = getenv('MSSQL_HOST') ?: 'database';
        $this->port = (int) (getenv('MSSQL_PORT') ?: 1433);
        $this->user = getenv('MSSQL_USER') ?: 'sa';
        $this->password = getenv('MSSQL_PASSWORD') ?: '';
        $this->dbname = getenv('MSSQL_DATABASE') ?: 'Account';

        $adminUsernames = getenv('WEBSITE_ADMIN_USERNAMES') ?: '';
        $this->adminUsernames = array_values(array_filter(array_map(
            'trim',
            explode(',', $adminUsernames)
        )));
    }

    public function getConnection() {
        try {
            $dsn = "sqlsrv:Server={$this->server},{$this->port};Database={$this->dbname};Encrypt=yes;TrustServerCertificate=yes";
            return new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            exit('Database connection error.');
        }
    }
    // --- ADMIN & STATS METHODS ---
    /**
     * Gets the total number of registered accounts
     */
    public function getTotalUsers() {
        $conn = $this->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) FROM Account.dbo.cabal_auth_table");
        return $stmt->fetchColumn();
    }
	


    /**
     * Gets the count of users currently flagged as logged in
     */
    public function getOnlineCount() {
        $conn = $this->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) FROM Account.dbo.cabal_auth_table WHERE Login = 1");
        return $stmt->fetchColumn();
    }

    /**
     * Fetches a list of users for the management table
     */
    public function getAllUsers($limit = 10) {
        $conn = $this->getConnection();
        // SQL Server TOP clause requires an explicit integer when using prepared statements
        $stmt = $conn->prepare("SELECT TOP (:limit) ID, Email, Login FROM Account.dbo.cabal_auth_table ORDER BY UserNum DESC");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // --- USER DATA METHODS ---

    public function getUserNum($username) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT UserNum FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn();
    }

    public function getJoinDate($username) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT createDate FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn();
    }

    public function getCash($userNum) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT Cash FROM CabalCash.dbo.CashAccount WHERE UserNum = ?");
        $stmt->execute([$userNum]);
        return $stmt->fetchColumn();
    }

    public function getForceGem($userNum) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT ForcegemHave FROM Server01.dbo.cabal_forcegem_table WHERE UserNum = ?");
        $stmt->execute([$userNum]);
        return $stmt->fetchColumn();
    }

    public function getStatus($username) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT AuthType FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn();
    }

    public function getCharacterCount($userNum) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Server01.dbo.cabal_character_table WHERE CharacterIdx/16 = ?");
        $stmt->execute([$userNum]);
        return $stmt->fetchColumn();
    }

    public function getEmail($username) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT Email FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn();
    } 

    public function getPlaytime($username) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT Playtime FROM Account.dbo.cabal_auth_table WHERE ID = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn();
    }
	public function isAdmin($username) {
        return in_array($username, $this->adminUsernames, true);
    }
}