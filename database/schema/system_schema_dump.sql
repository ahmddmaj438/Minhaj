-- System schema dump
-- Generated: 2026-05-04T21:40:35+00:00

PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;

CREATE TABLE "cache" ("key" varchar not null, "value" text not null, "expiration" integer not null, primary key ("key"));

CREATE TABLE "cache_locks" ("key" varchar not null, "owner" varchar not null, "expiration" integer not null, primary key ("key"));

CREATE TABLE "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);

CREATE TABLE "group_role" ("group_id" integer not null, "role_id" integer not null, foreign key("group_id") references "groups"("id") on delete cascade, foreign key("role_id") references "roles"("id") on delete cascade, primary key ("group_id", "role_id"));

CREATE TABLE "group_rule" ("group_id" integer not null, "rule_id" integer not null, foreign key("group_id") references "groups"("id") on delete cascade, foreign key("rule_id") references "rules"("id") on delete cascade, primary key ("group_id", "rule_id"));

CREATE TABLE "group_user" ("group_id" integer not null, "user_id" integer not null, foreign key("group_id") references "groups"("id") on delete cascade, foreign key("user_id") references "users"("id") on delete cascade, primary key ("group_id", "user_id"));

CREATE TABLE "groups" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "job_batches" ("id" varchar not null, "name" varchar not null, "total_jobs" integer not null, "pending_jobs" integer not null, "failed_jobs" integer not null, "failed_job_ids" text not null, "options" text, "cancelled_at" integer, "created_at" integer not null, "finished_at" integer, primary key ("id"));

CREATE TABLE "jobs" ("id" integer primary key autoincrement not null, "queue" varchar not null, "payload" text not null, "attempts" integer not null, "reserved_at" integer, "available_at" integer not null, "created_at" integer not null);

CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);

CREATE TABLE "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));

CREATE TABLE "permission_role" ("permission_id" integer not null, "role_id" integer not null, foreign key("permission_id") references "permissions"("id") on delete cascade, foreign key("role_id") references "roles"("id") on delete cascade, primary key ("permission_id", "role_id"));

CREATE TABLE "permissions" ("id" integer primary key autoincrement not null, "name" varchar not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "role_user" ("role_id" integer not null, "user_id" integer not null, foreign key("role_id") references "roles"("id") on delete cascade, foreign key("user_id") references "users"("id") on delete cascade, primary key ("role_id", "user_id"));

CREATE TABLE "roles" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "rules" ("id" integer primary key autoincrement not null, "resource" varchar not null, "action" varchar not null, "effect" varchar check ("effect" in ('allow', 'deny')) not null default 'allow', "created_at" datetime, "updated_at" datetime);

CREATE TABLE "sessions" ("id" varchar not null, "user_id" integer, "ip_address" varchar, "user_agent" text, "payload" text not null, "last_activity" integer not null, primary key ("id"));

CREATE TABLE "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "email_verified_at" datetime, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime);

CREATE INDEX "cache_expiration_index" on "cache" ("expiration");

CREATE INDEX "cache_locks_expiration_index" on "cache_locks" ("expiration");

CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");

CREATE UNIQUE INDEX "groups_name_unique" on "groups" ("name");

CREATE UNIQUE INDEX "groups_slug_unique" on "groups" ("slug");

CREATE INDEX "jobs_queue_index" on "jobs" ("queue");

CREATE UNIQUE INDEX "permissions_name_unique" on "permissions" ("name");

CREATE UNIQUE INDEX "roles_name_unique" on "roles" ("name");

CREATE UNIQUE INDEX "roles_slug_unique" on "roles" ("slug");

CREATE UNIQUE INDEX "rules_resource_action_effect_unique" on "rules" ("resource", "action", "effect");

CREATE INDEX "sessions_last_activity_index" on "sessions" ("last_activity");

CREATE INDEX "sessions_user_id_index" on "sessions" ("user_id");

CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");

COMMIT;
PRAGMA foreign_keys=ON;
