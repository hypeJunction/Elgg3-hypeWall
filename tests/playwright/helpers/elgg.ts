import { Page } from '@playwright/test';
import mysql from 'mysql2/promise';

const DB_CONFIG = {
  host: process.env.ELGG_DB_HOST || 'db',
  port: Number(process.env.ELGG_DB_PORT || 3306),
  user: process.env.ELGG_DB_USER || 'elgg',
  password: process.env.ELGG_DB_PASS || 'elgg',
  database: process.env.ELGG_DB_NAME || 'elgg',
};

export async function loginAs(page: Page, username: string, password: string = 'testpass123') {
  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  // Elgg renders two login forms: one in topbar (hidden), one on the main
  // resource view. Pick the visible one by filtering on visibility.
  const forms = page.locator('form[action*="action/login"]');
  const formCount = await forms.count();
  console.log(`DEBUG login form count: ${formCount}`);
  let form = null;
  for (let i = 0; i < formCount; i++) {
    const candidate = forms.nth(i);
    if (await candidate.isVisible().catch(() => false)) {
      form = candidate;
      break;
    }
  }
  if (!form) {
    // Fallback: last form on page (usually the main-body one)
    form = forms.last();
  }
  const formAction = await form.getAttribute('action');
  console.log(`DEBUG login form action: ${formAction}`);
  await form.locator('input[name="username"]').fill(username);
  await form.locator('input[name="password"]').fill(password);
  const [response] = await Promise.all([
    page.waitForResponse(resp => resp.url().includes('action/login'), { timeout: 15000 }),
    form.locator('button[type="submit"], input[type="submit"]').first().click(),
  ]);
  console.log(`DEBUG login response: ${response.status()} ${response.url()}`);
  // Wait for redirect chain to complete before querying the page.
  await page.waitForLoadState('load', { timeout: 15000 }).catch(() => {});
  await page.waitForLoadState('domcontentloaded', { timeout: 5000 }).catch(() => {});
  const errors = await page.locator('.elgg-message-error, .elgg-system-messages li').allTextContents().catch(() => []);
  if (errors.length > 0) {
    console.log(`DEBUG system messages: ${errors.join(' | ')}`);
  }
  const authMarker = await page.locator('a[href*="action/logout"]').count();
  console.log(`DEBUG logout marker count: ${authMarker}`);
  if (authMarker === 0) {
    throw new Error(`loginAs: login did not succeed for ${username}`);
  }
}

export async function queryDb(sql: string, params: any[] = []) {
  const conn = await mysql.createConnection(DB_CONFIG);
  const [rows] = await conn.execute(sql, params);
  await conn.end();
  return rows as any[];
}

export async function getEntitiesBySubtype(subtype: string, ownerGuid?: number) {
  // Elgg 3.x: subtype is an integer FK to elgg_entity_subtypes
  // Elgg 4.x: subtype is a string column on elgg_entities
  // Try the 4.x path first; fall back to the 3.x join.
  let sql: string;
  const params: any[] = [subtype];
  try {
    sql = 'SELECT * FROM elgg_entities WHERE subtype = ?';
    if (ownerGuid != null) { sql += ' AND owner_guid = ?'; params.push(ownerGuid); }
    return await queryDb(sql, params);
  } catch (e) {
    sql = `
      SELECT e.* FROM elgg_entities e
      JOIN elgg_entity_subtypes s ON s.id = e.subtype
      WHERE s.subtype = ?
    `;
    if (ownerGuid != null) { sql += ' AND e.owner_guid = ?'; params.push(ownerGuid); }
    return await queryDb(sql, params);
  }
}
