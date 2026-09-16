CREATE VIEW
    practice_run_history AS
SELECT
    r.id,
    r.presentation_id,
    r.team_id,
    p.name AS presentation_name,
    r.started_at,
    r.ended_at,
    r.duration_seconds,
    r.slide_timings,
    r.content,
    r.flow,
    r.created_at,
    r.updated_at
FROM
    practice_runs r
    JOIN presentations p ON p.id = r.presentation_id;
