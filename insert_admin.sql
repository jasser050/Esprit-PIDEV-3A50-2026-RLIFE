-- Insert admin user
INSERT INTO `user` (email, roles, password, first_name, last_name, username, gender, is_banned, created_at, updated_at, phone_number, profile_pic, bio, student_id, university, banned_at, ban_reason)
VALUES (
    'admin@studyflow.com',
    '["ROLE_USER","ROLE_ADMIN"]',
    '$2y$10$lkPyHGrC9jWMQfPsb.PiDeZ9e6EpfVgCP9dVrPAvx0SsnhdJRZSau',
    'Admin',
    'User',
    'admin',
    'male',
    0,
    NOW(),
    NOW(),
    NULL,
    NULL,
    'System Administrator',
    NULL,
    'StudyFlow University',
    NULL,
    NULL
);

-- Insert settings for admin user
INSERT INTO `user_settings` (user_id, study_level, weekly_goal, interests, notification_enabled, email_notifications, theme_preference, language)
VALUES (
    LAST_INSERT_ID(),
    'Graduate',
    40,
    '[]',
    1,
    1,
    'light',
    'en'
);

-- Display success message
SELECT 'Admin user created successfully!' AS message;
SELECT 'Email: admin@studyflow.com' AS credentials;
SELECT 'Password: admin123' AS credentials;
