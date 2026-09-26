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
    r.step_events,
    r.content,
    r.flow,
    r.created_at,
    r.updated_at,
    EXISTS (
        SELECT
            1
        FROM
            media m
        WHERE
            m.model_id = r.id
            AND m.model_type LIKE '%Rehearsal'
            AND m.collection_name = 'recording'
    ) AS has_recording,
    (
        SELECT
            COUNT(*)
        FROM
            rehearsal_reviews rv
        WHERE
            rv.practice_run_id = r.id
            AND rv.status = 'pending'
    ) AS pending_review_count,
    (
        SELECT
            COUNT(*)
        FROM
            rehearsal_reviews rv
        WHERE
            rv.practice_run_id = r.id
            AND rv.status = 'completed'
    ) AS completed_review_count
FROM
    practice_runs r
    JOIN presentations p ON p.id = r.presentation_id;
