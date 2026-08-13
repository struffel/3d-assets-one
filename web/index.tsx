import { Hono } from "hono";

const app = new Hono();

app.get("/", (c) => {
  return c.html(`
    <html>
      <body>
        <form id="asset-filters-form">
          <input type="text" name="q" placeholder="search tags..." />
        </form>
        <main>Loading...</main>
      </body>
    </html>
  `);
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