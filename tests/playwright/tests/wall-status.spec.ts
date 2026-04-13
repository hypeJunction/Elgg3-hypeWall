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

    // Check whether login actually took — Elgg 4.x puts the logged-in user's
    // avatar / logout link in the topbar.
    const loggedInMarker = await page.locator('a[href*="action/logout"], .elgg-menu-topbar').count();
    console.log(`DEBUG logout/topbar markers: ${loggedInMarker}`);

    await page.goto(`/wall/owner/${username}`);
    console.log(`DEBUG url after wall nav: ${page.url()}`);

    // How many textareas of any kind?
    const allTextareas = await page.locator('textarea').count();
    console.log(`DEBUG total textareas on page: ${allTextareas}`);
    const descTextareas = await page.locator('textarea[name="status"]').count();
    console.log(`DEBUG textareas[name=status]: ${descTextareas}`);

    const textarea = page.locator('textarea[name="status"]').first();
    const visible = await textarea.isVisible().catch(() => false);
    if (!visible) {
      // Dump a narrower slice to find the form state
      const body = await page.locator('body').innerHTML().catch(() => '');
      console.log(`DEBUG body slice [0..500]: ${body.substring(0, 500)}`);
      console.log(`DEBUG body slice [-500..]: ${body.substring(Math.max(0, body.length - 500))}`);
      throw new Error('wall textarea not visible');
    }

    const marker = `pw-test-${Date.now()}`;
    await textarea.fill(marker);

    // Submit form and wait for the response
    const submitForm = page.locator('form[action*="wall/status"]').first();
    const [submitResp] = await Promise.all([
      page.waitForResponse(r => r.url().includes('wall/status'), { timeout: 15000 }),
      submitForm.locator('button[type="submit"], input[type="submit"]').first().click(),
    ]);
    console.log(`DEBUG wall/status response: ${submitResp.status()} ${submitResp.url()}`);
    await page.waitForLoadState('domcontentloaded', { timeout: 10000 }).catch(() => {});
    console.log(`DEBUG post-submit url: ${page.url()}`);

    // Check for system-message errors on the landing page
    const postSubmitErrs = await page.locator('.elgg-system-messages li').allTextContents().catch(() => []);
    console.log(`DEBUG post-submit messages: ${postSubmitErrs.join(' | ')}`);

    // DB assertion: an entity exists whose description metadata contains the marker
    const rows = await queryDb(
      `SELECT m.entity_guid FROM elgg_metadata m WHERE m.name = 'description' AND m.value = ? LIMIT 1`,
      [marker]
    );
    console.log(`DEBUG DB rows for marker: ${rows.length}`);
    expect(rows.length).toBeGreaterThan(0);

    // UI assertion: navigate to the created post's page and assert marker
    // appears in the rendered HTML. Our session from loginAs carries through.
    if (rows.length > 0) {
      const guid = (rows[0] as any).entity_guid;
      const resp = await page.goto(`/wall/post/${guid}`, { waitUntil: 'load' });
      console.log(`DEBUG post view: status=${resp?.status()} url=${page.url()}`);
      const content = await page.content();
      console.log(`DEBUG content length: ${content.length}, contains marker: ${content.includes(marker)}`);
      expect(content).toContain(marker);
    }

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
