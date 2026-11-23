-- =============================================================================
-- 0005 — İş kuyruğu (SQLite hâli, VNL 4.1.0)
--
-- SQLite yazmaları veritabanı kilidiyle sıraya girer; tek ifadelik
-- UPDATE … WHERE id = (SELECT … LIMIT 1) ayırması atomiktir.
-- =============================================================================

CREATE TABLE IF NOT EXISTS tbl_vnl_jobs (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    queue        VARCHAR(50)  NOT NULL DEFAULT 'default',
    type         VARCHAR(100) NOT NULL,
    payload      TEXT         NOT NULL,
    status       VARCHAR(16)  NOT NULL DEFAULT 'queued',
    attempts     INTEGER      NOT NULL DEFAULT 0,
    max_attempts INTEGER      NOT NULL DEFAULT 1,
    progress     INTEGER      NOT NULL DEFAULT 0,
    output       TEXT         DEFAULT NULL,
    error        TEXT         DEFAULT NULL,
    reserved_by  CHAR(32)     DEFAULT NULL,
    available_at DATETIME     NOT NULL,
    reserved_at  DATETIME     DEFAULT NULL,
    finished_at  DATETIME     DEFAULT NULL,
    created_by   INTEGER      DEFAULT NULL,
    created_at   DATETIME     NOT NULL,
    updated_at   DATETIME     DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS ix_jobs_pick ON tbl_vnl_jobs (queue, status, available_at);
CREATE INDEX IF NOT EXISTS ix_jobs_reserved ON tbl_vnl_jobs (reserved_by);
