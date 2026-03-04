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
        $dsn = "Driver={ODBC Driver 17 for SQL Server};Server=$this->host;Database=$this->db_name;";
        $this->conn = odbc_connect($dsn, $this->username, $this->password);

        if (!$this->conn) {
            die("Connection Error: " . odbc_errormsg() . "<br><b>Note:</b> Make sure the Microsoft ODBC Driver 17 for SQL Server is installed.");
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
        return new ODBCWrapper($this->conn);
    }
}

class ODBCWrapper
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function prepare($sql)
    {
        // Convert named placeholders to positional (?) for ODBC if needed
        // For now, assume positional placeholders as seen in the code (?) 
        // Logic for converting :name to ? could be added here if necessary.
        $sql = preg_replace('/:(\w+)/', '?', $sql);
        return new ODBCStatement($this->conn, $sql);
    }

    public function query($sql)
    {
        $res = odbc_exec($this->conn, $sql);
        if (!$res)
            return false;
        return new ODBCStatement($this->conn, $sql, $res);
    }

    public function beginTransaction()
    {
        odbc_autocommit($this->conn, FALSE);
    }

    public function commit()
    {
        odbc_commit($this->conn);
        odbc_autocommit($this->conn, TRUE);
    }

    public function rollBack()
    {
        odbc_rollback($this->conn);
        odbc_autocommit($this->conn, TRUE);
    }

    // PDO compatibility shim for fetchColumn on a statement result
}

class ODBCStatement
{
    private $conn;
    private $sql;
    private $result;
    private $params = [];

    public function __construct($conn, $sql, $result = null)
    {
        $this->conn = $conn;
        $this->sql = $sql;
        $this->result = $result;
    }

    public function execute($params = [])
    {
        $stmt = odbc_prepare($this->conn, $this->sql);
        if (!$stmt)
            return false;

        $this->result = odbc_execute($stmt, $params);
        if ($this->result) {
            $this->result = $stmt; // Store result resource
        }
        return $this->result !== false;
    }

    public function fetch($fetch_style = null)
    {
        if (!$this->result)
            return false;
        return odbc_fetch_array($this->result);
    }

    public function fetchAll($fetch_style = null)
    {
        if (!$this->result)
            return [];
        $rows = [];
        while ($row = odbc_fetch_array($this->result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchColumn($column_number = 0)
    {
        if (!$this->result)
            return false;
        if (odbc_fetch_row($this->result)) {
            return odbc_result($this->result, $column_number + 1);
        }
        return false;
    }
}
?>