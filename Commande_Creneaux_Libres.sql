
-- Commande pour déterminer les créneaux libres d'une semaine

-- Étape 1 : Génération de tous les slots possibles (4 à 48)
WITH RECURSIVE slots AS (
    SELECT 4 AS slot
    UNION ALL
    SELECT slot + 1
    FROM slots
    WHERE slot + 1 <= 48
),

-- Étape 2 : Génération des jours de la semaine spécifiée (lundi à vendredi)
dates AS (
    SELECT DATE_ADD(
               STR_TO_DATE(CONCAT(@annee, LPAD(@semaine, 2, '0'), '1'), '%X%V%w'),
               INTERVAL n DAY
           ) AS date
    FROM (
        SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    ) AS nums
    WHERE DAYOFWEEK(DATE_ADD(STR_TO_DATE(CONCAT(@annee, LPAD(@semaine, 2, '0'), '1'), '%X%V%w'), INTERVAL n DAY)) BETWEEN 2 AND 6
),

-- Étape 3 : Création de toutes les combinaisons slots/dates
all_slots AS (
    SELECT d.date, s.slot
    FROM dates d
    CROSS JOIN slots s
),

-- Étape 4 : Création des créneaux pris pour toutes les ressources
taken_slots AS (
    SELECT DISTINCT activities.date, activities.slot AS start_slot, activities.slot + activities.duration AS end_slot
    FROM activities
    INNER JOIN activity_resource ON activities.id = activity_resource.idActivity
    INNER JOIN ressources ON activity_resource.idRessource = ressources.idADE
    WHERE FIND_IN_SET(ressources.name, @ressources)
      AND WEEK(activities.date, 1) = @semaine
      AND YEAR(activities.date) = @annee
),

-- Étape 5 : Trouver les slots libres pour toutes les ressources
free_slots AS (
    SELECT a.date, a.slot
    FROM all_slots a
    LEFT JOIN taken_slots t
      ON a.date = t.date AND a.slot >= t.start_slot AND a.slot < t.end_slot
    WHERE t.start_slot IS NULL
)

-- Étape 6 : Vérifier que chaque créneau est libre pour toutes les ressources
SELECT f.date, f.slot
FROM free_slots f
WHERE NOT EXISTS (
    SELECT 1
    FROM activities act
    INNER JOIN activity_resource ar ON act.id = ar.idActivity
    INNER JOIN ressources r ON ar.idRessource = r.idADE
    WHERE FIND_IN_SET(r.name, @ressources)
      AND act.date = f.date
      AND f.slot BETWEEN act.slot AND act.slot + act.duration - 1
)
ORDER BY f.date, f.slot;
