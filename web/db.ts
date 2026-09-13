
import { createClient, type InValue } from "@libsql/client";
import type { StoredAsset } from "./asset.ts";

const db = createClient({
    url: Bun.env.DB_URL ?? "file:./data/3d1.db",
    authToken: Bun.env.DB_AUTH_TOKEN, // undefined locally, required for Bunny Database
});

export async function runQuery(sql: string, params: InValue[] = []) {
    const result = await db.execute({ sql, args: params });
    return result.rows;
}

export async function insertDemoAsset(title: string) {
    await runQuery("INSERT INTO Asset (title) VALUES (?)", [title]);
}

export async function getAssets(): Promise<StoredAsset[]> {
    const rows = await runQuery("SELECT id, title,  clicks FROM Asset ORDER BY id");
    return rows.map((r) => ({
        id: r.id as number,
        title: r.title as string,
        type: "other",
        creatorId: 0,
        clicks: r.clicks as number,
    } as StoredAsset));
}