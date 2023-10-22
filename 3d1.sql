CREATE TABLE "Asset" (
	"assetId"	INTEGER NOT NULL UNIQUE,
	"assetUrl"	INTEGER NOT NULL UNIQUE,
	"assetName"	INTEGER NOT NULL,
	"assetActive"	INTEGER NOT NULL,
	"assetDate"	INTEGER,
	"assetClicks"	INTEGER,
	"licenseId"	INTEGER NOT NULL,
	"typeId"	INTEGER NOT NULL,
	"creatorId"	INTEGER NOT NULL,
	PRIMARY KEY("assetId" AUTOINCREMENT)
);

CREATE TABLE "Tag" (
	"assetId"	INTEGER NOT NULL,
	"tagName"	TEXT NOT NULL,
	PRIMARY KEY("assetId","tagName"),
	FOREIGN KEY("assetId") REFERENCES "Asset"("assetId")
);

