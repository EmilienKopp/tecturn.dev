CREATE VIEW
    admin_beta_requests AS
SELECT
    id,
    name,
    email,
    message,
    status,
    created_at,
    updated_at
FROM
    beta_requests;
