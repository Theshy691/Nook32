
<?php

class User
{
    private int $userId;
    private string $fullName;
    private string $username;
    private string $role;

    public function __construct(
        int $userId,
        string $fullName,
        string $username,
        string $role
    ) {
        $this->userId = $userId;
        $this->fullName = $fullName;
        $this->username = $username;
        $this->role = $role;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return strtolower($this->role) === "admin";
    }
}
