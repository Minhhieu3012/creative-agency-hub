<?php

class Database {

    public static function getConnection() {

        try {
            return new PDO(
                "mysql:host=localhost;dbname=creative_agency;charset=utf8mb4",
                "root",
                ""
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}