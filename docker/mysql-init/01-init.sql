CREATE TABLE app_db
(
    loginProprietaire    VARCHAR(30),
    nomProprietaire      VARCHAR(30),
    prenomProprietaire   VARCHAR(30),
    emailProprietaire    VARCHAR(255),
    mdpHacheProprietaire VARCHAR(255),
    mdpProprietaire      VARCHAR(50),
    idEvenement          INT,
    codeSecretEvenement  VARCHAR(255),
    titreEvenement       VARCHAR(50),
    dateEvenement        DATETIME,
    membresEvenement     JSON,
    idDepense            INT,
    titreDepense         VARCHAR(50),
    dateDepense          DATETIME,
    montantDepense       FLOAT,
    loginPayeur          VARCHAR(30),
    nomPayeur            VARCHAR(30),
    prenomPayeur         VARCHAR(30),
    participantsDepense  JSON,
    CONSTRAINT pk_db PRIMARY KEY (loginProprietaire, idEvenement, idDepense)
);