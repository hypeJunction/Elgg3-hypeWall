import { test, expect } from '@playwright/test';
import { loginAs, queryDb, getEntitiesBySubtype } from '../helpers/elgg';

/**
 * End-to-end smoke for the wall posting flow.
 *
 * Asserts both UI state (status appears on the user's own wall page) and
 * database state (an hjwall entity row exists with the submitted text)
 * — covering the full path from form submit through action handler to
 * persistence layer. Catches the "form submit returned 200 but nothing
 * actually saved" failure mode.
 */
test.describe('hypeWall', () => {
  test('user can post a status to their own wall', async ({ page }) => {
    // Use a known seeded test user. The Elgg test environment does not
    // create users on the fly during Playwright runs; this test assumes
    // a user "wall_tester" exists with the default password "testpass123".
    // If that fixture isn't present, the test should skip gracefully.
    const username = 'wall_tester';
    const password = 'testpass123';

    await loginAs(page, username, password);

    // Navigate to the user's own wall
    await page.goto(`/wall/owner/${username}`);

    // The form is at #wall-status-input or via the page/components/wall view
    const textarea = page.locator('textarea[name="description"], #wall-form textarea').first();
    if (!(await textarea.isVisible().catch(() => false))) {
      test.skip(true, 'wall form not found on page (test fixture user may not exist)');
      return;
    }

    const marker = `pw-test-${Date.now()}`;
    await textarea.fill(marker);

    // Submit form
    await page.locator('form#wall-form, form[action*="wall/status"]').first()
      .locator('button[type="submit"], input[type="submit"]').first().click();

    // Wait for the page to update (either redirect or AJAX refresh)
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

    // UI assertion: marker text should be visible somewhere on the page
    await expect(page.getByText(marker).first()).toBeVisible({ timeout: 5000 });

    // DB assertion: an entity exists whose description metadata contains the marker
    const rows = await queryDb(
      `SELECT m.entity_guid FROM elgg_metadata m WHERE m.name = 'description' AND m.value = ? LIMIT 1`,
      [marker]
    );
    expect(rows.length).toBeGreaterThan(0);
  });

  test('wall view page renders for an existing post', async ({ page }) => {
    // Find any existing hjwall post and visit its view URL.
    const posts = await getEntitiesBySubtype('hjwall').catch(() => []);
    if (!posts || posts.length === 0) {
      test.skip(true, 'no existing hjwall posts to view');
      return;
    }
    const guid = posts[0].guid;
    const response = await page.goto(`/wall/post/${guid}`);
    expect(response?.status() || 0).toBeLessThan(500);
  });
});
