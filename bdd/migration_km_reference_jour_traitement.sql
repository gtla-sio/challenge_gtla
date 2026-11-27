-- ajout des kilometre de référence et des jours de traitement

ALTER TABLE `zl_ordres_transport` ADD (
    `km_reference` int(11) NULL,
    `jour_traitement` ENUM('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi') NULL
);
