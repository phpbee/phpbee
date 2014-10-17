
CREATE PROCEDURE rr_stack (CID INT, SID INT,  stack_name VARCHAR(255), STARTSLOT INT, ENDSLOT INT) 
BEGIN


  DECLARE done INT DEFAULT FALSE;
  
  DECLARE recid INT;
  DECLARE rs_name, rec_add_intvalue, rec_add_stringvalue VARCHAR(255);

  DECLARE cur1 CURSOR FOR SELECT distinct record_id, recordset_name, add_intvalue, add_stringvalue FROM roundrobin WHERE Config_id=CID and STARTSLOT>=slot AND slot>=ENDSLOT order by record_id;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  DELETE from roundrobin_stack where Config_id=CID and Stack_id=SID;

  OPEN cur1;

  read_loop: LOOP

    SET done = FALSE;
    FETCH cur1 INTO recid, rs_name, rec_add_intvalue,rec_add_stringvalue;
    IF done THEN
      LEAVE read_loop;
    END IF;


    SET @counter1=0;
    SET @counter2=0;

    SELECT counter INTO @counter1 FROM roundrobin WHERE Config_id=CID and record_id=recid and recordset_name=rs_name
            and  slot<=STARTSLOT ORDER BY slot DESC LIMIT 1;
    SELECT counter INTO @counter2 FROM roundrobin WHERE Config_id=CID and record_id=recid and recordset_name=rs_name
            and  slot<ENDSLOT ORDER BY slot DESC LIMIT 1;


    INSERT INTO roundrobin_stack(Config_id,Stack_id,name,recordset_name,record_id,counter, slot,add_intvalue, add_stringvalue) 
        VALUES (CID,SID,stack_name,rs_name,recid,@counter1-@counter2,STARTSLOT, rec_add_intvalue, rec_add_stringvalue);


  END LOOP;

  CLOSE cur1;
    

END

