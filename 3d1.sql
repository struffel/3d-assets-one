-- Written for MySQL 8.0

-- Drop the tables if they exist (optional).
DROP TABLE IF EXISTS Tag;
DROP TABLE IF EXISTS Asset;

-- Create the Asset table.
CREATE TABLE IF NOT EXISTS Asset (
    assetId INT NOT NULL AUTO_INCREMENT,
    assetUrl VARCHAR(255) NOT NULL,
    assetThumbnailUrl VARCHAR(255),
    assetName VARCHAR(255) NOT NULL,
    assetActive INT NOT NULL,
    assetDate DATE,
    assetClicks INT,
    licenseId INT NOT NULL,
    typeId INT NOT NULL,
    creatorId INT NOT NULL,
    PRIMARY KEY (assetId)
);

-- Create the Tag table.
CREATE TABLE IF NOT EXISTS Tag (
    assetId INT NOT NULL,
    tagName VARCHAR(255) NOT NULL,
    PRIMARY KEY (assetId, tagName),
    FOREIGN KEY (assetId) REFERENCES Asset(assetId) ON DELETE CASCADE
);
