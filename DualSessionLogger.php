<?php 

// implements native `SessionHandlerInterface`(open | close | read | write | destroy | gc)
// Author: Mukul kumar(https://github.com/slimdestro/)

// im running on PHP 7.4.33 so some warnings however thats not core issue
error_reporting(0);

class DualSessionLogger implements SessionHandlerInterface {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    // standard implementation read() from SessionHandlerInterface
    public function read($sessionId) {
        try {
            $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = :id");
            $stmt->bindParam(":id", $sessionId);
            $stmt->execute();

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['data'];
            }

            return "";
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    
    // standard implementation write() from SessionHandlerInterface
    public function write($sessionId, $data) {
        try {
            $timestamp = time();
            $stmt = $this->db->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (:id, :data, :timestamp)");
            $stmt->bindParam(":id", $sessionId);
            $stmt->bindParam(":data", $data);
            $stmt->bindParam(":timestamp", $timestamp);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            print_r($e);
        }
    }

    
    // standard implementation destroy() from SessionHandlerInterface
    public function destroy($sessionId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = :id");
            $stmt->bindParam(":id", $sessionId);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            print_r($e);
        }
    }

    
    // standard implementation gc() from SessionHandlerInterface
    public function gc($maxLifetime) {
        try {
            $oldTimestamp = time() - $maxLifetime;
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE timestamp < :oldTimestamp");
            $stmt->bindParam(":oldTimestamp", $oldTimestamp);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}

