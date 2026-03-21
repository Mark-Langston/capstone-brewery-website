DROP TABLE IF EXISTS audit_log;

CREATE TABLE audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    action_type VARCHAR(50) NOT NULL,
    field_changed VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    change_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_log_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE INDEX idx_audit_log_user_id
    ON audit_log(user_id);

CREATE INDEX idx_audit_log_entity_type_entity_id
    ON audit_log(entity_type, entity_id);

CREATE INDEX idx_audit_log_change_timestamp
    ON audit_log(change_timestamp);
