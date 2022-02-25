CREATE DATABASE IF NOT EXISTS ratchet_sync;
CONNECT ratchet_sync;

CREATE USER IF NOT EXISTS 'cli_server'@'localhost' IDENTIFIED BY 'CHANGETHIS';

CREATE TABLE IF NOT EXISTS DataUpDto (
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

GRANT INSERT ON DataUpDto TO 'cli_server'@'%' WITH GRANT OPTION;

CREATE TABLE IF NOT EXISTS gwInfo (
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

GRANT INSERT ON gwInfo TO 'cli_server'@'%' WITH GRANT OPTION;

CREATE TABLE IF NOT EXISTS Water_Meters (
    id  	        MEDIUMINT AUTO_INCREMENT,

    endDevice_devEui        CHAR(50),
    endDevice_devAddr       CHAR(50),
    meterNumber				BIGINT,

    PRIMARY KEY (id)
);

GRANT SELECT ON Water_Meters TO 'cli_server'@'%' WITH GRANT OPTION;

CREATE OR REPLACE VIEW METER_READINGS AS
SELECT
    d.id,
    d.message_id,
    d.adr,
    d.classB,
    d.delayed,
    d.encodingType,
    d.endDevice_devEui,
    d.endDevice_devAddr,
    d.endDevice_cluster_id,
    w.meterNumber as meter_number,
    d.fCntDown,
    d.fCntUp,
    d.fPort,
    d.payload,
    SUBSTRING(d.payload,1,2) as control_code,
    SUBSTRING(d.payload,3,2) as `length`,
    SUBSTRING(d.payload,5,4) as D10_D11,
    SUBSTRING(d.payload,9,2) as count_number,
    SUBSTRING(d.payload,11,2) as unit,
    CAST(INSERT(HEX(REVERSE(UNHEX(SUBSTRING(d.payload,13,8)))),6,0,'.') as DECIMAL(8,3)) as current_reading,
    SUBSTRING(d.payload,21,2) as valve_status,
    SUBSTRING(d.payload,23,2) as alarm,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),1,1) as transducer_error,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),2,1) as leakage_alarm,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),3,1) as ee_error,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),4,1) as temperature_alarm,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),5,1) as over_range_alarm,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),6,1) as reverse_flow_alarm,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),7,1) as empty_pipe_alarm,
    SUBSTRING(LPAD(CONV(SUBSTRING(payload,23,2), 16, 2),8,0),8,1) as meter_battery_alarm,
    cast((CONV(SUBSTRING(d.payload,25,2),16,10)-1)/253 as DECIMAL(4,3)) as battery,
    d.received_in
FROM DataUpDto d
LEFT JOIN Water_Meters w on w.endDevice_devEui = d.endDevice_devEui;

GRANT SELECT ON METER_READINGS TO 'cli_server'@'%' WITH GRANT OPTION;

DELIMITER $$
CREATE TRIGGER WM_MR_MA_Trigger AFTER INSERT ON DataUpDto
  FOR EACH ROW
  BEGIN

    INSERT INTO Water_Meters (endDevice_devEui, endDevice_devAddr, meterNumber)
    SELECT d.endDevice_devEui, d.endDevice_devAddr,
        (CASE WHEN (SELECT MAX(meterNumber) FROM Water_Meters) IS NULL THEN 2100000 ELSE (SELECT MAX(meterNumber) FROM Water_Meters) END)+1
    FROM DataUpDto d
    LEFT JOIN Water_Meters w on w.endDevice_devEui = d.endDevice_devEui
    WHERE w.endDevice_devEui IS NULL and d.endDevice_devEui = NEW.endDevice_devEui
    LIMIT 1;

  END;
$$

ALTER TABLE DataUpDto ADD INDEX (endDevice_devEui);
ALTER TABLE Water_Meters ADD INDEX (endDevice_devEui);

--select cast((CONV(battery,16,10)-1)/253 as DECIMAL(4,3)) from meter_readings
/*SHOW INDEX FROM DataUpDto;
SHOW INDEX FROM Water_Meters;
SHOW INDEX FROM meter_readings;
SHOW INDEX FROM meter_alarms;

ALTER TABLE DataUpDto ADD INDEX (endDevice_devEui);
ALTER TABLE Water_Meters ADD INDEX (endDevice_devEui);
ALTER TABLE meter_readings ADD INDEX (endDevice_devEui);*/