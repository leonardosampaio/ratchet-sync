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

--para cada banco
CREATE TABLE IF NOT EXISTS Water_Meters (
    id  	        MEDIUMINT AUTO_INCREMENT,

    endDevice_devEui        CHAR(50),
    endDevice_devAddr       CHAR(50),
    meterNumber				BIGINT,

    PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS meter_readings (
    id  	        MEDIUMINT AUTO_INCREMENT,

    endDevice_devEui        CHAR(50),
    meter_no				BIGINT,
    payload                 CHAR(200),
    control_code            CHAR(2),
    `length`                CHAR(2),
    D10_D11                 CHAR(4),
    count_number            CHAR(2),
    unit                    CHAR(2),
    current_reading         DECIMAL(20,3),
    valve_status            CHAR(2),
    alarm                   CHAR(2),
    battery                 DECIMAL(4,3),

    received_in             DATETIME(6),

    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS meter_alarms (
    id  	        MEDIUMINT AUTO_INCREMENT,

    endDevice_devEui        CHAR(50),
    meter_no				BIGINT,
    alarm                   CHAR(2),

    transducer_error        CHAR(1),
    leakage_alarm           CHAR(1),
    ee_error                CHAR(1),
    temperature_alarm       CHAR(1),
    over_range_alarm        CHAR(1),
    reverse_flow_alarm      CHAR(1),
    empty_pipe_alarm        CHAR(1),
    meter_battery_alarm     CHAR(1),

    received_in             DATETIME(6),

    PRIMARY KEY (id)
);

DROP TRIGGER WM_MR_Trigger;
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

    INSERT INTO meter_readings (
        endDevice_devEui,
        meter_no,
        payload,
        control_code,
        `length`,
        D10_D11,
        count_number,
        unit,
        current_reading,
        valve_status,
        alarm,
        battery,
        received_in
        )
    SELECT
        endDevice_devEui,
        meter_no,
        payload,
        SUBSTRING(payload,1,2) as control_code,
        SUBSTRING(payload,3,2) as length,
        SUBSTRING(payload,5,4) as D10_D11,
        SUBSTRING(payload,9,2) as count_number,
        SUBSTRING(payload,11,2) as unit,
        CAST(INSERT(HEX(REVERSE(UNHEX(SUBSTRING(payload,13,8)))),6,0,'.') as DECIMAL(8,3)) as current_reading,
        SUBSTRING(payload,21,2) as valve_status,
        SUBSTRING(payload,23,2) as alarm,
        cast((CONV(SUBSTRING(payload,25,2),16,10)-1)/253 as DECIMAL(4,3)) as battery,
        received_in
        FROM
        (
            SELECT
            d.EndDevice_devEui,
            d.payload,
            w.meterNumber as meter_no,
            d.received_in
            FROM DataUpDto d
            INNER join Water_Meters w on w.endDevice_devEui = d.endDevice_devEui
            WHERE d.id = NEW.id
        ) a LIMIT 1;

    INSERT INTO meter_alarms (
        endDevice_devEui,
        meter_no,
        alarm,
        transducer_error,
        leakage_alarm,
        ee_error,
        temperature_alarm,
        over_range_alarm,
        reverse_flow_alarm,
        empty_pipe_alarm,
        meter_battery_alarm,
        received_in
        )
    SELECT
        endDevice_devEui,
        meter_no,
        alarm,
        SUBSTRING(alarm_conv,1,1) as transducer_error,
        SUBSTRING(alarm_conv,2,1) as leakage_alarm,
        SUBSTRING(alarm_conv,3,1) as ee_error,
        SUBSTRING(alarm_conv,4,1) as temperature_alarm,
        SUBSTRING(alarm_conv,5,1) as over_range_alarm,
        SUBSTRING(alarm_conv,6,1) as reverse_flow_alarm,
        SUBSTRING(alarm_conv,7,1) as empty_pipe_alarm,
        SUBSTRING(alarm_conv,8,1) as meter_battery_alarm,
        received_in
        FROM
        (
            SELECT
            d.EndDevice_devEui,
            w.meterNumber as meter_no,
            m.alarm,
            LPAD(CONV(m.alarm, 16, 2),8,0) as alarm_conv,
            d.received_in
            FROM DataUpDto d
            INNER join Water_Meters w on w.endDevice_devEui = d.endDevice_devEui
            INNER join meter_readings m on m.endDevice_devEui = d.endDevice_devEui
            WHERE d.id = NEW.id
        ) a LIMIT 1;
  END;
$$

ALTER TABLE DataUpDto ADD INDEX (endDevice_devEui);
ALTER TABLE Water_Meters ADD INDEX (endDevice_devEui);
ALTER TABLE meter_readings ADD INDEX (endDevice_devEui);
ALTER TABLE meter_alarms ADD INDEX (endDevice_devEui);

--select cast((CONV(battery,16,10)-1)/253 as DECIMAL(4,3)) from meter_readings
/*SHOW INDEX FROM DataUpDto;
SHOW INDEX FROM Water_Meters;
SHOW INDEX FROM meter_readings;
SHOW INDEX FROM meter_alarms;

ALTER TABLE DataUpDto ADD INDEX (endDevice_devEui);
ALTER TABLE Water_Meters ADD INDEX (endDevice_devEui);
ALTER TABLE meter_readings ADD INDEX (endDevice_devEui);*/