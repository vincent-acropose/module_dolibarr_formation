CREATE TABLE llx_formation_users
(
    rowid INT PRIMARY KEY NOT NULL,
    fk_user INT,
    fk_formation INT,
    FOREIGN KEY (fk_user) REFERENCES llx_user(rowid),
    FOREIGN KEY (fk_formation) REFERENCES llx_formation(rowid)
)