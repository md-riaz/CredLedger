-- Secrets table with encrypted credentials
CREATE TABLE secrets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    encrypted_value TEXT NOT NULL,
    nonce TEXT NOT NULL,
    category VARCHAR(100),
    owner_id INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE INDEX idx_secrets_owner ON secrets(owner_id);
CREATE INDEX idx_secrets_category ON secrets(category);
CREATE INDEX idx_secrets_active ON secrets(is_active);
