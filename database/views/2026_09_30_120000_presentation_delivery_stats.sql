CREATE VIEW
    presentation_delivery_stats AS
SELECT
    p.id AS presentation_id,
    p.team_id,
    (
        SELECT
            COUNT(*)
        FROM
            practice_runs r
        WHERE
            r.presentation_id = p.id
    ) AS rehearsal_count,
    (
        SELECT
            COALESCE(SUM(r.duration_seconds), 0)
        FROM
            practice_runs r
        WHERE
            r.presentation_id = p.id
    ) AS total_rehearsal_seconds,
    (
        SELECT
            COUNT(*)
        FROM
            presentation_sessions s
        WHERE
            s.presentation_id = p.id
            AND s.ended_at IS NOT NULL
    ) AS session_count
FROM
    presentations p;
