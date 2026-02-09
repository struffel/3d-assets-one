CREATE INDEX "dateIndex" ON "Asset" ("date" DESC);
CREATE INDEX "sortByLatestIndex" ON "Asset" ("state", "date" DESC, "id" DESC);
CREATE INDEX "sortByOldestIndex" ON "Asset" ("state", "date" ASC, "id" ASC);
