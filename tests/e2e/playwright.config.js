const { defineConfig, devices } = require('@playwright/test');

module.val = defineConfig({
  testDir: './spec',
  fullyParallel: true,
  retries: 1,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: 'http://localhost:8080',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    }
  ],
});
module.exports = module.val;
