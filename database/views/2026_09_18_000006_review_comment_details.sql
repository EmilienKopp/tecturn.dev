CREATE VIEW
    review_comment_details AS
SELECT
    c.id,
    c.rehearsal_review_id,
    c.slide_number,
    c.message,
    c.created_at,
    c.updated_at,
    rv.practice_run_id,
    rv.reviewer_user_id,
    reviewer.name AS reviewer_name,
    reviewer.avatar AS reviewer_avatar
FROM
    review_comments c
    INNER JOIN rehearsal_reviews rv ON rv.id = c.rehearsal_review_id
    INNER JOIN users reviewer ON reviewer.id = rv.reviewer_user_id;
