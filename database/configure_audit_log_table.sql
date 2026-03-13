ALTER TABLE audit_log
    ADD COLUMN user_id INT NOT NULL,
    ADD COLUMN inventory_id INT NULL,
    ADD COLUMN action_type VARCHAR(50) NOT NULL,
    ADD COLUMN field_changed VARCHAR(100) NULL,
    ADD COLUMN old_value TEXT NULL,
    ADD COLUMN new_value TEXT NULL,
    ADD COLUMN change_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD CONSTRAINT fk_audit_log_user
        FOREIGN KEY (user_id) REFERENCES users(user_id),
    ADD CONSTRAINT fk_audit_log_inventory
        FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id);
