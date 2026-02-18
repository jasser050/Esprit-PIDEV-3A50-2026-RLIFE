-- Create comment table
CREATE TABLE IF NOT EXISTS comment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignment(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create project_share table
CREATE TABLE IF NOT EXISTS project_share (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    shared_with_user_id INT NOT NULL,
    shared_by_user_id INT NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'viewer',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (project_id) REFERENCES project(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with_user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by_user_id) REFERENCES user(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_shared_with (shared_with_user_id),
    INDEX idx_shared_by (shared_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create assignment_collaborator table
CREATE TABLE IF NOT EXISTS assignment_collaborator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_by_user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignment(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by_user_id) REFERENCES user(id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_user (user_id),
    INDEX idx_assigned_by (assigned_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
