-- Written for MySQL 8.0
-- Drop the tables if they exist (optional).
DROP TABLE IF EXISTS Tag;

DROP TABLE IF EXISTS Asset;

DROP TABLE IF EXISTS IndexingState;

-- Create the Asset table.
CREATE TABLE IF NOT EXISTS Asset (
	assetId INT NOT NULL AUTO_INCREMENT,
	assetUrl VARCHAR(255) NOT NULL,
	assetThumbnailUrl VARCHAR(255),
	assetName VARCHAR(255) NOT NULL,
	assetActive INT NOT NULL,
	assetDate DATE,
	assetClicks INT DEFAULT 0,
	licenseId INT NOT NULL,
	typeId INT NOT NULL,
	creatorId INT NOT NULL,
	lastSuccessfulValidation DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (assetId)
);

-- Create the Tag table.
CREATE TABLE IF NOT EXISTS Tag (
	assetId INT NOT NULL,
	tagName VARCHAR(255) NOT NULL,
	PRIMARY KEY (assetId, tagName),
	FOREIGN KEY (assetId) REFERENCES Asset(assetId) ON DELETE CASCADE
);

-- Create the IndexingState table.
CREATE TABLE IF NOT EXISTS IndexingState (
	creatorId INT,
	stateKey VARCHAR(64),
	stateValue TEXT,
	PRIMARY KEY (creatorId, stateKey)
) 


-- Create the IndexingEvent table. 
Create TABLE IndexingEvent(
	eventId BIGINT PRIMARY KEY AUTO_INCREMENT,
	eventTime DATETIME NOT NULL,
	eventType INT NOT NULL,
	eventAffectedAssetId INT,
	eventAffectedCreatorId INT,
	eventAffectedUrl VARCHAR(500)
);