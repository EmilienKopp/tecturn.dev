CREATE VIEW
    rehearsal_review_details AS
SELECT
    rv.id,
    rv.practice_run_id,
    rv.requester_user_id,
    rv.reviewer_user_id,
    rv.status,
    rv.created_at,
    rv.updated_at,
    r.team_id,
    r.presentation_id,
    r.started_at AS rehearsed_at,
    r.duration_seconds,
    p.name AS presentation_name,
    requester.name AS requester_name,
    requester.avatar AS requester_avatar,
    requester.handle AS requester_handle,
    reviewer.name AS reviewer_name,
    reviewer.avatar AS reviewer_avatar,
    reviewer.handle AS reviewer_handle
FROM
    rehearsal_reviews rv
    INNER JOIN practice_runs r ON r.id = rv.practice_run_id
    INNER JOIN presentations p ON p.id = r.presentation_id
    INNER JOIN users requester ON requester.id = rv.requester_user_id
    INNER JOIN users reviewer ON reviewer.id = rv.reviewer_user_id;
