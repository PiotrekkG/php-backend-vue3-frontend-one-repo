<?php

/**
 * SimpleDb - prosty wrapper dla PDO do obsługi bazy danych
 */
class SimpleQuery
{
    private $connection;
    private $stmt;
    private $executed = false;
    private $error = null;
    private $paramsValues = [];
    // private $paramsNamed = [];

    public function __construct($connection, $sql)
    {
        $this->connection = $connection;
        $this->stmt = $this->connection->prepare($sql);
        // $this->stmt = $this->connection->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
    }

    /**
     * Executes the prepared statement with the bound parameters
     * @param array|null $params (optional) An array of parameters to bind and execute with the statement
     * @return SimpleQuery $this
     */
    public function execute($params = null)
    {
        // if (!empty($this->paramsNamed)) {
        //     $params = $this->paramsNamed;
        // } elseif (!empty($this->paramsValues)) {
        if (!empty($this->paramsValues)) {
            $params = $this->paramsValues;
        }
        if ($params === null) {
            $params = [];
        }
        $this->stmt->execute($params);
        $error = $this->stmt->errorInfo();
        if (count($error) > 0 && $error[0] !== '00000') {
            $this->error = $error;
        } else {
            $this->error = null;
        }
        $this->executed = true;
        return $this;
    }

    /**
     * Bind jednego lub więcej parametrów do zapytania (odpowiadające w kolejności dla ?)
     * @param mixed ...$value
     * @return $this
     */
    public function bind(...$value)
    {
        foreach ($value as $v) {
            if (is_array($v)) {
                foreach ($v as $av) {
                    $this->paramsValues[] = $av;
                }
                continue;
            }
            $this->paramsValues[] = $v;
        }
        return $this;
    }

    /**
     * Fetch all results as an associative array
     * @return array The fetched results
     */
    public function fetchAll()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single result as an associative array
     * @return array|null The fetched result or null if no result
     */
    public function fetch()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get the number of affected rows by the last executed statement
     * @return int The number of affected rows
     */
    public function rowCount()
    {
        if (!$this->executed) {
            $this->execute();
        }
        return $this->stmt->rowCount();
    }

    /**
     * Get the ID of the last inserted row
     * @return string The ID of the last inserted row
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Get the last error information from the executed statement
     * @return array|null The error information or null if no error
     */
    public function error()
    {
        return $this->error;
    }
}

/**
 * SimpleDb - prosty wrapper dla PDO do obsługi bazy danych
 */
class SimpleDb
{
    /** @var PDO|null The PDO connection instance */
    private static $connection;

    public function __construct()
    {
        // if (!self::$connection) {
        //     $this->autoConnect();

        //     if (!self::$connection) {
        //         throw new Exception("Database connection not initialized.");
        //     }
        // }
    }

    /**
     * Connects to the database using the provided parameters or defaults from configuration
     * @param string|null $host The database host (default: DB_HOST or 'localhost')
     * @param string|null $dbname The database name (default: DB_NAME)
     * @param string|null $user The database user (default: DB_USER)
     * @param string|null $pass The database password (default: DB_PASS)
     */
    public function connect($host = null, $dbname = null, $user = null, $pass = null)
    {
        if ($host === null) {
            $host = DB_HOST ?? 'localhost';
        }
        if ($dbname === null) {
            $dbname = DB_NAME ?? null;
        }
        if ($user === null) {
            $user = DB_USER ?? null;
        }
        if ($pass === null) {
            $pass = DB_PASS ?? null;
        }

        if (!self::$connection) {
            self::$connection = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        }
    }

    /**
     * Closes the database connection
     * @return void
     */
    public function disconnect()
    {
        self::$connection = null;
    }

    /**
     * Automatically connects to the database using default configuration parameters
     * @return void
     */
    public function autoConnect()
    {
        $this->connect(DB_HOST, DB_NAME, DB_USER, DB_PASS);
    }

    /**
     * Get the PDO connection instance
     * @return PDO The PDO connection instance
     * @throws Exception if the database connection is not initialized
     */
    public function connection()
    {
        if (!self::$connection) {
            throw new Exception("Database connection not initialized.");
        }
        return self::$connection;
    }

    /**
     * Executes a raw SQL query and returns a SimpleQuery instance for further handling
     * @param string $sql The SQL query to execute
     * @return SimpleQuery A SimpleQuery instance for the executed query
     */
    public function query($sql)
    {
        // $stmt = self::$connection->prepare($sql);
        // $stmt->execute($bindings);
        // return $stmt->fetchAll(PDO::FETCH_ASSOC);
        return new SimpleQuery($this->connection(), $sql);
    }

    // public function query($sql, $params = [])
    // {
    //     $query = new SimpleQuery($this->connection, $sql);
    //     return $query->execute($params)->fetchAll();
    // }

    /**
     * Update rekordów w tabeli
     * @param string $table
     * @param array $params (klucz => wartość)
     * @param string|null $where (warunek WHERE, np. "id = 1" lub "id = ?")
     * @param array|mixed $whereParams (jeśli w where są ?, to podaj wartości w tablicy lub pojedynczą wartość)
     * @param array|null $allowParamsKeys (jeśli podane, to tylko te klucze z $params będą użyte w zapytaniu)
     * @return int|false liczba zmienionych rekordów lub false
     */
    public function update($table, $params = [], $where = null, $whereParams = [], $allowParamsKeys = null)
    {
        $set = [];
        $valuesParams = [];
        foreach ($params as $key => $value) {
            if ($allowParamsKeys !== null && !in_array($key, $allowParamsKeys)) {
                continue;
            }
            $set[] = "$key = ?";
            $valuesParams[] = $value;
        }
        $sql = "UPDATE $table SET " . implode(", ", $set);

        if ($where === null) {
            return false;
        }
        if ($where && is_string($where)) {
            $sql .= " WHERE $where";
        }

        if (!is_array($whereParams)) {
            $whereParams = [$whereParams];
        }

        $query = $this->query($sql);
        return $query->bind($valuesParams)->bind($whereParams)->execute()->rowCount();
    }

    /**
     * Delete rekordów z tabeli
     * @param string $table
     * @param string|null $where (warunek WHERE, np. "id = 1" lub "id = ?")
     * @param array|mixed $whereParams (jeśli w where są ?, to podaj wartości w tablicy lub pojedynczą wartość)
     * @return int|false liczba usuniętych rekordów lub false
     */
    public function delete($table, $where = null, $whereParams = [])
    {
        $sql = "DELETE FROM $table";

        if ($where === null) {
            return false;
        }
        if ($where && is_string($where)) {
            $sql .= " WHERE $where";
        } else {
            return false;
        }

        if (!is_array($whereParams)) {
            $whereParams = [$whereParams];
        }

        $query = $this->query($sql);
        return $query->bind($whereParams)->execute()->rowCount();
    }

    public function lastInsertId()
    {
        return $this->connection()->lastInsertId();
    }
}

/**
 * Global helper function to get response instance
 * @return SimpleDb
 */
function db()
{
    return new SimpleDb();
}
