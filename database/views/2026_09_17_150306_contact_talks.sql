CREATE VIEW
    contact_talks AS
SELECT
    p.id,
    owner.id AS user_id,
    owner.name AS user_name,
    owner.avatar AS user_avatar,
    owner.handle AS user_handle,
    owner.social_x_handle AS user_social_x_handle,
    owner.social_github_handle AS user_social_github_handle,
    p.name,
    p.is_private,
    p.updated_at,
    MAX(s.started_at) AS last_presented_at,
    COALESCE(SUM(s.viewer_count), 0) AS viewer_count,
    COALESCE(SUM(s.reaction_total), 0) AS reaction_total
FROM
    presentations p
    INNER JOIN teams t ON t.id = p.team_id
        AND t.is_personal = 1
    INNER JOIN team_members tm ON tm.team_id = t.id
        AND tm.role = 'owner'
    INNER JOIN users owner ON owner.id = tm.user_id
    LEFT JOIN session_analytics s ON s.presentation_id = p.id
WHERE
    p.is_private = 0
GROUP BY
    p.id,
    owner.id,
    owner.name,
    owner.avatar,
    owner.handle,
    owner.social_x_handle,
    owner.social_github_handle,
    p.name,
    p.is_private,
    p.updated_at;
