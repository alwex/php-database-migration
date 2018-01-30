-- Test that file inclusion works correctly
-- Migration SQL that makes the change goes here.
-- @FILE
up/db1/table1.sql
-- @FILE
up/db1/table2.sql
-- @FILE
up/db2/table1.sql

-- @UNDO
-- SQL to undo the change goes here.
-- @FILE
down/db1/table1.sql
-- @FILE
down/db1/table2.sql
-- @FILE
down/db2/table1.sql