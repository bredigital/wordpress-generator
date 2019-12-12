# Changelog
Adheres to [Keep a Changelog][KC], and [Semantic Versioning][SV].

This has been migrated from Subversion to Git(Hub). Versions prior to 1.0b are
no longer available.

## Unreleased
### Added
- Password for the generated site is emailed to you (#2).
- Emails include more site details where applicable (#3).

### Changed
- Mail from and name are passed to WordPress sites via generator plugin.
- Adjustment to verbosity of email error logging (if debug is enabled).

## [1.0b] - 2019-07-11
### Changed
- WordPress theme files dropped in favour of bootstrap.
- HTTPS dropdown changed to checkbox.
- Adjustments to docker-compose.yml (was pre-adjusted for Toolbox).
- `resources` is now `assets`.
- Dependency upgrades (CLI 2.2.0).
- Code quality improvements.

### Fixed
- Export site no longer offers to download a broken archive.

## Removed
- WordPress favicon removed.

[KC]: https://keepachangelog.com/en/1.0.0/
[SV]: https://semver.org/spec/v2.0.0.html
