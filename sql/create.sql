CREATE DATABASE IF NOT EXISTS ratchet_sync;
CREATE TABLE IF NOT EXISTS ratchet_sync.DataUpDto (
	id 			            MEDIUMINT AUTO_INCREMENT,

    message_id              CHAR(50),
    adr                     BOOLEAN,
    classB                  BOOLEAN,
    codingRate              CHAR(25),
    confirmed               BOOLEAN,
    dataRate                CHAR(25),
    `delayed`         		BOOLEAN,
    encodingType            CHAR(25),
    encrypted               BOOLEAN,
    endDevice_devEui        CHAR(50),
    endDevice_devAddr       CHAR(50),
    endDevice_cluster_id    BIGINT,
    fCntDown                BIGINT,
    fCntUp                  BIGINT,
    fPort                   BIGINT,
    gwCnt                   BIGINT,
    gwRecvTime              BIGINT,
    modulation              CHAR(25),
    payload                 CHAR(200),
    recvTime                BIGINT,
    ulFrequency             DECIMAL(20,6),

    received_in             DATETIME(6),

	PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS ratchet_sync.gwInfo (
    id  	        MEDIUMINT AUTO_INCREMENT,

    gwEui           CHAR(50),
    rfRegion        CHAR(50),
    rssi            CHAR(50),
    snr             BIGINT,
    latitude        BIGINT,
    longitude       BIGINT,
    channel         BIGINT,
    radioId         BIGINT,

    DataUpDto_id    MEDIUMINT,
    
	PRIMARY KEY (id),
    FOREIGN KEY (DataUpDto_id)
        REFERENCES DataUpDto(id)
        ON DELETE CASCADE
);

CREATE USER IF NOT EXISTS 'cli_server'@'localhost' IDENTIFIED BY 'CHANGETHIS';
GRANT INSERT ON ratchet_sync.DataUpDto TO 'cli_server'@'localhost' WITH GRANT OPTION;
GRANT INSERT ON ratchet_sync.gwInfo TO 'cli_server'@'localhost' WITH GRANT OPTION;

CREATE TABLE IF NOT EXISTS ratchet_sync.Water_Meters (
    id  	        MEDIUMINT AUTO_INCREMENT,

    endDevice_devEui        CHAR(50),
    endDevice_devAddr       CHAR(50),
    meterNumber				BIGINT,

    PRIMARY KEY (id)
);

DELIMITER $$
CREATE TRIGGER WM_Trigger AFTER INSERT ON ratchet_sync.DataUpDto
  FOR EACH ROW
  BEGIN
    INSERT INTO ratchet_sync.Water_Meters (endDevice_devEui, endDevice_devAddr, meterNumber)
    SELECT d.endDevice_devEui, d.endDevice_devAddr,
        (CASE WHEN (SELECT MAX(meterNumber) FROM ratchet_sync.Water_Meters) IS NULL THEN 2100000 ELSE (SELECT MAX(meterNumber) FROM ratchet_sync.Water_Meters) END)+1
    FROM ratchet_sync.DataUpDto d
    LEFT JOIN ratchet_sync.Water_Meters w on w.endDevice_devEui = d.endDevice_devEui
    WHERE w.endDevice_devEui IS NULL and d.endDevice_devEui = NEW.endDevice_devEui
   LIMIT 1;
  END;
$$  