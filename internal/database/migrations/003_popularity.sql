ALTER TABLE "Asset" ADD COLUMN "popularityScore" REAL DEFAULT 0;
CREATE INDEX "popularityScoreIndex" ON "Asset" ("popularityScore");
