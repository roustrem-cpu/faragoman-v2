# Faragoman v2 — Progress Tracker

This document tracks the incremental rewrite of the Faragoman application from the
original `project` repository into the modernized `faragoman-v2` architecture.

## Status legend
- [x] Done
- [~] In progress
- [ ] Not started

## Phase 1 — Architecture foundation
- [x] DI container, Application kernel (composition root)
- [x] Router + middleware pipeline (Auth, CSRF, Role)
- [x] Service / Repository layers
- [x] Backward-compatible mysqli `Database` wrapper (exposes legacy `$conn`)
- [x] Dynamic RBAC (`Rbac`) + additive `roles`/`permissions` schema
- [x] Compiled Tailwind UI scaffold (glass cards, aurora, pseudo-3D tilt)
- [x] Backward-compatible auth (legacy password hashes keep working)
- [x] Initial production ZIP

## Phase 2 — Content features (this iteration)
- [x] **Stories** (re-enabled): Model, Repository, Service, Controller
- [x] Additive `stories` table (`IF NOT EXISTS`, legacy columns preserved)
- [x] Premium pseudo-3D story ring bar + immersive viewer (vanilla JS, local)
- [x] Public `/stories` JSON endpoint; guarded create/delete via `stories.manage`
- [x] Graceful degradation when `stories` table is absent
- [x] **Syndication**: RSS (`/feed`, `/feed/rss`), JSON Feed (`/feed.json`)
- [x] **CLI / terminal feed** (`/feed.txt`) + User-Agent content negotiation
- [x] **LegacyBridge**: untouched Store & Chat modules reuse the new connection
- [x] All PHP files pass `php -l` (PHP 8.4); kernel smoke-tested
- [x] Production ZIP refreshed

## Phase 3 — Remaining work (next iterations)
- [x] Article detail page + category/author/search pages on the new router
- [~] Admin panel — foundation/layout/routing done (Task C); management UIs pending (Tasks D, F)
- [ ] Full dynamic RBAC management UI (assign/revoke permissions per admin)
- [ ] Profile / wiki pages ported to the new architecture
- [ ] Image upload pipeline for stories (file storage + optimization)
- [ ] Drop-in copy of the untouched Store & Chat modules under `/legacy`
- [ ] End-to-end test against a copy of the production database

_Last updated by the automated continuation agent._
