<?php
// implements native `SessionHandlerInterface`(open | close | read | write | destroy | gc)
// Author: Mukul kumar(https://github.com/slimdestro/)

namespace YourNamespace;

use Exception;
use PDO;
use PDOException;
use SessionHandlerInterface;

class DualSessionLogger implements SessionHandlerInterface
{
    private $db;
    private $dual_sessions_table;

    public function __construct(PDO $db = null, $dual_sessions_table = null)
    {
        if (!$db) {
            throw new Exception($this->dualSessionLoggerError("fatal", "DB instance missed in constructor()"));
        } else {
            $this->db = $db;
            $this->dual_sessions_table = $dual_sessions_table;
            $this->createTableIfNotExists();
        }
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($sessionId)
    {
        try {
            $stmt = $this->db->prepare("SELECT data FROM {$this->dual_sessions_table} WHERE id = :id");
            $stmt->bindParam(":id", $sessionId);
            $stmt->execute();

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['data'];
            }

            return "";
        } catch (PDOException $e) {
            throw new Exception($this->dualSessionLoggerError("warning", $e->getMessage()));
            return false;
        }
    }

    public function write($sessionId, $data)
    {
        try {
            $timestamp = time();
            $stmt = $this->db->prepare("REPLACE INTO {$this->dual_sessions_table}(id, data, timestamp) VALUES (:id, :data, :timestamp)");
            $stmt->bindParam(":id", $sessionId);
            $stmt->bindParam(":data", $data);
            $stmt->bindParam(":timestamp", $timestamp);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception($this->dualSessionLoggerError("warning", $e->getMessage()));
            return false;
        }
    }

    public function destroy($sessionId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->dual_sessions_table} WHERE id = :id");
            $stmt->bindParam(":id", $sessionId);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception($this->dualSessionLoggerError("warning", $e->getMessage()));
            return false;
        }
    }

    public function gc($maxLifetime)
    {
        try {
            $oldTimestamp = time() - $maxLifetime;
            $stmt = $this->db->prepare("DELETE FROM {$this->dual_sessions_table} WHERE timestamp < :oldTimestamp");
            $stmt->bindParam(":oldTimestamp", $oldTimestamp);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            throw new Exception($this->dualSessionLoggerError("warning", $e->getMessage()));
            return false;
        }
    }

    private function createTableIfNotExists()
    {
        try {
            if ($this->db) {
                if (!$this->dual_sessions_table) {
                    $sql = "
                    CREATE TABLE IF NOT EXISTS dual_sessions (
                        id VARCHAR(128) NOT NULL PRIMARY KEY,
                        data TEXT,
                        timestamp INT(11) NOT NULL
                    );";

                    $this->db->exec($sql);
                    $this->dual_sessions_table = "dual_sessions";
                } else {

                    if ($this->tableReallyExists($this->dual_sessions_table) == false) {
                        echo $this->dualSessionLoggerError("fatal", "Table supplied {$this->dual_sessions_table} doesn't exist");
                    }
                }
            } else {
                echo $this->dualSessionLoggerError("fatal", "Couldn't create table");
            }
        } catch (Exception $e) {
            throw new Exception($this->dualSessionLoggerError("fatal", "Failed initializing table..."));
        }
    }

    private function dualSessionLoggerError($level, $msg)
    {
        if ($level && $msg) {
            return "<b>DualSessionLogger_Error(<small style='color:red'>exit({$level})</small>):</b> {$msg}<hr>";
        }
    }

    private function tableReallyExists($tableName)
    {
        try {
            $sql = "SHOW TABLES LIKE :table_name";
            $stmt = $this->db->prepare($sql);

            if ($stmt) {
                $stmt->bindParam(':table_name', $tableName, PDO::PARAM_STR);
                $stmt->execute();

                return $stmt->rowCount() > 0;
            } else {
                return "SQL Error";
            }
        } catch (Exception $e) {
            return false;
        }
    }
}
