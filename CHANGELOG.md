# Changelog
Adheres to [Keep a Changelog][KC], and [Semantic Versioning][SV].

This has been migrated from Subversion to Git(Hub). Versions prior to 1.0b are
no longer available.

## Unreleased
### Added
- Sitelog table created by system when empty schema detected.
- Version dropdown selector.
- DB Collation can now be specified (for base system, WP may differ).
- Custom WP-CLI path can be specified.

### Changed
- MAJOR expiration changed to date rather than an integer incremental.
- Protected status measured by lack of expiry date.
- Strict mode enabled and adhered to for most classes.
- Dependencies upgraded (Minimum version PHP 7.2).
- Twig moved to custom class.
- Email HTML moved to Twig.
- WP-CLI runtime cache within application directory.

### Fixed
- Downloadable backup link in deletion email appeared when such didn't exist.
- System crash when using emojis in the name field (#8).
- System crash when deleting a site with no user #1 (#7).

## [1.1b] - 2019-12-17
### Added
- Password for the generated site is emailed to you (#2).
- Emails include more site details where applicable (#3).
- Warning messages can be shown via txt files in the root.
- Protected status to avoid a site being deleted.

### Changed
- Mail from and name are passed to WordPress sites via generator plugin.
- Adjustment to verbosity of email error logging (if debug is enabled).
- Errors now appear in the log.

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

### Removed
- WordPress favicon removed.

[KC]:   https://keepachangelog.com/en/1.0.0/
[SV]:   https://semver.org/spec/v2.0.0.html
[1.0b]: https://github.com/bredigital/wordpress-generator/releases/tag/1.0b
[1.1b]: https://github.com/bredigital/wordpress-generator/releases/tag/1.1b