<?php

class Db {
    // The connection
    protected static $conn;
	public static $dbToUse = "";
    
    /**
     * Connect to the database
     * 
     * @return die with error message on failure
     */
    public function connMySQL($dbName = "") {    
        
		// Try db connection
        if(!isset(self::$conn)) {
            
			// Load config
            $config = parse_ini_file('./dbConfig.ini'); 
			
			//Provides the ability to easily use multiple DBs
			if(empty($dbName)){
				self::$dbToUse = $config['dbname'];
			}
				
            self::$conn = new mysqli($config['host'],$config['username'],$config['password'],self::$dbToUse);
			
        }

        // If connection was not successful, handle the error
        if(self::$conn === false) {
            if(self::$conn->connect_error){ 
				 die('Connect Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
			} 
            
        }
        return self::$conn;
    }
}
