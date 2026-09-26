CREATE VIEW
    presentations_view AS
SELECT
    id,
    team_id,
    name,
    is_private,
    content,
    talk_settings,
    flow,
    source,
    embed_token,
    yoyotranslate_session_id,
    yoyotranslate_session_started_at,
    yoyotranslate_languages,
    draft_requested_at,
    draft_completed_at,
    draft_failed_at,
    draft_error,
    created_at,
    updated_at
FROM
    presentations;
