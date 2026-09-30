CREATE VIEW
    presentation_images_view AS
SELECT
    m.id,
    m.model_id AS presentation_id,
    p.team_id,
    p.name AS presentation_name,
    m.name,
    m.file_name,
    m.mime_type,
    m.size,
    m.disk,
    m.created_at
FROM
    media m
    JOIN presentations p ON p.id = m.model_id
WHERE
    m.collection_name = 'images'
    AND m.model_type LIKE '%Presentation';
