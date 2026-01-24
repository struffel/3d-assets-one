CREATE TABLE "Asset" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"creatorGivenId" TEXT DEFAULT NULL,
	"url" TEXT NOT NULL UNIQUE,
	"title" TEXT NOT NULL,
	"state" INTEGER NOT NULL,
	"date" TEXT DEFAULT NULL,
	"clicks" INTEGER DEFAULT NULL,
	"typeId" INTEGER NOT NULL,
	"creatorId" INTEGER NOT NULL,
	"lastSuccessfulValidation" TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX "creatorGivenIdIndex" ON "Asset" ("creatorId", "creatorGivenId");
CREATE UNIQUE INDEX "urlIndex" ON "Asset" ("url");
CREATE INDEX "stateIndex" ON "Asset" ("state");
CREATE INDEX "typeIdIndex" ON "Asset" ("typeId");
CREATE INDEX "creatorIdIndex" ON "Asset" ("creatorId");

CREATE TABLE "FetchingState" (
	"creatorId" INTEGER NOT NULL,
	"stateKey" TEXT NOT NULL,
	"stateValue" TEXT,
	PRIMARY KEY ("creatorId", "stateKey")
);

CREATE TABLE "Tag" (
	"id" INTEGER NOT NULL,
	"tag" TEXT NOT NULL,
	PRIMARY KEY ("id", "tag"),
	FOREIGN KEY ("id") REFERENCES "Asset" ("id") ON DELETE CASCADE
);

CREATE INDEX "tagIndex" ON "Tag" ("tag");
CREATE INDEX "assetIdIndex" ON "Tag" ("id");

CREATE TABLE "Event" (
	"eventId" INTEGER PRIMARY KEY AUTOINCREMENT,
	"eventType" INTEGER NOT NULL,
	"eventTimestamp" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	"eventDetails" TEXT
);

CREATE INDEX "eventTypeIndex" ON "Event" ("eventType");
CREATE INDEX "eventTimestampIndex" ON "Event" ("eventTimestamp");
