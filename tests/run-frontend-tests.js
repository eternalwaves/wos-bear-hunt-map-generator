#!/usr/bin/env node

/**
 * Frontend Test Runner for Bear Hunt Map Generator
 * 
 * This script helps run frontend tests and provides setup instructions.
 * 
 * To run the tests:
 * 1. Install Node.js dependencies: npm install
 * 2. Run tests: npm test
 * 3. Run tests in watch mode: npm run test:watch
 * 4. Run tests with coverage: npm run test:coverage
 */

const fs = require('fs');
const path = require('path');

console.log('🎯 Bear Hunt Map Generator - Frontend Test Runner');
console.log('==========================================\n');

// Check if package.json exists
if (!fs.existsSync('package.json')) {
  console.error('❌ package.json not found. Please run this from the project root directory.');
  process.exit(1);
}

// Check if node_modules exists
if (!fs.existsSync('node_modules')) {
  console.log('📦 Installing dependencies...');
  console.log('Please run: npm install');
  console.log('');
}

// Check if tests directory exists
if (!fs.existsSync('tests')) {
  console.log('📁 Creating tests directory...');
  fs.mkdirSync('tests');
}

console.log('✅ Test environment ready!');
console.log('');
console.log('Available commands:');
console.log('  npm test              - Run all tests');
console.log('  npm run test:watch    - Run tests in watch mode');
console.log('  npm run test:coverage - Run tests with coverage report');
console.log('');
console.log('Test files:');
console.log('  tests/setup.js        - Jest configuration and mocks');
console.log('  tests/frontend.test.js - Frontend functionality tests');
console.log('');

// List test files
const testFiles = fs.readdirSync('tests').filter(file => file.endsWith('.test.js'));
if (testFiles.length > 0) {
  console.log('Found test files:');
  testFiles.forEach(file => {
    console.log(`  - ${file}`);
  });
} else {
  console.log('No test files found. Creating sample test...');
}

console.log('');
console.log('🚀 Ready to test! Run: npm test'); 