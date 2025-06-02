<?php
/**
 * Security rate limiter for public download and verification
 * Prevents brute force attempts to access gatepasses
 */

class RateLimiter {
    private $max_attempts;
    private $time_window;
    private $db_conn;
    
    /**
     * Constructor
     * 
     * @param int $max_attempts Maximum number of attempts allowed in time window
     * @param int $time_window Time window in seconds
     */
    public function __construct($max_attempts = 10, $time_window = 3600) {
        $this->max_attempts = $max_attempts;
        $this->time_window = $time_window;
        
        // Connect to database
        $this->db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->db_conn->connect_error) {
            error_log("RateLimiter: Database connection failed: " . $this->db_conn->connect_error);
        }
        
        // Ensure the rate_limit table exists
        $this->createTableIfNotExists();
    }
    
    /**
     * Create rate limit table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action VARCHAR(50) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            attempt_count INT DEFAULT 1,
            blocked_until TIMESTAMP NULL,
            INDEX (ip_address, action)
        )";
        
        if (!$this->db_conn->query($sql)) {
            error_log("RateLimiter: Error creating table: " . $this->db_conn->error);
        }
    }
    
    /**
     * Check if an IP is allowed to perform an action
     * 
     * @param string $ip_address The IP address to check
     * @param string $action The action being performed
     * @return bool True if allowed, false if rate limited
     */
    public function isAllowed($ip_address, $action) {
        // Clean up old records first
        $this->cleanupOldRecords();
        
        // Check if IP is currently blocked
        $stmt = $this->db_conn->prepare("SELECT blocked_until FROM rate_limit 
                                      WHERE ip_address = ? AND action = ? AND 
                                      blocked_until IS NOT NULL AND 
                                      blocked_until > NOW()");
        
        if (!$stmt) {
            error_log("RateLimiter: Prepare failed: " . $this->db_conn->error);
            return true; // If we can't check, we allow (fail open)
        }
        
        $stmt->bind_param("ss", $ip_address, $action);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $blocked_until = strtotime($row['blocked_until']);
            $minutes_remaining = ceil(($blocked_until - time()) / 60);
            
            // Log this blocked attempt
            error_log("RateLimiter: Blocked attempt from IP: $ip_address for action: $action. Blocked for $minutes_remaining more minutes");
            
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        
        // Count recent attempts
        $stmt = $this->db_conn->prepare("SELECT COUNT(*) as attempt_count FROM rate_limit 
                                      WHERE ip_address = ? AND action = ? AND 
                                      attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)");
                                      
        if (!$stmt) {
            error_log("RateLimiter: Prepare failed: " . $this->db_conn->error);
            return true;
        }
        
        $stmt->bind_param("ssi", $ip_address, $action, $this->time_window);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $attempts = $row['attempt_count'];
        
        $stmt->close();
        
        // If too many attempts, block the IP
        if ($attempts >= $this->max_attempts) {
            // Block for twice the time window
            $block_duration = $this->time_window * 2;
            $block_until = date('Y-m-d H:i:s', time() + $block_duration);
            
            $stmt = $this->db_conn->prepare("INSERT INTO rate_limit 
                                         (ip_address, action, blocked_until, attempt_count) 
                                         VALUES (?, ?, ?, ?)");
            
            if (!$stmt) {
                error_log("RateLimiter: Prepare failed: " . $this->db_conn->error);
                return true;
            }
            
            $count = $this->max_attempts + 1;
            $stmt->bind_param("sssi", $ip_address, $action, $block_until, $count);
            $stmt->execute();
            $stmt->close();
            
            // Log this event
            error_log("RateLimiter: IP $ip_address blocked for action $action until $block_until due to too many attempts");
            
            return false;
        }
        
        // Record this attempt
        $stmt = $this->db_conn->prepare("INSERT INTO rate_limit (ip_address, action) VALUES (?, ?)");
        
        if (!$stmt) {
            error_log("RateLimiter: Prepare failed: " . $this->db_conn->error);
            return true;
        }
        
        $stmt->bind_param("ss", $ip_address, $action);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Clean up old records
     */
    private function cleanupOldRecords() {
        // Delete old attempt records
        $this->db_conn->query("DELETE FROM rate_limit 
                             WHERE blocked_until IS NULL AND 
                             attempt_time < DATE_SUB(NOW(), INTERVAL " . ($this->time_window * 2) . " SECOND)");
        
        // Delete expired blocks
        $this->db_conn->query("DELETE FROM rate_limit 
                             WHERE blocked_until IS NOT NULL AND 
                             blocked_until < NOW()");
    }
    
    /**
     * Close the database connection
     */
    public function __destruct() {
        if ($this->db_conn) {
            $this->db_conn->close();
        }
    }
}
?>
