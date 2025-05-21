<?php
namespace MoBooking\Database;

/**
 * Database Transaction Manager
 * Provides utility methods for handling database transactions safely
 */
class TransactionManager {
    /**
     * @var bool Flag to track if a transaction is active
     */
    private $transaction_active = false;
    
    /**
     * Start a database transaction
     * 
     * @return bool True if transaction started successfully
     */
    public function start() {
        global $wpdb;
        
        if ($this->transaction_active) {
            // Transaction already active
            return false;
        }
        
        // Start transaction
        $result = $wpdb->query('START TRANSACTION');
        
        if ($result !== false) {
            $this->transaction_active = true;
            return true;
        }
        
        return false;
    }
    
    /**
     * Commit the current transaction
     * 
     * @return bool True if committed successfully
     */
    public function commit() {
        global $wpdb;
        
        if (!$this->transaction_active) {
            // No active transaction
            return false;
        }
        
        // Commit transaction
        $result = $wpdb->query('COMMIT');
        
        if ($result !== false) {
            $this->transaction_active = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * Rollback the current transaction
     * 
     * @return bool True if rolled back successfully
     */
    public function rollback() {
        global $wpdb;
        
        if (!$this->transaction_active) {
            // No active transaction
            return false;
        }
        
        // Rollback transaction
        $result = $wpdb->query('ROLLBACK');
        
        if ($result !== false) {
            $this->transaction_active = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute a function inside a transaction, with automatic rollback on error
     * 
     * @param callable $callback Function to execute
     * @return mixed Result of the callback function or false on error
     */
    public function execute($callback) {
        try {
            // Start transaction
            $this->start();
            
            // Execute the callback
            $result = call_user_func($callback);
            
            // If callback returned false, rollback
            if ($result === false) {
                $this->rollback();
                return false;
            }
            
            // Commit transaction
            $this->commit();
            
            return $result;
        } catch (\Exception $e) {
            // Log error
            error_log('Transaction error: ' . $e->getMessage());
            
            // Rollback on exception
            $this->rollback();
            
            return false;
        }
    }
    
    /**
     * Check if a transaction is currently active
     * 
     * @return bool True if transaction is active
     */
    public function is_active() {
        return $this->transaction_active;
    }
}