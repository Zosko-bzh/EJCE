-- SQL/add_date_ceinture.sql
ALTER TABLE licence
ADD COLUMN date_ceinture DATE NULL COMMENT 'Date d\'obtention de la ceinture';
