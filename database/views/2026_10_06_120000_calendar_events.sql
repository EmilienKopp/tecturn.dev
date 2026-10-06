CREATE VIEW
    calendar_events AS
SELECT
    e.id,
    e.team_id,
    e.talk_id,
    e.name,
    e.date,
    e.start_time,
    t.title AS talk_title,
    e.created_at,
    e.updated_at
FROM
    talk_events e
    LEFT JOIN talks t ON t.id = e.talk_id;
