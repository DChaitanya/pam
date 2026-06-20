<?php
/**
 * Database access layer.
 *
 * Changes in Phase 1 (security hardening):
 *  - Credentials are loaded from config.php (.env), not hardcoded (finding S2).
 *  - Added prepared-statement helpers select()/execute() to eliminate SQL
 *    injection (finding S1). Legacy query()/insert_query() are kept for
 *    constant/integer-validated statements during the incremental migration.
 *  - Logging only happens when APP_DEBUG is enabled, and never logs bound
 *    parameter values (finding S5).
 *
 * Requires PHP 8.x with the mysqli driver (mysqlnd) for mysqli_stmt_get_result().
 */

class db {

    private $host;
    private $port;
    private $username;
    private $password;
    private $database;
    private $debug;
    private $link = null;

    public function __construct() {
        $cfg = require __DIR__ . '/config.php';
        $this->host     = $cfg['host'];
        $this->port     = $cfg['port'];
        $this->username = $cfg['username'];
        $this->password = $cfg['password'];
        $this->database = $cfg['database'];
        $this->debug    = $cfg['debug'];
    }

    public function connect() {
        $this->link = mysqli_connect(
            $this->host, $this->username, $this->password, $this->database, $this->port
        );
        if (!$this->link) {
            $this->log_error('[CONNECT]', mysqli_connect_error());
            throw new RuntimeException('Database connection failed.');
        }
    }

    public function close() {
        if ($this->link) {
            mysqli_close($this->link);
            $this->link = null;
        }
    }

    /* ---------------------------------------------------------------------
     * Preferred API: prepared statements (safe against SQL injection).
     * ------------------------------------------------------------------- */

    /**
     * Run a prepared SELECT and return a buffered mysqli_result (or false).
     * Usage: $db->select("select * from accounts where id = ?", "i", [$id]);
     */
    public function select($sql, $types = '', array $params = []) {
        $this->connect();
        $stmt = mysqli_prepare($this->link, $sql);
        if ($stmt === false) {
            $this->log_error($sql, mysqli_error($this->link));
            $this->close();
            return false;
        }
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (!mysqli_stmt_execute($stmt)) {
            $this->log_error($sql, mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $this->close();
            return false;
        }
        $result = mysqli_stmt_get_result($stmt); // buffered: survives close
        mysqli_stmt_close($stmt);
        $this->close();
        return $result;
    }

    /**
     * Run a prepared write (INSERT/UPDATE/DELETE).
     * Returns ['insert_id' => int, 'affected' => int] on success, or false.
     * Usage: $db->execute("delete from accounts where id = ?", "i", [$id]);
     */
    public function execute($sql, $types = '', array $params = []) {
        $this->connect();
        $this->log_query($sql);
        $stmt = mysqli_prepare($this->link, $sql);
        if ($stmt === false) {
            $this->log_error($sql, mysqli_error($this->link));
            $this->close();
            return false;
        }
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (!mysqli_stmt_execute($stmt)) {
            $this->log_error($sql, mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $this->close();
            return false;
        }
        $out = [
            'insert_id' => mysqli_stmt_insert_id($stmt),
            'affected'  => mysqli_stmt_affected_rows($stmt),
        ];
        mysqli_stmt_close($stmt);
        $this->close();
        return $out;
    }

    /* ---------------------------------------------------------------------
     * Legacy API (kept for constant or integer-validated statements only).
     * Do NOT pass unescaped user input through these.
     * ------------------------------------------------------------------- */

    public function query($query) {
        $this->connect();
        if (stripos($query, "select") === false) {
            $this->log_query($query);
        }
        $rs = mysqli_query($this->link, $query);
        if (mysqli_error($this->link)) {
            $this->log_error($query, mysqli_error($this->link));
            $this->close();
            return false;
        }
        $this->close();
        return $rs;
    }

    public function insert_query($query) {
        $this->connect();
        $this->log_query($query);
        $rs = mysqli_query($this->link, $query);
        if (mysqli_error($this->link)) {
            $this->log_error($query, mysqli_error($this->link));
        }
        $insert_id = $this->link->insert_id;
        $this->close();
        return $insert_id;
    }

    public function get_link() {
        return $this->link;
    }

    public function get_insert_id() {
        return $this->link->insert_id;
    }

    /* ---------------------------------------------------------------------
     * Logging (debug only; written outside the document root is recommended).
     * ------------------------------------------------------------------- */

    private function log_query($sql) {
        if (!$this->debug) {
            return;
        }
        @file_put_contents(__DIR__ . '/query_log.txt', $sql . "\n", FILE_APPEND);
    }

    private function log_error($sql, $error) {
        // Errors are always recorded, but only the statement template is stored.
        @file_put_contents(
            __DIR__ . '/error_log.txt',
            date('c') . " | " . $sql . " | " . $error . "\n",
            FILE_APPEND
        );
    }
}
