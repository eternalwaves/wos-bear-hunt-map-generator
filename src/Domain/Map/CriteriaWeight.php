<?php

namespace App\Domain\Map;

class CriteriaWeight
{
    public const CRITERIA_POWER = 'power';
    public const CRITERIA_LEVEL = 'level';
    public const CRITERIA_RANK = 'rank';
    public const CRITERIA_PARTICIPATION = 'participation';
    public const CRITERIA_CHIEF_GEAR_AND_CHARMS = 'chief_gear_and_charms';

    private string $criteria;
    private float $weight;

    public function __construct(string $criteria, float $weight)
    {
        $this->validateCriteria($criteria);
        $this->validateWeight($weight);
        
        $this->criteria = $criteria;
        $this->weight = $weight;
    }

    public function getCriteria(): string
    {
        return $this->criteria;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->validateWeight($weight);
        $this->weight = $weight;
    }

    public static function getAvailableCriteria(): array
    {
        return [
            self::CRITERIA_POWER => 'Power',
            self::CRITERIA_LEVEL => 'Level',
            self::CRITERIA_RANK => 'Rank',
            self::CRITERIA_PARTICIPATION => 'Participation',
            self::CRITERIA_CHIEF_GEAR_AND_CHARMS => 'Chief Gear and Charms'
        ];
    }

    public static function getDefaultWeights(): array
    {
        return [
            self::CRITERIA_POWER => 1.0,
            self::CRITERIA_LEVEL => 1.0,
            self::CRITERIA_RANK => 1.0,
            self::CRITERIA_PARTICIPATION => 1.0,
            self::CRITERIA_CHIEF_GEAR_AND_CHARMS => 1.0
        ];
    }

    private function validateCriteria(string $criteria): void
    {
        $validCriteria = array_keys(self::getAvailableCriteria());
        if (!in_array($criteria, $validCriteria)) {
            throw new \InvalidArgumentException("Invalid criteria: {$criteria}. Valid criteria: " . implode(', ', $validCriteria));
        }
    }

    private function validateWeight(float $weight): void
    {
        if ($weight < 0) {
            throw new \InvalidArgumentException("Weight must be non-negative, got: {$weight}");
        }
    }

    public function toArray(): array
    {
        return [
            'criteria' => $this->criteria,
            'weight' => $this->weight
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['criteria'], $data['weight']);
    }
} 