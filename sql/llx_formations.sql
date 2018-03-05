CREATE TABLE llx_formation
(
    rowid INT PRIMARY KEY NOT NULL,
    entity INT DEFAULT 1,
    ref VARCHAR(100),
    date_cre datetime,
    date_maj datetime,
    dated date,
    datef date,
    fk_statut INT NOT NULL,
    fk_user INT,
    fk_product INT,
    total_ht DOUBLE(24,8)  DEFAULT 0,
    total_ttc DOUBLE(24,8)  DEFAULT 0,
    FOREIGN KEY (fk_user) REFERENCES llx_user(rowid),
    FOREIGN KEY (fk_product) REFERENCES llx_product(rowid)
)