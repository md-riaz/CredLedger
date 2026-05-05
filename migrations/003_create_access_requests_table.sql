-- Access requests for credential approval workflow
CREATE TABLE access_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    secret_id INTEGER NOT NULL,
    requester_id INTEGER NOT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    requested_duration_hours INTEGER NOT NULL,
    reviewed_by INTEGER,
    reviewed_at DATETIME,
    review_note TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (secret_id) REFERENCES secrets(id),
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

CREATE INDEX idx_access_requests_status ON access_requests(status);
CREATE INDEX idx_access_requests_secret ON access_requests(secret_id);
CREATE INDEX idx_access_requests_requester ON access_requests(requester_id);
