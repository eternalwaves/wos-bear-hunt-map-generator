<?php

namespace App\Domain\Repository;

use App\Domain\Map\Map;

interface MapRepositoryInterface
{
    public function save(Map $map): void;
    public function findById(string $id): ?Map;
    public function findByName(string $name): ?Map;
    public function findAll(): array;
    public function delete(string $id): bool;
    public function saveVersion(Map $map, string $version): void;
    public function findVersion(string $mapId, string $version): ?Map;
    public function getVersions(string $mapId): array;
    public function deleteVersion(string $mapId, string $version): bool;
} 