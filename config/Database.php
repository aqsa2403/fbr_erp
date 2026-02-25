<?php
class Database
{
    private static $instance = null;
    private $conn;

    private $host = '108.181.166.175,53008';
    private $db_name = 'FBR_DI_ERP';
    private $username = 'fbr_app_user';
    private $password = 'Password!123';

    private function __construct()
    {
        try {
            $this->conn = new PDO("sqlsrv:Server=$this->host;Database=$this->db_name", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Connection Error: " . $e->getMessage() . "<br><b>Note:</b> Make sure the Microsoft ODBC Driver for PHP (pdo_sqlsrv) is installed and enabled in your WAMP php.ini.");
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
?>