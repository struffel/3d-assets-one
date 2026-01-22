CREATE TABLE "Asset" (
	"assetId" INTEGER PRIMARY KEY AUTOINCREMENT,
	"assetCreatorGivenId" TEXT DEFAULT NULL,
	"assetUrl" TEXT NOT NULL UNIQUE,
	"assetName" TEXT NOT NULL,
	"assetState" INTEGER NOT NULL,
	"assetDate" TEXT DEFAULT NULL,
	"assetClicks" INTEGER DEFAULT NULL,
	"typeId" INTEGER NOT NULL,
	"creatorId" INTEGER NOT NULL,
	"lastSuccessfulValidation" TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE "FetchingState" (
	"creatorId" INTEGER NOT NULL,
	"stateKey" TEXT NOT NULL,
	"stateValue" TEXT,
	PRIMARY KEY ("creatorId", "stateKey")
);

CREATE TABLE "Tag" (
	"assetId" INTEGER NOT NULL,
	"tagName" TEXT NOT NULL,
	PRIMARY KEY ("assetId", "tagName"),
	FOREIGN KEY ("assetId") REFERENCES "Asset" ("assetId") ON DELETE CASCADE
);

CREATE INDEX "tagIndex" ON "Tag" ("tagName");

CREATE INDEX "assetIdIndex" ON "Tag" ("assetId");

CREATE TABLE "Event" (
	"eventId" INTEGER PRIMARY KEY AUTOINCREMENT,
	"eventType" INTEGER NOT NULL,
	"eventTimestamp" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	"eventDetails" TEXT
);

CREATE INDEX "eventTypeIndex" ON "Event" ("eventType");

CREATE INDEX "eventTimestampIndex" ON "Event" ("eventTimestamp");