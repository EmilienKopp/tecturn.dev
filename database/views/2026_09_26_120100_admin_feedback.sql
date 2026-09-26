CREATE VIEW
    admin_feedback AS
SELECT
    f.id,
    f.message,
    f.user_id,
    u.name AS user_name,
    u.email AS user_email,
    f.created_at
FROM
    feedback f
    LEFT JOIN users u ON u.id = f.user_id;
