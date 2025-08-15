/**
 * Test for Frontend Furnace Validation
 * Tests all validation methods and constants in the frontend Furnace model
 */

import { Furnace } from '../public/js/models/Furnace.js';

describe('Frontend Furnace Validation', () => {
  describe('Validation Constants', () => {
    test('VALID_LEVELS contains expected values', () => {
      expect(Array.isArray(Furnace.VALID_LEVELS)).toBe(true);
      expect(Furnace.VALID_LEVELS).toContain('1');
      expect(Furnace.VALID_LEVELS).toContain('30');
      expect(Furnace.VALID_LEVELS).toContain('FC1');
      expect(Furnace.VALID_LEVELS).toContain('FC10');
      expect(Furnace.VALID_LEVELS.length).toBe(40); // 1-30 + FC1-FC10
    });

    test('VALID_RANKS contains expected values', () => {
      expect(Array.isArray(Furnace.VALID_RANKS)).toBe(true);
      expect(Furnace.VALID_RANKS).toContain('R1');
      expect(Furnace.VALID_RANKS).toContain('R5');
      expect(Furnace.VALID_RANKS.length).toBe(5);
    });

    test('VALID_TRAP_PREFERENCES contains expected values', () => {
      expect(Array.isArray(Furnace.VALID_TRAP_PREFERENCES)).toBe(true);
      expect(Furnace.VALID_TRAP_PREFERENCES).toContain('1');
      expect(Furnace.VALID_TRAP_PREFERENCES).toContain('2');
      expect(Furnace.VALID_TRAP_PREFERENCES).toContain('both');
      expect(Furnace.VALID_TRAP_PREFERENCES).toContain('n/a');
      expect(Furnace.VALID_TRAP_PREFERENCES.length).toBe(4);
    });

    test('VALID_STATUSES contains expected values', () => {
      expect(Array.isArray(Furnace.VALID_STATUSES)).toBe(true);
      expect(Furnace.VALID_STATUSES).toContain('');
      expect(Furnace.VALID_STATUSES).toContain('assigned');
      expect(Furnace.VALID_STATUSES).toContain('moved');
      expect(Furnace.VALID_STATUSES).toContain('messaged');
      expect(Furnace.VALID_STATUSES).toContain('wrong');
      expect(Furnace.VALID_STATUSES.length).toBe(5);
    });

    test('VALID_GEAR_LEVELS contains expected values', () => {
      expect(Array.isArray(Furnace.VALID_GEAR_LEVELS)).toBe(true);
      expect(Furnace.VALID_GEAR_LEVELS).toContain('Uncommon');
      expect(Furnace.VALID_GEAR_LEVELS).toContain('Epic');
      expect(Furnace.VALID_GEAR_LEVELS).toContain('Legendary T3 ***');
      expect(Furnace.VALID_GEAR_LEVELS.length).toBe(44);
    });

    test('MAX_CHARM_LEVEL is correct', () => {
      expect(Furnace.MAX_CHARM_LEVEL).toBe(16);
    });
  });

  describe('Level Validation', () => {
    test('valid levels are accepted', () => {
      Furnace.VALID_LEVELS.forEach(level => {
        expect(() => {
          new Furnace('Test', level, 100, 'R1', 2, 'both');
        }).not.toThrow();
      });
    });

    test('invalid level throws error', () => {
      expect(() => {
        new Furnace('Test', 'invalid_level', 100, 'R1', 2, 'both');
      }).toThrow('Invalid level: invalid_level');
    });

    test('case insensitive level validation', () => {
      expect(() => {
        new Furnace('Test', 'fc1', 100, 'R1', 2, 'both');
      }).not.toThrow();
    });
  });

  describe('Rank Validation', () => {
    test('valid ranks are accepted', () => {
      Furnace.VALID_RANKS.forEach(rank => {
        expect(() => {
          new Furnace('Test', 'FC1', 100, rank, 2, 'both');
        }).not.toThrow();
      });
    });

    test('invalid rank throws error', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 100, 'R6', 2, 'both');
      }).toThrow('Invalid rank: R6');
    });

    test('case insensitive rank validation', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 100, 'r1', 2, 'both');
      }).not.toThrow();
    });
  });

  describe('Trap Preference Validation', () => {
    test('valid trap preferences are accepted', () => {
      Furnace.VALID_TRAP_PREFERENCES.forEach(pref => {
        expect(() => {
          new Furnace('Test', 'FC1', 100, 'R1', 2, pref);
        }).not.toThrow();
      });
    });

    test('invalid trap preference throws error', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 100, 'R1', 2, 'invalid');
      }).toThrow('Invalid trap preference: invalid');
    });

    test('case insensitive trap preference validation', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 100, 'R1', 2, 'BOTH');
      }).not.toThrow();
    });
  });

  describe('Status Validation', () => {
    test('valid statuses are accepted', () => {
      Furnace.VALID_STATUSES.forEach(status => {
        expect(() => {
          new Furnace('Test', 'FC1', 100, 'R1', 2, 'both', null, null, null, status);
        }).not.toThrow();
      });
    });

    test('invalid status throws error', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 100, 'R1', 2, 'both', null, null, null, 'invalid_status');
      }).toThrow('Invalid status: invalid_status');
    });
  });

  describe('Power Validation', () => {
    test('valid power values are accepted', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 1, 'R1', 2, 'both');
      }).not.toThrow();

      expect(() => {
        new Furnace('Test', 'FC1', 1000, 'R1', 2, 'both');
      }).not.toThrow();
    });

    test('zero power throws error', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 0, 'R1', 2, 'both');
      }).toThrow('Power must be a positive integer');
    });

    test('negative power throws error', () => {
      expect(() => {
        new Furnace('Test', 'FC1', -10, 'R1', 2, 'both');
      }).toThrow('Power must be a positive integer');
    });
  });

  describe('Participation Validation', () => {
    test('valid participation values are accepted', () => {
      for (let i = 0; i <= 4; i++) {
        expect(() => {
          new Furnace('Test', 'FC1', 100, 'R1', i, 'both');
        }).not.toThrow();
      }

      expect(() => {
        new Furnace('Test', 'FC1', 100, 'R1', null, 'both');
      }).not.toThrow();
    });

    test('participation above 4 throws error', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 100, 'R1', 5, 'both');
      }).toThrow('Participation must be between 0 and 4');
    });

    test('negative participation throws error', () => {
      expect(() => {
        new Furnace('Test', 'FC1', 100, 'R1', -1, 'both');
      }).toThrow('Participation must be between 0 and 4');
    });
  });

  describe('Gear Level Validation', () => {
    test('valid gear levels are accepted', () => {
      Furnace.VALID_GEAR_LEVELS.forEach(level => {
        expect(() => {
          new Furnace(
            'Test', 'FC1', 100, 'R1', 2, 'both',
            null, null, null, '', false,
            level, null, null, null, null, null
          );
        }).not.toThrow();
      });
    });

    test('invalid gear level throws error', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          'Invalid Level', null, null, null, null, null
        );
      }).toThrow('Invalid gear level: Invalid Level');
    });

    test('null gear level is allowed', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null
        );
      }).not.toThrow();
    });

    test('empty gear level is allowed', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          '', null, null, null, null, null
        );
      }).not.toThrow();
    });
  });

  describe('Charms Validation', () => {
    test('valid charms are accepted', () => {
      const validCharms = [
        '1,2,3',
        '5,10,15',
        '16,16,16',
        '1,1,1'
      ];

      validCharms.forEach(charms => {
        expect(() => {
          new Furnace(
            'Test', 'FC1', 100, 'R1', 2, 'both',
            null, null, null, '', false,
            null, null, null, null, null, null,
            charms, null, null, null, null, null
          );
        }).not.toThrow();
      });
    });

    test('charms with wrong count throws error', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null,
          '1,2', null, null, null, null, null
        );
      }).toThrow('Charms must have exactly 3 comma-separated values');
    });

    test('charms with too many values throws error', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null,
          '1,2,3,4', null, null, null, null, null
        );
      }).toThrow('Charms must have exactly 3 comma-separated values');
    });

    test('charms with invalid level throws error', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null,
          '1,2,17', null, null, null, null, null
        );
      }).toThrow('Invalid charm level: 17');
    });

    test('charms with zero level throws error', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null,
          '1,0,3', null, null, null, null, null
        );
      }).toThrow('Invalid charm level: 0');
    });

    test('charms with non-numeric value throws error', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null,
          '1,abc,3', null, null, null, null, null
        );
      }).toThrow('Invalid charm level: abc');
    });

    test('null charms is allowed', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null,
          null, null, null, null, null, null
        );
      }).not.toThrow();
    });

    test('empty charms is allowed', () => {
      expect(() => {
        new Furnace(
          'Test', 'FC1', 100, 'R1', 2, 'both',
          null, null, null, '', false,
          null, null, null, null, null, null,
          '', null, null, null, null, null
        );
      }).not.toThrow();
    });
  });

  describe('Setter Validation', () => {
    let furnace;

    beforeEach(() => {
      furnace = new Furnace('Test', 'FC1', 100, 'R1', 2, 'both');
    });

    test('valid setter calls work', () => {
      expect(() => {
        furnace.setLevel('FC2');
        furnace.setRank('R3');
        furnace.setTrapPref('n/a');
        furnace.setPower(150);
        furnace.setParticipation(3);
        furnace.setStatus('moved');
        furnace.setCapLevel('Epic');
        furnace.setCapCharms('5,6,7');
      }).not.toThrow();
    });

    test('invalid setter calls throw errors', () => {
      expect(() => {
        furnace.setLevel('invalid');
      }).toThrow('Invalid level: invalid');

      expect(() => {
        furnace.setRank('R6');
      }).toThrow('Invalid rank: R6');

      expect(() => {
        furnace.setTrapPref('invalid');
      }).toThrow('Invalid trap preference: invalid');

      expect(() => {
        furnace.setPower(0);
      }).toThrow('Power must be a positive integer');

      expect(() => {
        furnace.setParticipation(5);
      }).toThrow('Participation must be between 0 and 4');

      expect(() => {
        furnace.setStatus('invalid');
      }).toThrow('Invalid status: invalid');

      expect(() => {
        furnace.setCapLevel('Invalid Level');
      }).toThrow('Invalid gear level: Invalid Level');

      expect(() => {
        furnace.setCapCharms('1,2');
      }).toThrow('Charms must have exactly 3 comma-separated values');
    });
  });

  describe('Validation Methods', () => {
    let furnace;

    beforeEach(() => {
      furnace = new Furnace('Test', 'FC1', 100, 'R1', 2, 'both');
    });

    test('validateLevel method works correctly', () => {
      expect(furnace.validateLevel('FC1')).toBe('FC1');
      expect(() => furnace.validateLevel('invalid')).toThrow('Invalid level: invalid');
    });

    test('validateRank method works correctly', () => {
      expect(furnace.validateRank('R1')).toBe('R1');
      expect(() => furnace.validateRank('R6')).toThrow('Invalid rank: R6');
    });

    test('validateTrapPref method works correctly', () => {
      expect(furnace.validateTrapPref('both')).toBe('both');
      expect(() => furnace.validateTrapPref('invalid')).toThrow('Invalid trap preference: invalid');
    });

    test('validatePower method works correctly', () => {
      expect(furnace.validatePower(100)).toBe(100);
      expect(() => furnace.validatePower(0)).toThrow('Power must be a positive integer');
    });

    test('validateParticipation method works correctly', () => {
      expect(furnace.validateParticipation(2)).toBe(2);
      expect(furnace.validateParticipation(null)).toBe(null);
      expect(() => furnace.validateParticipation(5)).toThrow('Participation must be between 0 and 4');
    });

    test('validateStatus method works correctly', () => {
      expect(furnace.validateStatus('assigned')).toBe('assigned');
      expect(() => furnace.validateStatus('invalid')).toThrow('Invalid status: invalid');
    });

    test('validateGearLevel method works correctly', () => {
      expect(furnace.validateGearLevel('Epic')).toBe('Epic');
      expect(furnace.validateGearLevel(null)).toBe(null);
      expect(furnace.validateGearLevel('')).toBe(null);
      expect(() => furnace.validateGearLevel('Invalid')).toThrow('Invalid gear level: Invalid');
    });

    test('validateCharms method works correctly', () => {
      expect(furnace.validateCharms('1,2,3')).toBe('1,2,3');
      expect(furnace.validateCharms(null)).toBe(null);
      expect(furnace.validateCharms('')).toBe(null);
      expect(() => furnace.validateCharms('1,2')).toThrow('Charms must have exactly 3 comma-separated values');
      expect(() => furnace.validateCharms('1,2,17')).toThrow('Invalid charm level: 17');
    });
  });
});
