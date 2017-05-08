## Get ParentListIDByID

```
DELIMITER $$
DROP FUNCTION IF EXISTS `endeavou_app`.`GetParentListIDByID` $$
CREATE FUNCTION `endeavou_app`.`GetParentListIDByID` (GivenID INT) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE rv INT;

    SELECT IFNULL(ParentID,-1) INTO rv FROM
    (SELECT ParentID FROM Lists WHERE ID = GivenID) A;
    RETURN rv;
END $$
DELIMITER;
```

## Get GetListAncestry

```
DELIMITER $$
DROP FUNCTION IF EXISTS `endeavou_app`.`GetListAncestry` $$
CREATE FUNCTION `endeavou_app`.`GetListAncestry` (GivenID INT) RETURNS VARCHAR(1024)
DETERMINISTIC
BEGIN
    DECLARE rv VARCHAR(1024);
    DECLARE cm CHAR(1);
    DECLARE ch INT;

    SET rv = '';
    SET cm = '';
    SET ch = GivenID;
    WHILE ch > 0 DO
        SELECT IFNULL(ParentID,-1) INTO ch FROM
        (SELECT ParentID FROM Lists WHERE ID = ch) A;
        IF ch > 0 THEN
            SET rv = CONCAT(rv,cm,ch);
            SET cm = ',';
        END IF;
    END WHILE;
    RETURN rv;
END $$
DELIMITER;
```

## Get GetListDescendants

```
DELIMITER $$

DROP FUNCTION IF EXISTS `endeavour`.`GetListDescendants` $$
CREATE FUNCTION `endeavour`.`GetListDescendants` (GivenID INT) RETURNS varchar(1024) CHARSET latin1
DETERMINISTIC
BEGIN

    DECLARE rv,q,queue,queue_children VARCHAR(1024);
    DECLARE queue_length,front_id,pos INT;

    SET rv = '';
    SET queue = GivenID;
    SET queue_length = 1;

    WHILE queue_length > 0 DO
        SET front_id = FORMAT(queue,0);
        IF queue_length = 1 THEN
            SET queue = '';
        ELSE
            SET pos = LOCATE(',',queue) + 1;
            SET q = SUBSTR(queue,pos);
            SET queue = q;
        END IF;
        SET queue_length = queue_length - 1;

        SELECT IFNULL(qc,'') INTO queue_children
        FROM (SELECT GROUP_CONCAT(ID) qc
        FROM Lists WHERE ParentID = front_id) A;

        IF LENGTH(queue_children) = 0 THEN
            IF LENGTH(queue) = 0 THEN
                SET queue_length = 0;
            END IF;
        ELSE
            IF LENGTH(rv) = 0 THEN
                SET rv = queue_children;
            ELSE
                SET rv = CONCAT(rv,',',queue_children);
            END IF;
            IF LENGTH(queue) = 0 THEN
                SET queue = queue_children;
            ELSE
                SET queue = CONCAT(queue,',',queue_children);
            END IF;
            SET queue_length = LENGTH(queue) - LENGTH(REPLACE(queue,',','')) + 1;
        END IF;
    END WHILE;

    RETURN rv;

END $$
DELIMITER;
```
