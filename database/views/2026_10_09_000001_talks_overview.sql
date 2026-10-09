CREATE VIEW
    talks_overview AS
SELECT
    t.id,
    t.team_id,
    t.title,
    COUNT(p.id) AS deck_count,
    MAX(p.version_major) AS latest_major,
    (
        SELECT
            p2.id
        FROM
            presentations p2
        WHERE
            p2.talk_id = t.id
        ORDER BY
            COALESCE(p2.version_major, 0) DESC,
            COALESCE(p2.version_minor, 0) DESC,
            p2.id DESC
        LIMIT
            1
    ) AS latest_presentation_id
FROM
    talks t
    LEFT JOIN presentations p ON p.talk_id = t.id
GROUP BY
    t.id,
    t.team_id,
    t.title;
