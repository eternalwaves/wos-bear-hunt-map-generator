import { MapObject } from './MapObject.js';

export class Furnace extends MapObject {
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
    this.level = level;
    this.power = power;
    this.rank = rank;
    this.participation = participation;
    this.trapPref = trapPref;
    this.status = status;
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

  validateGearLevel(level) {
    if (level === null || level === '') {
      return null;
    }
    
    if (!Furnace.VALID_GEAR_LEVELS.includes(level)) {
      throw new Error(`Invalid gear level: ${level}`);
    }
    
    return level;
  }

  validateCharms(charms) {
    if (charms === null || charms === '') {
      return null;
    }
    
    const charmArray = charms.split(',').map(c => c.trim());
    for (const charm of charmArray) {
      const charmLevel = parseInt(charm);
      if (isNaN(charmLevel) || charmLevel < 0 || charmLevel > Furnace.MAX_CHARM_LEVEL) {
        throw new Error(`Invalid charm level: ${charm}`);
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
  setLevel(level) { this.level = level; }
  setPower(power) { this.power = power; }
  setRank(rank) { this.rank = rank; }
  setParticipation(participation) { this.participation = participation; }
  setTrapPref(trapPref) { this.trapPref = trapPref; }
  setStatus(status) { this.status = status; }
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