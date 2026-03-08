CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    type VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_role
        FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE RESTRICT
);

INSERT INTO roles (id, name, type, created_at)
VALUES (DEFAULT, 'ADMIN', 'ADMIN', DEFAULT);

INSERT INTO roles (id, name, type, created_at)
VALUES (DEFAULT, 'USER', 'USER', DEFAULT);

INSERT INTO users (name, email, password, role_id, created_at, updated_at) VALUES ('Administrator', 'admin@admin.com', '$2y$12$mFISnnhc9H2S351RYCDoouLe0/DSzVe1IHBUSFo02QUtNHMbZ1IsW', 1, '2026-03-08 13:58:32.000000', '2026-03-08 13:58:32.000000');
