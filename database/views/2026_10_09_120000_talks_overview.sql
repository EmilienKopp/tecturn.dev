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
            AND (
                p2.draft_requested_at IS NULL
                OR p2.draft_completed_at IS NOT NULL
            )
        ORDER BY
            COALESCE(p2.version_major, 0) DESC,
            COALESCE(p2.version_minor, 0) DESC,
            p2.id DESC
        LIMIT
            1
    ) AS latest_presentation_id,
    (
        SELECT
            COALESCE(p3.draft_completed_at, p3.created_at)
        FROM
            presentations p3
        WHERE
            p3.talk_id = t.id
            AND (
                p3.draft_requested_at IS NULL
                OR p3.draft_completed_at IS NOT NULL
            )
        ORDER BY
            COALESCE(p3.version_major, 0) DESC,
            COALESCE(p3.version_minor, 0) DESC,
            p3.id DESC
        LIMIT
            1
    ) AS generated_at
FROM
    talks t
    LEFT JOIN presentations p ON p.talk_id = t.id
GROUP BY
    t.id,
    t.team_id,
    t.title;
