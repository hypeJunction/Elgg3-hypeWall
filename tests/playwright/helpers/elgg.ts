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
  await page.goto('/login');
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForURL(/\/$|\/dashboard|\/index/, { timeout: 10000 }).catch(() => {});
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
