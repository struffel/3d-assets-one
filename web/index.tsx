import { Hono } from "hono";
import { Header, SearchForm, AssetList } from "./components.tsx";
import type { StoredAsset } from "./asset.ts";
import { getAssets, insertDemoAsset } from "./db.ts";

const app = new Hono();
app.get("/",  async (c) => {
  // await insertDemoAsset("Paving Stones");
  const assets = await getAssets();
  return c.html(
    <>
      <Header siteName="3Dassets.one" />
      <SearchForm />
      <AssetList assets={assets} />
    </>
  );
});

app.get("/search", (c) => {
  const q = c.req.query("q"); // string | undefined
  console.log("search query:", q);
  const creators = c.req.queries("creator"); // string[] | undefined — for creator[]=a&creator[]=b
  console.log("search creators:", creators);
  return c.json({ q, creators });
});

app.get("/go/:id", (c) => {
  const id = parseInt(c.req.param("id")); // always a string — you'll parseInt() it yourself
  return c.text(`Would redirect for asset ${id}`);
});

const port = Number(Bun.env.PORT ?? 7000);
const server = Bun.serve({
  port: port,
  fetch: app.fetch,
});

console.log(`Listening on http://localhost:${server.port}`);