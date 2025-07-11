CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "newsletter_subscribers"(
  "id" integer primary key autoincrement not null,
  "newsletter_list_id" integer not null,
  "email" varchar not null,
  "first_name" varchar,
  "last_name" varchar,
  "email_verified_at" datetime,
  "verification_token" varchar,
  "subscribed_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  "unsubscribe_token" varchar,
  "status" varchar check("status" in('subscribed', 'unsubscribed')) not null default 'subscribed'
);
CREATE TABLE IF NOT EXISTS "newsletter_lists"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "from_email" varchar not null,
  "from_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "campaigns"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "newsletter_list_id" integer not null,
  "status" varchar check("status" in('draft', 'scheduled', 'sending', 'sent')) not null default 'draft',
  "scheduled_at" datetime,
  "sent_at" datetime,
  "sent_count" integer not null default '0',
  "opens" integer not null default '0',
  "clicks" integer not null default '0',
  "unsubscribes" integer not null default '0',
  "bounces" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "subject" varchar,
  "content" text,
  foreign key("newsletter_list_id") references "newsletter_lists"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "campaign_opens"(
  "id" integer primary key autoincrement not null,
  "campaign_id" integer not null,
  "newsletter_subscriber_id" integer not null,
  "opened_at" datetime not null default CURRENT_TIMESTAMP,
  "ip_address" varchar,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("campaign_id") references "campaigns"("id") on delete cascade,
  foreign key("newsletter_subscriber_id") references "newsletter_subscribers"("id") on delete cascade
);
CREATE UNIQUE INDEX "campaign_opens_campaign_id_newsletter_subscriber_id_unique" on "campaign_opens"(
  "campaign_id",
  "newsletter_subscriber_id"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_07_10_102141_create_subscribers_table',1);
INSERT INTO migrations VALUES(5,'2025_07_10_105715_create_newsletter_lists_table',1);
INSERT INTO migrations VALUES(6,'2025_07_11_005228_create_campaigns_table',1);
INSERT INTO migrations VALUES(7,'2025_07_11_010559_add_email_content_to_campaigns_table',1);
INSERT INTO migrations VALUES(8,'2025_07_11_011016_add_unsubscribe_token_and_status_to_newsletter_subscribers_table',1);
INSERT INTO migrations VALUES(9,'2025_07_11_015208_create_campaign_opens_table',1);
