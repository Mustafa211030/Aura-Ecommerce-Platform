<?php
/**
 * User Model
 *
 * Handles all database operations related to the users table.
 */

declare(strict_types=1);

namespace Core\Classes;

use Core\Database\Connection;
use Core\Helpers\Security;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getPDO();
    }

    /**
     * Find a user by their email address.
     *
     * @param  string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find a user by their ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Create a new user record.
     *
     * @param  string $name
     * @param  string $email
     * @param  string $plainPassword
     * @param  string $role
     * @return int  The new user's ID
     */
    public function create(string $name, string $email, string $plainPassword, string $role = 'user'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)'
        );
        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':password' => Security::hashPassword($plainPassword),
            ':role'     => $role,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update basic profile information.
     *
     * @param  int    $id
     * @param  array  $data  Keys: name, phone, address, city, country
     * @return bool
     */
    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET name=:name, phone=:phone, address=:address,
             city=:city, country=:country WHERE id=:id'
        );
        return $stmt->execute([
            ':name'    => $data['name'],
            ':phone'   => $data['phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':city'    => $data['city'] ?? null,
            ':country' => $data['country'] ?? null,
            ':id'      => $id,
        ]);
    }

    /**
     * Update a user's hashed password.
     *
     * @param  int    $id
     * @param  string $newPassword  Plain text
     * @return bool
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password=:password WHERE id=:id'
        );
        return $stmt->execute([
            ':password' => Security::hashPassword($newPassword),
            ':id'       => $id,
        ]);
    }

    /**
     * Update user avatar filename.
     *
     * @param  int    $id
     * @param  string $filename
     * @return bool
     */
    public function updateAvatar(int $id, string $filename): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET avatar=:avatar WHERE id=:id');
        return $stmt->execute([':avatar' => $filename, ':id' => $id]);
    }

    /**
     * Promote / demote a user's role.
     *
     * @param  int    $id
     * @param  string $role  admin|user
     * @return bool
     */
    public function setRole(int $id, string $role): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET role=:role WHERE id=:id');
        return $stmt->execute([':role' => $role, ':id' => $id]);
    }

    /**
     * Delete a user.
     *
     * @param  int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id=:id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get all users with optional pagination.
     *
     * @param  int $page
     * @param  int $perPage
     * @return array
     */
    public function getAll(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, created_at FROM users
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count total users.
     *
     * @return int
     */
    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /**
     * Count new users registered in the last 30 days.
     *
     * @return int
     */
    public function countNewLast30Days(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
