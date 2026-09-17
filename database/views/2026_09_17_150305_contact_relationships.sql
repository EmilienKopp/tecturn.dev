CREATE VIEW
    contact_relationships AS
SELECT
    uf.id,
    uf.follower_user_id,
    uf.followed_user_id,
    uf.created_at,
    uf.updated_at,
    follower.name AS follower_name,
    follower.avatar AS follower_avatar,
    follower.handle AS follower_handle,
    follower.social_x_handle AS follower_social_x_handle,
    follower.social_github_handle AS follower_social_github_handle,
    followed.name AS followed_name,
    followed.avatar AS followed_avatar,
    followed.handle AS followed_handle,
    followed.social_x_handle AS followed_social_x_handle,
    followed.social_github_handle AS followed_social_github_handle
FROM
    user_follows uf
    INNER JOIN contact_profiles follower ON follower.id = uf.follower_user_id
    INNER JOIN contact_profiles followed ON followed.id = uf.followed_user_id;
