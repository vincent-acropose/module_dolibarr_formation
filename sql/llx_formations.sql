CREATE TABLE llx_formation
(
    rowid INT PRIMARY KEY NOT NULL,
    entity INT DEFAULT 1,
    ref VARCHAR(100),
    date_cre datetime,
    date_maj datetime,
    dated date,
    help DOUBLE(24,8)  DEFAULT 0,
    delayh INT,
    fk_statut INT NOT NULL,
    fk_product INT,
    FOREIGN KEY (fk_product) REFERENCES llx_product(rowid)
)