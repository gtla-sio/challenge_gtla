-- bdd/migrations/2026-11-23-ajout-date-livraison-prevue.sql
-- Ajoute la date de livraison prévue des OT (pénalité de retard, à venir)
ALTER TABLE zl_ordres_transport
  ADD COLUMN date_livraison_prevue DATETIME NULL DEFAULT NULL AFTER ca;

-- Annulation (à exécuter seulement pour revenir en arrière) :
-- ALTER TABLE zl_ordres_transport DROP COLUMN date_livraison_prevue;