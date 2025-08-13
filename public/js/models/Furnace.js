import { MapObject } from './MapObject.js';

export class Furnace extends MapObject {
  // Validation constants - centralized from backend
  static VALID_LEVELS = [
    '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
    '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
    '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
    'FC1', 'FC2', 'FC3', 'FC4', 'FC5', 'FC6', 'FC7', 'FC8', 'FC9', 'FC10'
  ];

  static VALID_RANKS = ['R1', 'R2', 'R3', 'R4', 'R5'];

  static VALID_TRAP_PREFERENCES = ['1', '2', 'both', 'n/a'];

  static VALID_STATUSES = ['', 'assigned', 'moved', 'messaged', 'wrong'];

  static VALID_GEAR_LEVELS = [
    'Uncommon', 'Uncommon *', 'Rare', 'Rare *', 'Rare **', 'Rare ***',
    'Epic', 'Epic *', 'Epic **', 'Epic ***', 'Epic T1', 'Epic T1 *', 'Epic T1 **', 'Epic T1 ***',
    'Mythic', 'Mythic *', 'Mythic **', 'Mythic ***', 'Mythic T1', 'Mythic T1 *', 'Mythic T1 **', 'Mythic T1 ***',
    'Mythic T2', 'Mythic T2 *', 'Mythic T2 **', 'Mythic T2 ***',
    'Legendary', 'Legendary *', 'Legendary **', 'Legendary ***',
    'Legendary T1', 'Legendary T1 *', 'Legendary T1 **', 'Legendary T1 ***',
    'Legendary T2', 'Legendary T2 *', 'Legendary T2 **', 'Legendary T2 ***',
    'Legendary T3', 'Legendary T3 *', 'Legendary T3 **', 'Legendary T3 ***'
  ];

  static MAX_CHARM_LEVEL = 16;

  constructor(
    name,
    level,
    power,
    rank,
    participation,
    trapPref,
    x = null,
    y = null,
    id = null,
    status = '',
    locked = false,
    capLevel = null,
    watchLevel = null,
    vestLevel = null,
    pantsLevel = null,
    ringLevel = null,
    caneLevel = null,
    capCharms = null,
    watchCharms = null,
    vestCharms = null,
    pantsCharms = null,
    ringCharms = null,
    caneCharms = null
  ) {
    super(x ?? 0, y ?? 0, 2, id);
    this.name = name;
    this.level = this.validateLevel(level);
    this.power = this.validatePower(power);
    this.rank = this.validateRank(rank);
    this.participation = this.validateParticipation(participation);
    this.trapPref = trapPref ? this.validateTrapPref(trapPref) : '';
    this.status = this.validateStatus(status);
    this.locked = locked;
    
    // Set chief gear properties with validation
    this.capLevel = this.validateGearLevel(capLevel);
    this.watchLevel = this.validateGearLevel(watchLevel);
    this.vestLevel = this.validateGearLevel(vestLevel);
    this.pantsLevel = this.validateGearLevel(pantsLevel);
    this.ringLevel = this.validateGearLevel(ringLevel);
    this.caneLevel = this.validateGearLevel(caneLevel);
    
    this.capCharms = this.validateCharms(capCharms);
    this.watchCharms = this.validateCharms(watchCharms);
    this.vestCharms = this.validateCharms(vestCharms);
    this.pantsCharms = this.validateCharms(pantsCharms);
    this.ringCharms = this.validateCharms(ringCharms);
    this.caneCharms = this.validateCharms(caneCharms);
  }

  // Validation methods
  validateLevel(level) {
    if (!Furnace.VALID_LEVELS.includes(level)) {
      throw new Error(`Invalid level: ${level}. Must be one of: ${Furnace.VALID_LEVELS.join(', ')}`);
    }
    return level;
  }

  validatePower(power) {
    if (power <= 0) {
      throw new Error(`Power must be a positive integer, got: ${power}`);
    }
    return power;
  }

  validateRank(rank) {
    if (!Furnace.VALID_RANKS.includes(rank)) {
      throw new Error(`Invalid rank: ${rank}. Must be one of: ${Furnace.VALID_RANKS.join(', ')}`);
    }
    return rank;
  }

  validateParticipation(participation) {
    if (participation !== null && (participation < 0 || participation > 4)) {
      throw new Error(`Participation must be between 0 and 4, got: ${participation}`);
    }
    return participation;
  }

  validateTrapPref(trapPref) {
    if (!Furnace.VALID_TRAP_PREFERENCES.includes(trapPref)) {
      throw new Error(`Invalid trap preference: ${trapPref}. Must be one of: ${Furnace.VALID_TRAP_PREFERENCES.join(', ')}`);
    }
    return trapPref;
  }

  validateStatus(status) {
    if (!Furnace.VALID_STATUSES.includes(status)) {
      throw new Error(`Invalid status: ${status}. Must be one of: ${Furnace.VALID_STATUSES.join(', ')}`);
    }
    return status;
  }

  validateGearLevel(level) {
    if (level === null || level === '') {
      return null;
    }
    
    if (!Furnace.VALID_GEAR_LEVELS.includes(level)) {
      throw new Error(`Invalid gear level: ${level}. Must be one of: ${Furnace.VALID_GEAR_LEVELS.join(', ')}`);
    }
    
    return level;
  }

  validateCharms(charms) {
    if (charms === null || charms === '') {
      return null;
    }
    
    const charmLevels = charms.split(',').map(c => c.trim());
    if (charmLevels.length !== 3) {
      throw new Error(`Charms must have exactly 3 comma-separated values: ${charms}`);
    }
    
    for (const charmLevel of charmLevels) {
      const level = parseInt(charmLevel);
      if (isNaN(level) || level < 1 || level > Furnace.MAX_CHARM_LEVEL) {
        throw new Error(`Invalid charm level: ${charmLevel}. Must be between 1 and ${Furnace.MAX_CHARM_LEVEL}.`);
      }
    }
    
    return charms;
  }

  getGearLevelIndex(level) {
    return Furnace.VALID_GEAR_LEVELS.indexOf(level);
  }

  getHighestCharmLevel(charms) {
    if (!charms) return 0;
    const charmArray = charms.split(',').map(c => parseInt(c.trim()));
    return Math.max(...charmArray);
  }

  getMeanCharmLevel(charms) {
    if (!charms) return 0;
    const charmArray = charms.split(',').map(c => parseInt(c.trim()));
    return charmArray.reduce((sum, level) => sum + level, 0) / charmArray.length;
  }

  getMeanGearLevelIndex(level1, level2) {
    const index1 = level1 ? this.getGearLevelIndex(level1) : 0;
    const index2 = level2 ? this.getGearLevelIndex(level2) : 0;
    return (index1 + index2) / 2;
  }

  getPlacementPriority() {
    // Calculate priority based on power, level, rank, participation, and chief gear
    let priority = this.power;
    
    // Add level bonus (higher level = higher priority)
    const levelBonus = this.getGearLevelIndex(this.level) * 10;
    priority += levelBonus;
    
    // Add rank bonus (higher rank = higher priority)
    const rankBonus = parseInt(this.rank.replace('R', '')) * 50;
    priority += rankBonus;
    
    // Add participation bonus
    if (this.participation) {
      priority += this.participation * 20;
    }
    
    // Add chief gear bonus
    const gearLevels = [this.capLevel, this.watchLevel, this.vestLevel, this.pantsLevel, this.ringLevel, this.caneLevel];
    const gearBonus = gearLevels.reduce((sum, level) => {
      return sum + (level ? this.getGearLevelIndex(level) : 0);
    }, 0);
    priority += gearBonus;
    
    return priority;
  }

  // Getters
  getName() { return this.name; }
  getLevel() { return this.level; }
  getPower() { return this.power; }
  getRank() { return this.rank; }
  getParticipation() { return this.participation; }
  getTrapPref() { return this.trapPref; }
  getStatus() { return this.status; }
  isLocked() { return this.locked; }
  
  // Chief gear getters
  getCapLevel() { return this.capLevel; }
  getWatchLevel() { return this.watchLevel; }
  getVestLevel() { return this.vestLevel; }
  getPantsLevel() { return this.pantsLevel; }
  getRingLevel() { return this.ringLevel; }
  getCaneLevel() { return this.caneLevel; }
  getCapCharms() { return this.capCharms; }
  getWatchCharms() { return this.watchCharms; }
  getVestCharms() { return this.vestCharms; }
  getPantsCharms() { return this.pantsCharms; }
  getRingCharms() { return this.ringCharms; }
  getCaneCharms() { return this.caneCharms; }

  // Setters
  setName(name) { this.name = name; }
  setLevel(level) { this.level = this.validateLevel(level); }
  setPower(power) { this.power = this.validatePower(power); }
  setRank(rank) { this.rank = this.validateRank(rank); }
  setParticipation(participation) { this.participation = this.validateParticipation(participation); }
  setTrapPref(trapPref) { this.trapPref = this.validateTrapPref(trapPref); }
  setStatus(status) { this.status = this.validateStatus(status); }
  setLocked(locked) { this.locked = locked; }
  
  // Chief gear setters
  setCapLevel(capLevel) { this.capLevel = this.validateGearLevel(capLevel); }
  setWatchLevel(watchLevel) { this.watchLevel = this.validateGearLevel(watchLevel); }
  setVestLevel(vestLevel) { this.vestLevel = this.validateGearLevel(vestLevel); }
  setPantsLevel(pantsLevel) { this.pantsLevel = this.validateGearLevel(pantsLevel); }
  setRingLevel(ringLevel) { this.ringLevel = this.validateGearLevel(ringLevel); }
  setCaneLevel(caneLevel) { this.caneLevel = this.validateGearLevel(caneLevel); }
  setCapCharms(capCharms) { this.capCharms = this.validateCharms(capCharms); }
  setWatchCharms(watchCharms) { this.watchCharms = this.validateCharms(watchCharms); }
  setVestCharms(vestCharms) { this.vestCharms = this.validateCharms(vestCharms); }
  setPantsCharms(pantsCharms) { this.pantsCharms = this.validateCharms(pantsCharms); }
  setRingCharms(ringCharms) { this.ringCharms = this.validateCharms(ringCharms); }
  setCaneCharms(caneCharms) { this.caneCharms = this.validateCharms(caneCharms); }

  getType() {
    return 'furnace';
  }

  toArray() {
    return {
      ...super.toArray(),
      name: this.name,
      level: this.level,
      power: this.power,
      rank: this.rank,
      participation: this.participation,
      trapPref: this.trapPref,
      status: this.status,
      locked: this.locked,
      capLevel: this.capLevel,
      watchLevel: this.watchLevel,
      vestLevel: this.vestLevel,
      pantsLevel: this.pantsLevel,
      ringLevel: this.ringLevel,
      caneLevel: this.caneLevel,
      capCharms: this.capCharms,
      watchCharms: this.watchCharms,
      vestCharms: this.vestCharms,
      pantsCharms: this.pantsCharms,
      ringCharms: this.ringCharms,
      caneCharms: this.caneCharms
    };
  }

  hasPosition() {
    return this.x > 0 && this.y > 0;
  }
} 