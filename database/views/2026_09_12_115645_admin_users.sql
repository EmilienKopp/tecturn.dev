CREATE VIEW
    admin_users AS
SELECT
    u.id,
    u.name,
    u.email,
    u.avatar,
    u.created_at,
    (
        SELECT COUNT(*)
        FROM team_members tm
        WHERE tm.user_id = u.id
    ) AS team_count,
    (
        SELECT t.name
        FROM teams t
        WHERE t.id = u.current_team_id
    ) AS current_team_name
FROM
    users u;
