<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL;

use Doctrine\Common\EventManager,
    Doctrine\Common\DoctrineException,
    Doctrine\DBAL\DBALException;

/**
 * A wrapper around a Doctrine\DBAL\Driver\Connection that adds features like
 * events, transaction isolation levels, configuration, emulated transaction nesting,
 * lazy connecting and more.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author  Lukas Smith <smith@pooteeweet.org> (MDB2 library)
 */
class Connection
{
    /**
     * Constant for transaction isolation level READ UNCOMMITTED.
     */
    const TRANSACTION_READ_UNCOMMITTED = 1;
    
    /**
     * Constant for transaction isolation level READ COMMITTED.
     */
    const TRANSACTION_READ_COMMITTED = 2;
    
    /**
     * Constant for transaction isolation level REPEATABLE READ.
     */
    const TRANSACTION_REPEATABLE_READ = 3;
    
    /**
     * Constant for transaction isolation level SERIALIZABLE.
     */
    const TRANSACTION_SERIALIZABLE = 4;

    /**
     * Derived PDO constants
     */
    const FETCH_ASSOC       = 2;
    const FETCH_BOTH        = 4;
    const FETCH_COLUMN      = 7;
    const FETCH_NUM         = 3;
    const ATTR_AUTOCOMMIT   = 0;

    /**
     * The wrapped driver connection.
     *
     * @var Doctrine\DBAL\Driver\Connection
     */
    protected $_conn;

    /**
     * The Configuration.
     *
     * @var Doctrine\DBAL\Configuration
     */
    protected $_config;

    /**
     * The EventManager.
     *
     * @var Doctrine\Common\EventManager
     */
    protected $_eventManager;

    /**
     * Whether or not a connection has been established.
     *
     * @var boolean
     */
    private $_isConnected = false;

    /**
     * The transaction nesting level.
     *
     * @var integer
     */
    private $_transactionNestingLevel = 0;

    /**
     * The currently active transaction isolation level.
     *
     * @var integer
     */
    private $_transactionIsolationLevel;

    /**
     * The parameters used during creation of the Connection instance.
     *
     * @var array
     */
    private $_params = array();

    /**
     * The DatabasePlatform object that provides information about the
     * database platform used by the connection.
     *
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $_platform;

    /**
     * The schema manager.
     *
     * @var Doctrine\DBAL\Schema\SchemaManager
     */
    protected $_schemaManager;

    /**
     * The used DBAL driver.
     *
     * @var Doctrine\DBAL\Driver
     */
    protected $_driver;
    
    /**
     * Flag that indicates whether the current transaction is marked for rollback only.
     * 
     * @var boolean
     */
    private $_isRollbackOnly = false;

    /**
     * Initializes a new instance of the Connection class.
     *
     * @param array $params  The connection parameters.
     * @param Driver $driver
     * @param Configuration $config
     * @param EventManager $eventManager
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null,
            EventManager $eventManager = null)
    {
        $this->_driver = $driver;
        $this->_params = $params;

        if (isset($params['pdo'])) {
            $this->_conn = $params['pdo'];
            $this->_isConnected = true;
        }

        // Create default config and event manager if none given
        if ( ! $config) {
            $config = new Configuration();
        }
        
        if ( ! $eventManager) {
            $eventManager = new EventManager();
        }

        $this->_config = $config;
        $this->_eventManager = $eventManager;
        if ( ! isset($params['platform'])) {
            $this->_platform = $driver->getDatabasePlatform();
        } else if ($params['platform'] instanceof Platforms\AbstractPlatform) {
            $this->_platform = $params['platform'];
        } else {
            throw DBALException::invalidPlatformSpecified();
        }
        $this->_transactionIsolationLevel = $this->_platform->getDefaultTransactionIsolationLevel();
    }

    /**
     * Gets the parameters used during instantiation.
     *
     * @return array $params
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Gets the name of the database this Connection is connected to.
     *
     * @return string $database
     */
    public function getDatabase()
    {
        return $this->_driver->getDatabase($this);
    }
    
    /**
     * Gets the hostname of the currently connected database.
     * 
     * @return string
     */
    public function getHost()
    {
        return isset($this->_params['host']) ? $this->_params['host'] : null;
    }
    
    /**
     * Gets the port of the currently connected database.
     * 
     * @return mixed
     */
    public function getPort()
    {
        return isset($this->_params['port']) ? $this->_params['port'] : null;
    }
    
    /**
     * Gets the username used by this connection.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->_params['user']) ? $this->_params['user'] : null;
    }
    
    /**
     * Gets the password used by this connection.
     * 
     * @return string
     */
    public function getPassword()
    {
        return isset($this->_params['password']) ? $this->_params['password'] : null;
    }

    /**
     * Gets the DBAL driver instance.
     *
     * @return Doctrine\DBAL\Driver
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * Gets the Configuration used by the Connection.
     *
     * @return Doctrine\DBAL\Configuration
     */
    public function getConfiguration()
    {
        return $this->_config;
    }

    /**
     * Gets the EventManager used by the Connection.
     *
     * @return Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->_eventManager;
    }

    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return Doctrine\DBAL\Platforms\AbstractPlatform
     */
    public function getDatabasePlatform()
    {
        return $this->_platform;
    }

    /**
     * Establishes the connection with the database.
     *
     * @return boolean TRUE if the connection was successfully established, FALSE if
     *                 the connection is already open.
     */
    public function connect()
    {
        if ($this->_isConnected) return false;

        $driverOptions = isset($this->_params['driverOptions']) ?
                $this->_params['driverOptions'] : array();
        $user = isset($this->_params['user']) ? $this->_params['user'] : null;
        $password = isset($this->_params['password']) ?
                $this->_params['password'] : null;

        $this->_conn = $this->_driver->connect($this->_params, $user, $password, $driverOptions);
        $this->_isConnected = true;

        return true;
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     * 
     * @param string $statement The SQL query.
     * @param array $params The query parameters.
     * @return array
     * @todo Rename: fetchAssoc
     */
    public function fetchRow($statement, array $params = array())
    {
        return $this->execute($statement, $params)->fetch(Connection::FETCH_ASSOC);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchArray($statement, array $params = array())
    {
        return $this->execute($statement, $params)->fetch(Connection::FETCH_NUM);
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     * 
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @param int $colnum               0-indexed column number to retrieve
     * @return mixed
     */
    public function fetchColumn($statement, array $params = array(), $colnum = 0)
    {
        return $this->execute($statement, $params)->fetchColumn($colnum);
    }

    /**
     * Whether an actual connection to the database is established.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_isConnected;
    }

    /**
     * Deletes table row(s) matching the specified identifier.
     *
     * @param string $table         The table to delete data from.
     * @param array $identifier     An associateve array containing identifier fieldname-value pairs.
     * @return integer              The number of affected rows
     */
    public function delete($tableName, array $identifier)
    {
        $this->connect();
        
        $criteria = array();
        
        foreach (array_keys($identifier) as $id) {
            $criteria[] = $id . ' = ?';
        }

        $query = 'DELETE FROM ' . $tableName . ' WHERE ' . implode(' AND ', $criteria);

        return $this->executeUpdate($query, array_values($identifier));
    }

    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
        unset($this->_conn);
        
        $this->_isConnected = false;
    }

    /**
     * Sets the transaction isolation level.
     *
     * @param integer $level The level to set.
     */
    public function setTransactionIsolation($level)
    {
        $this->_transactionIsolationLevel = $level;
        
        return $this->executeUpdate($this->_platform->getSetTransactionIsolationSql($level));
    }

    /**
     * Gets the currently active transaction isolation level.
     *
     * @return integer The current transaction isolation level.
     */
    public function getTransactionIsolation()
    {
        return $this->_transactionIsolationLevel;
    }

    /**
     * Updates table row(s) with specified data
     *
     * @throws Doctrine\DBAL\ConnectionException    if something went wrong at the database level
     * @param string $table     The table to insert data into
     * @param array $values     An associateve array containing column-value pairs.
     * @return mixed            boolean false if empty value array was given,
     *                          otherwise returns the number of affected rows
     */
    public function update($tableName, array $data, array $identifier)
    {
        $this->connect();
        
        if (empty($data)) {
            return false;
        }

        $set = array();
        
        foreach ($data as $columnName => $value) {
            $set[] = $columnName . ' = ?';
        }

        $params = array_merge(array_values($data), array_values($identifier));

        $sql  = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $set)
                . ' WHERE ' . implode(' = ? AND ', array_keys($identifier))
                . ' = ?';

        return $this->executeUpdate($sql, $params);
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param string $table     The table to insert data into.
     * @param array $fields     An associateve array containing fieldname-value pairs.
     * @return mixed            boolean false if empty value array was given,
     *                          otherwise returns the number of affected rows
     */
    public function insert($tableName, array $data)
    {
        $this->connect();
        
        if (empty($data)) {
            return false;
        }

        // column names are specified as array keys
        $cols = array();
        $a = array();
        
        foreach ($data as $columnName => $value) {
            $cols[] = $columnName;
            $a[] = '?';
        }

        $query = 'INSERT INTO ' . $tableName
               . ' (' . implode(', ', $cols) . ')'
               . ' VALUES (' . implode(', ', $a) . ')';

        return $this->executeUpdate($query, array_values($data));
    }

    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     */
    public function setCharset($charset)
    {
        $this->executeUpdate($this->_platform->getSetCharsetSql($charset));
    }

    /**
     * Quote a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * NOTE: Just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str           identifier name to be quoted
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str)
    {
        return $this->_platform->quoteIdentifier($str);
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input  Parameter to be quoted.
     * @param string $type  Type of the parameter.
     * @return string  The quoted parameter.
     */
    public function quote($input, $type = null)
    {
        $this->connect();
        
        return $this->_conn->quote($input, $type);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql The SQL query.
     * @param array $params The query parameters.
     * @return array
     */
    public function fetchAll($sql, array $params = array())
    {
        return $this->execute($sql, $params)->fetchAll(Connection::FETCH_ASSOC);
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $statement The SQL statement to prepare.
     * @return Statement The prepared statement.
     */
    public function prepare($statement)
    {
        $this->connect();
        
        return $this->_conn->prepare($statement);
    }

    /**
     * Prepares and executes an SQL query.
     *
     * @param string $query The SQL query to prepare and execute.
     * @param array $params The parameters, if any.
     * @return Statement The prepared and executed statement.
     */
    public function execute($query, array $params = array())
    {
        $this->connect();

        if ($this->_config->getSqlLogger()) {
            $this->_config->getSqlLogger()->logSql($query, $params);
        }
        
        if ( ! empty($params)) {
            $stmt = $this->_conn->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->_conn->query($query);
        }
        
        return $stmt;
    }
    
    /**
     * Prepares and executes an SQL query and returns the result, optionally applying a
     * transformation on the rows of the result.
     *
     * @param string $query The SQL query to execute.
     * @param array $params The parameters, if any.
     * @param Closure $mapper The transformation function that is applied on each row.
     *                        The function receives a single paramater, an array, that
     *                        represents a row of the result set.
     * @return mixed The (possibly transformed) result of the query.
     */
    public function query($query, array $params = array(), \Closure $mapper = null)
    {
        $result = array();
        $stmt = $this->execute($query, $params);
        
        while ($row = $stmt->fetch()) {
            if ($mapper === null) {
                $result[] = $row;
            } else {
                $result[] = $mapper($row);
            }
        }
        
        $stmt->closeCursor();
        
        return $result;
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters.
     *
     * @param string $query     sql query
     * @param array $params     query parameters
     * @return integer
     */
    public function executeUpdate($query, array $params = array())
    {
        $this->connect();

        if ($this->_config->getSqlLogger()) {
            $this->_config->getSqlLogger()->logSql($query, $params);
        }

        if ( ! empty($params)) {
            $stmt = $this->_conn->prepare($query);
            $stmt->execute($params);
            $result = $stmt->rowCount();
        } else {
            $result = $this->_conn->exec($query);
        }
        
        return $result;
    }

    /**
     * Returns the current transaction nesting level.
     *
     * @return integer The nesting level. A value of 0 means theres no active transaction.
     */
    public function getTransactionNestingLevel()
    {
        return $this->_transactionNestingLevel;
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return integer
     */
    public function errorCode()
    {
        $this->connect();
        
        return $this->_conn->errorCode();
    }

    /**
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo()
    {
        $this->connect();
        
        return $this->_conn->errorInfo();
    }

    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of auto-increment fields or sequences.
     *
     * @param string $table     Name of the table into which a new row was inserted.
     * @param string $field     Name of the field into which a new row was inserted.
     */
    public function lastInsertId($seqName = null)
    {
        $this->connect();
        
        return $this->_conn->lastInsertId($seqName);
    }

    /**
     * Start a transaction by suspending auto-commit mode.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->connect();
        
        if ($this->_transactionNestingLevel == 0) {
            $this->_conn->beginTransaction();
        }
        
        ++$this->_transactionNestingLevel;
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     * @throws ConnectionException If the commit failed due to no active transaction or
     *                             because the transaction was marked for rollback only.
     */
    public function commit()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::commitFailedNoActiveTransaction();
        }
        if ($this->_isRollbackOnly) {
            throw ConnectionException::commitFailedRollbackOnly();
        }

        $this->connect();

        if ($this->_transactionNestingLevel == 1) {
            $this->_conn->commit();
        }
        
        --$this->_transactionNestingLevel;
    }

    /**
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @throws ConnectionException If the rollback operation failed.
     */
    public function rollback()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::rollbackFailedNoActiveTransaction();
        }

        $this->connect();

        if ($this->_transactionNestingLevel == 1) {
            $this->_transactionNestingLevel = 0;
            $this->_conn->rollback();
            $this->_isRollbackOnly = false;
        } else {
            $this->_isRollbackOnly = true;
            --$this->_transactionNestingLevel;
        }
    }

    /**
     * Gets the wrapped driver connection.
     *
     * @return Doctrine\DBAL\Driver\Connection
     */
    public function getWrappedConnection()
    {
        $this->connect();
        
        return $this->_conn;
    }

    /**
     * Gets the SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @return Doctrine\DBAL\Schema\SchemaManager
     */
    public function getSchemaManager()
    {
        if ( ! $this->_schemaManager) {
            $this->_schemaManager = $this->_driver->getSchemaManager($this);
        }
        
        return $this->_schemaManager;
    }
    
    /**
     * Marks the current transaction so that the only possible
     * outcome for the transaction to be rolled back.
     * 
     * @throws BadMethodCallException If no transaction is active.
     */
    public function setRollbackOnly()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::noActiveTransaction();
        }
        $this->_isRollbackOnly = true;
    }
    
    /**
     * Check whether the current transaction is marked for rollback only.
     * 
     * @return boolean
     * @throws BadMethodCallException If no transaction is active.
     */
    public function getRollbackOnly()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::noActiveTransaction();
        }
        return $this->_isRollbackOnly;
    }
}
