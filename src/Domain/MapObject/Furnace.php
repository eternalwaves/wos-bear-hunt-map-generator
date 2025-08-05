<?php

namespace App\Domain\MapObject;

class Furnace extends MapObject
{
    private string $name;
    private string $level;
    private int $power;
    private string $rank;
    private ?int $participation;
    private string $trapPref;
    private string $status;
    private bool $locked;
    
    // Chief Gear properties
    private ?string $capLevel;
    private ?string $watchLevel;
    private ?string $vestLevel;
    private ?string $pantsLevel;
    private ?string $ringLevel;
    private ?string $caneLevel;
    private ?string $capCharms;
    private ?string $watchCharms;
    private ?string $vestCharms;
    private ?string $pantsCharms;
    private ?string $ringCharms;
    private ?string $caneCharms;

    // Valid gear levels in order from lowest to highest
    private const VALID_GEAR_LEVELS = [
        'Uncommon', 'Uncommon *', 'Rare', 'Rare *', 'Rare **', 'Rare ***',
        'Epic', 'Epic *', 'Epic **', 'Epic ***', 'Epic T1', 'Epic T1 *', 'Epic T1 **', 'Epic T1 ***',
        'Mythic', 'Mythic *', 'Mythic **', 'Mythic ***', 'Mythic T1', 'Mythic T1 *', 'Mythic T1 **', 'Mythic T1 ***',
        'Mythic T2', 'Mythic T2 *', 'Mythic T2 **', 'Mythic T2 ***',
        'Legendary', 'Legendary *', 'Legendary **', 'Legendary ***',
        'Legendary T1', 'Legendary T1 *', 'Legendary T1 **', 'Legendary T1 ***',
        'Legendary T2', 'Legendary T2 *', 'Legendary T2 **', 'Legendary T2 ***',
        'Legendary T3', 'Legendary T3 *', 'Legendary T3 **', 'Legendary T3 ***'
    ];

    private const MAX_CHARM_LEVEL = 16;

    public function __construct(
        string $name,
        string $level,
        int $power,
        string $rank,
        ?int $participation,
        string $trapPref,
        ?int $x = null,
        ?int $y = null,
        string $id = null,
        string $status = '',
        bool $locked = false,
        ?string $capLevel = null,
        ?string $watchLevel = null,
        ?string $vestLevel = null,
        ?string $pantsLevel = null,
        ?string $ringLevel = null,
        ?string $caneLevel = null,
        ?string $capCharms = null,
        ?string $watchCharms = null,
        ?string $vestCharms = null,
        ?string $pantsCharms = null,
        ?string $ringCharms = null,
        ?string $caneCharms = null
    ) {
        parent::__construct($x ?? 0, $y ?? 0, 2, $id);
        $this->name = $name;
        $this->level = $level;
        $this->power = $power;
        $this->rank = $rank;
        $this->participation = $participation;
        $this->trapPref = $trapPref;
        $this->status = $status;
        $this->locked = $locked;
        
        // Set chief gear properties with validation
        $this->capLevel = $this->validateGearLevel($capLevel);
        $this->watchLevel = $this->validateGearLevel($watchLevel);
        $this->vestLevel = $this->validateGearLevel($vestLevel);
        $this->pantsLevel = $this->validateGearLevel($pantsLevel);
        $this->ringLevel = $this->validateGearLevel($ringLevel);
        $this->caneLevel = $this->validateGearLevel($caneLevel);
        
        $this->capCharms = $this->validateCharms($capCharms);
        $this->watchCharms = $this->validateCharms($watchCharms);
        $this->vestCharms = $this->validateCharms($vestCharms);
        $this->pantsCharms = $this->validateCharms($pantsCharms);
        $this->ringCharms = $this->validateCharms($ringCharms);
        $this->caneCharms = $this->validateCharms($caneCharms);
    }

    private function validateGearLevel(?string $level): ?string
    {
        if ($level === null || $level === '') {
            return null;
        }
        
        if (!in_array($level, self::VALID_GEAR_LEVELS)) {
            throw new \InvalidArgumentException("Invalid gear level: {$level}");
        }
        
        return $level;
    }

    private function validateCharms(?string $charms): ?string
    {
        if ($charms === null || $charms === '') {
            return null;
        }
        
        $charmLevels = explode(',', $charms);
        if (count($charmLevels) !== 3) {
            throw new \InvalidArgumentException("Charms must have exactly 3 comma-separated values: {$charms}");
        }
        
        foreach ($charmLevels as $charmLevel) {
            $charmLevel = trim($charmLevel);
            if (!is_numeric($charmLevel) || $charmLevel < 1 || $charmLevel > self::MAX_CHARM_LEVEL) {
                throw new \InvalidArgumentException("Invalid charm level: {$charmLevel}. Must be between 1 and " . self::MAX_CHARM_LEVEL . ".");
            }
        }
        
        return $charms;
    }

    public function getGearLevelIndex(string $level): int
    {
        return array_search($level, self::VALID_GEAR_LEVELS) ?: -1;
    }

    public function getHighestCharmLevel(?string $charms): int
    {
        if (!$charms) {
            return 0;
        }
        
        $charmLevels = array_map('intval', explode(',', $charms));
        return max($charmLevels);
    }

    public function getMeanCharmLevel(?string $charms): float
    {
        if (!$charms) {
            return 0.0;
        }
        
        $charmLevels = array_map('intval', explode(',', $charms));
        return count($charmLevels) > 0 ? array_sum($charmLevels) / count($charmLevels) : 0.0;
    }

    public function getMeanGearLevelIndex(?string $level1, ?string $level2): float
    {
        $index1 = $level1 ? $this->getGearLevelIndex($level1) : -1;
        $index2 = $level2 ? $this->getGearLevelIndex($level2) : -1;
        
        if ($index1 === -1 && $index2 === -1) {
            return -1.0;
        }
        
        if ($index1 === -1) {
            return (float) $index2;
        }
        
        if ($index2 === -1) {
            return (float) $index1;
        }
        
        return ($index1 + $index2) / 2.0;
    }

    public function getPlacementPriority(): int
    {
        // Calculate mean gear level indices for each group
        $primaryGearMean = $this->getMeanGearLevelIndex($this->ringLevel, $this->caneLevel);
        $secondaryGearMean = $this->getMeanGearLevelIndex($this->vestLevel, $this->pantsLevel);
        
        // Calculate mean charm levels for each group
        $primaryCharmsMean = ($this->getMeanCharmLevel($this->ringCharms) + $this->getMeanCharmLevel($this->caneCharms)) / 2.0;
        $secondaryCharmsMean = ($this->getMeanCharmLevel($this->vestCharms) + $this->getMeanCharmLevel($this->pantsCharms)) / 2.0;
        
        // Calculate weighted priority (primary gear gets 70% weight, secondary gets 30%)
        $primaryWeight = 0.6;
        $secondaryWeight = 0.4;
        
        // Normalize gear levels to 0-100 scale based on actual gear level count
        $maxGearLevel = count(self::VALID_GEAR_LEVELS);
        $normalizedPrimaryGear = min(100, ($primaryGearMean / $maxGearLevel) * 100);
        $normalizedSecondaryGear = min(100, ($secondaryGearMean / $maxGearLevel) * 100);
        
        // Normalize charm levels to 0-100 scale
        $normalizedPrimaryCharms = min(100, ($primaryCharmsMean / self::MAX_CHARM_LEVEL) * 100);
        $normalizedSecondaryCharms = min(100, ($secondaryCharmsMean / self::MAX_CHARM_LEVEL) * 100);
        
        // Calculate final priority as weighted average
        $primaryScore = (4 * $normalizedPrimaryGear + $normalizedPrimaryCharms) / 5;
        $secondaryScore = (4 * $normalizedSecondaryGear + $normalizedSecondaryCharms) / 5;
        
        $finalPriority = ($primaryScore * $primaryWeight) + ($secondaryScore * $secondaryWeight);
        
        return (int) round($finalPriority);
    }

    // Getters for chief gear properties
    public function getCapLevel(): ?string { return $this->capLevel; }
    public function getWatchLevel(): ?string { return $this->watchLevel; }
    public function getVestLevel(): ?string { return $this->vestLevel; }
    public function getPantsLevel(): ?string { return $this->pantsLevel; }
    public function getRingLevel(): ?string { return $this->ringLevel; }
    public function getCaneLevel(): ?string { return $this->caneLevel; }
    public function getCapCharms(): ?string { return $this->capCharms; }
    public function getWatchCharms(): ?string { return $this->watchCharms; }
    public function getVestCharms(): ?string { return $this->vestCharms; }
    public function getPantsCharms(): ?string { return $this->pantsCharms; }
    public function getRingCharms(): ?string { return $this->ringCharms; }
    public function getCaneCharms(): ?string { return $this->caneCharms; }

    // Setters for chief gear properties
    public function setCapLevel(?string $capLevel): void { $this->capLevel = $this->validateGearLevel($capLevel); }
    public function setWatchLevel(?string $watchLevel): void { $this->watchLevel = $this->validateGearLevel($watchLevel); }
    public function setVestLevel(?string $vestLevel): void { $this->vestLevel = $this->validateGearLevel($vestLevel); }
    public function setPantsLevel(?string $pantsLevel): void { $this->pantsLevel = $this->validateGearLevel($pantsLevel); }
    public function setRingLevel(?string $ringLevel): void { $this->ringLevel = $this->validateGearLevel($ringLevel); }
    public function setCaneLevel(?string $caneLevel): void { $this->caneLevel = $this->validateGearLevel($caneLevel); }
    public function setCapCharms(?string $capCharms): void { $this->capCharms = $this->validateCharms($capCharms); }
    public function setWatchCharms(?string $watchCharms): void { $this->watchCharms = $this->validateCharms($watchCharms); }
    public function setVestCharms(?string $vestCharms): void { $this->vestCharms = $this->validateCharms($vestCharms); }
    public function setPantsCharms(?string $pantsCharms): void { $this->pantsCharms = $this->validateCharms($pantsCharms); }
    public function setRingCharms(?string $ringCharms): void { $this->ringCharms = $this->validateCharms($ringCharms); }
    public function setCaneCharms(?string $caneCharms): void { $this->caneCharms = $this->validateCharms($caneCharms); }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): void
    {
        $this->level = $level;
    }

    public function getPower(): int
    {
        return $this->power;
    }

    public function setPower(int $power): void
    {
        $this->power = $power;
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    public function setRank(string $rank): void
    {
        $this->rank = $rank;
    }

    public function getParticipation(): ?int
    {
        return $this->participation;
    }

    public function setParticipation(?int $participation): void
    {
        $this->participation = $participation;
    }

    public function getTrapPref(): string
    {
        return $this->trapPref;
    }

    public function setTrapPref(string $trapPref): void
    {
        $this->trapPref = $trapPref;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    public function setPosition(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getType(): string
    {
        return 'furnace';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'power' => $this->power,
            'rank' => $this->rank,
            'participation' => $this->participation,
            'trap_pref' => $this->trapPref,
            'x' => $this->x,
            'y' => $this->y,
            'status' => $this->status,
            'locked' => $this->locked,
            'cap_level' => $this->capLevel,
            'watch_level' => $this->watchLevel,
            'vest_level' => $this->vestLevel,
            'pants_level' => $this->pantsLevel,
            'ring_level' => $this->ringLevel,
            'cane_level' => $this->caneLevel,
            'cap_charms' => $this->capCharms,
            'watch_charms' => $this->watchCharms,
            'vest_charms' => $this->vestCharms,
            'pants_charms' => $this->pantsCharms,
            'ring_charms' => $this->ringCharms,
            'cane_charms' => $this->caneCharms
        ];
    }

    public function hasPosition(): bool
    {
        return $this->x > 0 && $this->y > 0;
    }
} 