CREATE VIEW
    admin_overview AS
SELECT
    1 AS id,
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM teams WHERE deleted_at IS NULL) AS total_teams,
    (SELECT COUNT(*) FROM teams WHERE deleted_at IS NULL AND is_personal = 0) AS total_workspaces,
    (SELECT COUNT(*) FROM presentations) AS total_presentations,
    (SELECT COUNT(*) FROM presentation_sessions) AS total_sessions,
    (SELECT COUNT(*) FROM presentation_sessions WHERE ended_at IS NULL) AS live_sessions,
    (SELECT COALESCE(SUM(reaction_total), 0) FROM presentation_sessions) AS total_reactions,
    (SELECT COALESCE(SUM(viewer_count), 0) FROM presentation_sessions) AS total_viewers;
