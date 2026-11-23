-- =============================================================================
-- 0004 — Denetim kaydı (SQLite hâli, VNL 4.1.0)
-- =============================================================================

CREATE TABLE IF NOT EXISTS tbl_vnl_audit_log (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER      DEFAULT NULL,
    action       VARCHAR(100) NOT NULL,
    subject_type VARCHAR(100) DEFAULT NULL,
    subject_id   VARCHAR(64)  DEFAULT NULL,
    data         TEXT         DEFAULT NULL,
    ip           VARCHAR(45)  DEFAULT NULL,
    user_agent   VARCHAR(255) DEFAULT NULL,
    created_at   DATETIME     NOT NULL
);
CREATE INDEX IF NOT EXISTS ix_audit_user ON tbl_vnl_audit_log (user_id);
CREATE INDEX IF NOT EXISTS ix_audit_action ON tbl_vnl_audit_log (action);
CREATE INDEX IF NOT EXISTS ix_audit_subject ON tbl_vnl_audit_log (subject_type, subject_id);
CREATE INDEX IF NOT EXISTS ix_audit_created ON tbl_vnl_audit_log (created_at);
