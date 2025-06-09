-- phpMyAdmin SQL Dump
-- version 5.1.4
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : lun. 09 juin 2025 à 18:52
-- Version du serveur : 10.5.15-MariaDB-0+deb11u1
-- Version de PHP : 8.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `SAE4A_Q1C`
--

-- --------------------------------------------------------

--
-- Structure de la table `Depenses`
--

CREATE TABLE `Depenses` (
  `idDepense` int(11) NOT NULL,
  `titreDepense` varchar(50) CHARACTER SET utf8 NOT NULL,
  `dateDepense` date NOT NULL,
  `montantDepense` float NOT NULL,
  `loginPayeur` varchar(30) CHARACTER SET utf8 NOT NULL,
  `idEvenement` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `EtreMembre`
--

CREATE TABLE `EtreMembre` (
  `idEvenement` int(11) NOT NULL,
  `loginMembre` varchar(30) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Evenements`
--

CREATE TABLE `Evenements` (
  `idEvenement` int(11) NOT NULL,
  `codeSecretEvenement` varchar(255) CHARACTER SET utf8 NOT NULL,
  `titreEvenement` varchar(50) CHARACTER SET utf8 NOT NULL,
  `dateEvenement` date NOT NULL,
  `loginProprietaire` varchar(30) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Participer`
--

CREATE TABLE `Participer` (
  `idDepense` int(11) NOT NULL,
  `loginParticipant` varchar(30) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `Utilisateurs`
--

CREATE TABLE `Utilisateurs` (
  `login` varchar(30) CHARACTER SET utf8 NOT NULL,
  `nom` varchar(30) CHARACTER SET utf8 NOT NULL,
  `prenom` varchar(30) CHARACTER SET utf8 NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 NOT NULL,
  `mdpHache` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `Depenses`
--
ALTER TABLE `Depenses`
  ADD PRIMARY KEY (`idDepense`),
  ADD KEY `idEvenement` (`idEvenement`),
  ADD KEY `loginPayeur` (`loginPayeur`);

--
-- Index pour la table `EtreMembre`
--
ALTER TABLE `EtreMembre`
  ADD PRIMARY KEY (`idEvenement`,`loginMembre`),
  ADD KEY `loginMembre` (`loginMembre`);

--
-- Index pour la table `Evenements`
--
ALTER TABLE `Evenements`
  ADD PRIMARY KEY (`idEvenement`),
  ADD KEY `loginProprietaire` (`loginProprietaire`);

--
-- Index pour la table `Participer`
--
ALTER TABLE `Participer`
  ADD PRIMARY KEY (`idDepense`,`loginParticipant`),
  ADD KEY `loginParticipant` (`loginParticipant`);

--
-- Index pour la table `Utilisateurs`
--
ALTER TABLE `Utilisateurs`
  ADD PRIMARY KEY (`login`),
  ADD UNIQUE KEY `emailUnique` (`email`);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `Depenses`
--
ALTER TABLE `Depenses`
  ADD CONSTRAINT `Depenses_ibfk_1` FOREIGN KEY (`idEvenement`) REFERENCES `Evenements` (`idEvenement`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Depenses_ibfk_2` FOREIGN KEY (`loginPayeur`) REFERENCES `Utilisateurs` (`login`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `EtreMembre`
--
ALTER TABLE `EtreMembre`
  ADD CONSTRAINT `EtreMembre_ibfk_1` FOREIGN KEY (`idEvenement`) REFERENCES `Evenements` (`idEvenement`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `EtreMembre_ibfk_2` FOREIGN KEY (`loginMembre`) REFERENCES `Utilisateurs` (`login`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `Evenements`
--
ALTER TABLE `Evenements`
  ADD CONSTRAINT `Evenements_ibfk_1` FOREIGN KEY (`loginProprietaire`) REFERENCES `Utilisateurs` (`login`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `Participer`
--
ALTER TABLE `Participer`
  ADD CONSTRAINT `Participer_ibfk_1` FOREIGN KEY (`idDepense`) REFERENCES `Depenses` (`idDepense`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Participer_ibfk_2` FOREIGN KEY (`loginParticipant`) REFERENCES `Utilisateurs` (`login`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
