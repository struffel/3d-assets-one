-- Written for MySQL 8.0

CREATE TABLE Asset (
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

CREATE TABLE Tag (
    assetId INT NOT NULL,
    tagName VARCHAR(255) NOT NULL,
    PRIMARY KEY (assetId, tagName),
    FOREIGN KEY (assetId) REFERENCES Asset(assetId)
);
