<?php

namespace Mts88\MongoGrid\Services;

use \MongoDB\Client;

class MongoClient {

    /**
     * Connection to MongoDB
     */
    private $connection;

    /**
     * Selected DB
     */
    private $db;

    /**
     * Config of connection
     */
    private $config;

    /**
     * Options of connection
     */
    private $options;

    public function __construct() {

        $this->config = config('gridfs.db_config');
        $this->connectDB()->selectDatabase();

    }

    /**
     * Return connection to db
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Return the GridFS bucket
     */
    public function getGridFS($options) {
        try {
            return $this->connection->{$this->db}->selectGridFSBucket($options);
        } catch(\MongoDB\Exception\InvalidArgumentException $e) {
            echo "InvalidArgumentException: ", $e->getMessage(), "\n";
        }
    }

    public function getMongoDB() {
        return $this->db;
    }


    /**
     * Connect to MongoDB
     */
    private function connectDB() {

        $dsn = "mongodb://";

        if(is_array($this->config['host'])) {
            $hosts = $this->config['host'];
            $list = [];

            foreach($hosts as $host) {
                array_push($list, trim($host['address']) . ':' . $host['port']);
            }

            $dsn .= implode(",", $list);
        } else {
            $dsn .= trim($this->config['host']) . ':' . $this->config['port'];
        }

        $this->options = [
            'username'          =>  $this->config['username'],
            'password'          =>  $this->config['password'],
            'db'                =>  $this->config['options']['database']
        ];

        // Add replicaSet name if is necessary
        if( isset($this->config['options']['replicaSet']) ) {
            $this->options['replicaSet'] = $this->config['options']['replicaSet'];
        }

        try {

            $this->connection = new Client($dsn, $this->options);

        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            echo "AuthenticationException: ", $e->getMessage(), "\n";
        } catch (\MongoDB\Driver\Exception\ConnectionException $e) {
            echo "ConnectionException: ", $e->getMessage(), "\n";
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            echo "ConnectionTimeoutException: ", $e->getMessage(), "\n";
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            echo "Exception: ", $e->getMessage(), "\n";
        }

        return $this;
    }

    /**
     * Select the database
     */
    private function selectDatabase() {
        $this->db = $this->connection->selectDatabase($this->config['database']);
        return $this;
    }

}
