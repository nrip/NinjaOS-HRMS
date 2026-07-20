<?php

namespace App\Services;

/**
 * TenantContext Singleton
 * 
 * Stores the current tenant (location) context for the authenticated user.
 * This is used by the LocationScope global scope to filter queries.
 * 
 * CRITICAL: This must be set by the EnforceLocationScope middleware for every request.
 */
class TenantContext
{
    private ?int $locationId = null;
    private ?string $userId = null;

    /**
     * Set the current location ID (tenant)
     */
    public function setLocationId(?int $locationId): void
    {
        $this->locationId = $locationId;
    }

    /**
     * Get the current location ID (tenant)
     */
    public function getLocationId(): ?int
    {
        return $this->locationId;
    }

    /**
     * Set the current user ID
     */
    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get the current user ID
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * Check if a location ID is set
     */
    public function hasLocationId(): bool
    {
        return $this->locationId !== null;
    }

    /**
     * Clear the context (useful for testing or when switching contexts)
     */
    public function clear(): void
    {
        $this->locationId = null;
        $this->userId = null;
    }
}
