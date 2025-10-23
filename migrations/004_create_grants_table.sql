-- Time-boxed grants with checkout/check-in tracking
CREATE TABLE grants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    secret_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    access_request_id INTEGER,
    granted_by INTEGER NOT NULL,
    expires_at DATETIME NOT NULL,
    checked_out_at DATETIME,
    checked_in_at DATETIME,
    is_revoked BOOLEAN DEFAULT 0,
    revoked_by INTEGER,
    revoked_at DATETIME,
    revocation_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (secret_id) REFERENCES secrets(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (access_request_id) REFERENCES access_requests(id),
    FOREIGN KEY (granted_by) REFERENCES users(id),
    FOREIGN KEY (revoked_by) REFERENCES users(id)
);

CREATE INDEX idx_grants_secret ON grants(secret_id);
CREATE INDEX idx_grants_user ON grants(user_id);
CREATE INDEX idx_grants_expires ON grants(expires_at);
CREATE INDEX idx_grants_revoked ON grants(is_revoked);
