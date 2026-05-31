<?php

namespace apps\model;

use apps\config\dbconnection;
use PDO;
use PDOException;

class User {
    private PDO $db;

    public function __construct() {
        $dbClass  = new dbconnection();
        $this->db = $dbClass->connect();
        // Ensure PDO throws exceptions on error for the try-catch blocks to work
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // ── studentlist ──────────────────────────────────────────────────────────

    public function findStudentBySchoolId(string $schoolId): array|false {
        try {
            $stmt = $this->db->prepare(
                "SELECT schoolID, firstname, mi, lastname, suffix, program, department
                 FROM studentlist
                 WHERE schoolID = :schoolId
                 LIMIT 1"
            );
            $stmt->execute([':schoolId' => $schoolId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("findStudentBySchoolId Error: " . $e->getMessage());
            return false;
        }
    }

    // ── users ────────────────────────────────────────────────────────────────

    public function isAlreadyRegistered(string $schoolId): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM users
                 WHERE loginID = :schoolId
                 LIMIT 1"
            );
            $stmt->execute([':schoolId' => $schoolId]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return true; // Failsafe: prevent registration if check fails
        }
    }

    public function emailExists(string $email): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM users
                 WHERE email = :email
                 LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return true; // Failsafe
        }
    }

    public function register(array $data): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users
                    (loginID, firstname, mi, lastname, suffix, email, password, roles)
                 VALUES
                    (:loginId, :firstname, :mi, :lastname, :suffix, :email, :password, 'student')"
            );
            return $stmt->execute([
                ':loginId'   => $data['loginId'],
                ':firstname' => $data['firstname'],
                ':mi'        => $data['mi'],
                ':lastname'  => $data['lastname'],
                ':suffix'    => $data['suffix'],
                ':email'     => $data['email'],
                ':password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            return false;
        }
    }

    // ── login ────────────────────────────────────────────────────────────────

    public function findByCredential(string $credential): array|false {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, loginID, firstname, lastname, email, password, roles
                 FROM users
                 WHERE loginID = :cred OR email = :cred2
                 LIMIT 1"
            );
            $stmt->execute([':cred' => $credential, ':cred2' => $credential]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("findByCredential Error: " . $e->getMessage());
            return false;
        }
    }

    public function updateLastLogin(int $userId): void {
        try {
            $stmt = $this->db->prepare(
                "UPDATE users SET lastLogin = NOW() WHERE id = :id"
            );
            $stmt->execute([':id' => $userId]);
        } catch (PDOException $e) {
            error_log("updateLastLogin Error: " . $e->getMessage());
        }
    }
}