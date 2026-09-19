CREATE VIEW
    contact_profiles AS
SELECT
    u.id,
    u.name,
    u.avatar,
    u.handle,
    u.social_x_handle,
    u.social_github_handle,
    COALESCE((
        SELECT COUNT(*)
        FROM presentations p
        WHERE p.team_id = (
            SELECT t.id
            FROM teams t
            INNER JOIN team_members tm ON tm.team_id = t.id
            WHERE tm.user_id = u.id
                AND tm.role = 'owner'
                AND t.is_personal = 1
            LIMIT 1
        )
            AND p.is_private = 0
    ), 0) AS talks_count,
    COALESCE((
        SELECT SUM(s.viewer_count)
        FROM session_analytics s
        INNER JOIN presentations p ON p.id = s.presentation_id
        WHERE s.team_id = (
            SELECT t.id
            FROM teams t
            INNER JOIN team_members tm ON tm.team_id = t.id
            WHERE tm.user_id = u.id
                AND tm.role = 'owner'
                AND t.is_personal = 1
            LIMIT 1
        )
            AND p.is_private = 0
    ), 0) AS total_viewers,
    COALESCE((
        SELECT SUM(s.reaction_total)
        FROM session_analytics s
        INNER JOIN presentations p ON p.id = s.presentation_id
        WHERE s.team_id = (
            SELECT t.id
            FROM teams t
            INNER JOIN team_members tm ON tm.team_id = t.id
            WHERE tm.user_id = u.id
                AND tm.role = 'owner'
                AND t.is_personal = 1
            LIMIT 1
        )
            AND p.is_private = 0
    ), 0) AS total_reactions,
    COALESCE((
        SELECT COUNT(*)
        FROM user_follows uf
        WHERE uf.followed_user_id = u.id
            AND uf.status = 'accepted'
    ), 0) AS followers_count,
    COALESCE((
        SELECT COUNT(*)
        FROM user_follows uf
        WHERE uf.follower_user_id = u.id
            AND uf.status = 'accepted'
    ), 0) AS following_count
FROM
    users u;
