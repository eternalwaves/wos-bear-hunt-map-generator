<?php

namespace App\Application\Service;

use App\Domain\Map\CriteriaWeight;
use App\Domain\MapObject\Furnace;

class WeightedCriteriaService
{
    /**
     * Calculate weighted score for a furnace based on criteria weights
     */
    public function calculateWeightedScore(Furnace $furnace, array $criteriaWeights): float
    {
        $totalScore = 0.0;
        $totalWeight = 0.0;

        foreach ($criteriaWeights as $criteriaWeight) {
            if (!($criteriaWeight instanceof CriteriaWeight)) {
                throw new \InvalidArgumentException('Each criteria weight must be a CriteriaWeight instance');
            }

            $weight = $criteriaWeight->getWeight();
            if ($weight <= 0) {
                continue; // Skip zero-weight criteria
            }

            $score = $this->getCriteriaScore($furnace, $criteriaWeight->getCriteria());
            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        // Return normalized score (average if all weights are equal)
        return $totalWeight > 0 ? $totalScore / $totalWeight : 0.0;
    }

    /**
     * Get individual criteria score for a furnace (normalized to 0-100)
     */
    private function getCriteriaScore(Furnace $furnace, string $criteria): float
    {
        switch ($criteria) {
            case CriteriaWeight::CRITERIA_POWER:
                // Normalize power to 0-100 (assuming max power is around 1000000000)
                $power = $furnace->getPower();
                $maxPower = 1000000000.0;
                return min(100, ($power / $maxPower) * 100);

            case CriteriaWeight::CRITERIA_LEVEL:
                // Normalize level to 0-100 (max level is FC10 = 40)
                $levelScore = $this->getLevelScore($furnace->getLevel());
                $maxLevel = 40.0;
                return min(100, ($levelScore / $maxLevel) * 100);

            case CriteriaWeight::CRITERIA_RANK:
                // Normalize rank to 0-100 (max rank is R5)
                $rankScore = $this->getRankScore($furnace->getRank());
                $maxRank = 5.0;
                return min(100, ($rankScore / $maxRank) * 100);

            case CriteriaWeight::CRITERIA_PARTICIPATION:
                // Normalize participation to 0-100 (max participation is 4)
                $participation = $furnace->getParticipation();
                if ($participation === null) {
                    return 0.0;
                }
                $maxParticipation = 4.0;
                return min(100, ($participation / $maxParticipation) * 100);

            case CriteriaWeight::CRITERIA_CHIEF_GEAR_AND_CHARMS:
                // The placement priority is already normalized to 0-100
                return $this->getChiefGearAndCharmsScore($furnace);

            default:
                throw new \InvalidArgumentException("Unknown criteria: {$criteria}");
        }
    }

    /**
     * Convert level to numeric score (1-30 = 1-30, FC1-FC10 = 31-40)
     */
    private function getLevelScore(string $level): float
    {
        if (is_numeric($level)) {
            return (float) $level;
        }

        if (preg_match('/^FC(\d+)$/', $level, $matches)) {
            return 30.0 + (float) $matches[1];
        }

        return 0.0; // Invalid level
    }

    /**
     * Convert rank to numeric score (R1=1, R2=2, etc.)
     */
    private function getRankScore(string $rank): float
    {
        if (preg_match('/^R(\d+)$/', $rank, $matches)) {
            return (float) $matches[1];
        }

        return 0.0; // Invalid rank
    }

    /**
     * Calculate chief gear and charms score using the existing placement priority logic
     */
    private function getChiefGearAndCharmsScore(Furnace $furnace): float
    {
        // The placement priority is already normalized to 0-100
        return $furnace->getPlacementPriority();
    }

    /**
     * Sort furnaces by weighted criteria
     */
    public function sortFurnacesByWeightedCriteria(array $furnaces, array $criteriaWeights): array
    {
        $furnacesWithScores = [];
        
        foreach ($furnaces as $furnace) {
            $score = $this->calculateWeightedScore($furnace, $criteriaWeights);
            $furnacesWithScores[] = [
                'furnace' => $furnace,
                'score' => $score
            ];
        }

        // Sort by score in descending order (highest score first)
        usort($furnacesWithScores, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Return just the furnaces in sorted order
        return array_map(function ($item) {
            return $item['furnace'];
        }, $furnacesWithScores);
    }

    /**
     * Validate criteria weights array
     */
    public function validateCriteriaWeights(array $criteriaWeights): void
    {
        if (empty($criteriaWeights)) {
            throw new \InvalidArgumentException('At least one criteria weight must be provided');
        }

        foreach ($criteriaWeights as $criteriaWeight) {
            if (!($criteriaWeight instanceof CriteriaWeight)) {
                throw new \InvalidArgumentException('Each criteria weight must be a CriteriaWeight instance');
            }
        }

        // Check for duplicate criteria
        $criteria = array_map(function ($cw) {
            return $cw->getCriteria();
        }, $criteriaWeights);

        $duplicates = array_diff_assoc($criteria, array_unique($criteria));
        if (!empty($duplicates)) {
            throw new \InvalidArgumentException('Duplicate criteria found: ' . implode(', ', array_unique($duplicates)));
        }
    }

    /**
     * Create criteria weights from array format
     */
    public function createCriteriaWeightsFromArray(array $criteriaWeightsData): array
    {
        $criteriaWeights = [];
        
        foreach ($criteriaWeightsData as $data) {
            if (!isset($data['criteria']) || !isset($data['weight'])) {
                throw new \InvalidArgumentException('Each criteria weight must have "criteria" and "weight" fields');
            }
            
            $criteriaWeights[] = new CriteriaWeight($data['criteria'], (float) $data['weight']);
        }

        return $criteriaWeights;
    }
} 