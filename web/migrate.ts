import { runQuery } from "./db.ts";

await runQuery(`
  CREATE TABLE IF NOT EXISTS Asset (
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    clicks INTEGER NOT NULL DEFAULT 0
  )
`);

console.log("Migration complete");