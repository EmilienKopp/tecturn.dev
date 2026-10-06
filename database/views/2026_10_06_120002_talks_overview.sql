CREATE VIEW
    talks_overview AS
SELECT
    t.id,
    t.team_id,
    t.title,
    COUNT(p.id) AS deck_count,
    MAX(p.version_major) AS latest_major
FROM
    talks t
    LEFT JOIN presentations p ON p.talk_id = t.id
GROUP BY
    t.id,
    t.team_id,
    t.title;
