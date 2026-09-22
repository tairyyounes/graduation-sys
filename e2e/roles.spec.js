import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:8000';

test.describe('Role-Based E2E Browser Testing', () => {

  test('01. Admin Login and Dashboard Navigation', async ({ page }) => {
    // 1. Navigate to Login
    await page.goto(`${BASE_URL}/login`);
    await expect(page).toHaveTitle(/(Laravel|ProposalGuard AI|Log in)/);

    // 2. Fill Admin Credentials
    await page.fill('#email', 'testadmin@example.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');

    // 3. Verify Admin Dashboard Redirection
    await page.waitForURL('**/admin/dashboard**', { timeout: 15000 });
    expect(page.url()).toContain('/admin/dashboard');

    // 4. Verify Vue App Container is Mounted
    const dashboardApp = page.locator('#admin-dashboard');
    await expect(dashboardApp).toBeVisible();

    // 5. Screenshot
    await page.screenshot({ path: 'selenium_tests/screenshots/admin_dashboard.png' });
  });

  test('02. Department Head Login and Dashboard Access', async ({ page }) => {
    // 1. Navigate to Login
    await page.goto(`${BASE_URL}/login`);

    // 2. Fill Dept Head Credentials
    await page.fill('#email', 'testhead@example.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');

    // 3. Verify Dept Dashboard Redirection
    await page.waitForURL('**/department/dashboard**', { timeout: 15000 });
    expect(page.url()).toContain('/department/dashboard');

    // 4. Verify Department Vue App Container
    const dashboardApp = page.locator('#department-dashboard');
    await expect(dashboardApp).toBeVisible();

    // 5. Screenshot
    await page.screenshot({ path: 'selenium_tests/screenshots/dept_head_dashboard.png' });
  });

  test('03. Department Member Login and Access Control', async ({ page }) => {
    // 1. Navigate to Login
    await page.goto(`${BASE_URL}/login`);

    // 2. Fill Dept Member Credentials
    await page.fill('#email', 'testmember@example.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');

    // 3. Verify Redirection
    await page.waitForURL('**/department/dashboard**', { timeout: 15000 });
    expect(page.url()).toContain('/department/dashboard');

    // 4. Verify Department Vue App Container
    const dashboardApp = page.locator('#department-dashboard');
    await expect(dashboardApp).toBeVisible();

    // 5. Screenshot
    await page.screenshot({ path: 'selenium_tests/screenshots/dept_member_dashboard.png' });
  });

});
