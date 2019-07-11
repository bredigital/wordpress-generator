# Sandbox WordPress Generator
One-click generation of WordPress sites, for quick spin-up development and
testing without configuration. All through the power of [WP-CLI][wp]!

This is intended for internal classic hosting. **This is not production-ready
software.**

## Setup
Setup requires [Composer][c]. Run composer install in the main directory to grab
the vendor dependencies. After this, make a copy of the `env.example` file (just
`.env`) to configure the database (will read from system env first).

System will automatically install a local email plugin, and set the database
connection up based upon your environment configuration.

Use the following code to trigger the cron system (replace <>):

`powershell "Invoke-WebRequest <root web address>/controls.php?control=cron"`

**This comes with Docker support! Run `docker-compose up --build` to run this
via Docker, and access through `localhost:58000`.**

## Sites
Each site will be given a small must-use plugin that maintains a link back to
the generator. This brings the generator email settings into the site, and adds
a small admin bar option to head back. This automatically detects if it is
within the generator, and will disable itself if the site is moved outside.

Within `assets/wordpress` you can place themes and plugins (regular and must-use
) that will be added into each newly generated site.

[wp]: https://wp-cli.org/
[c]:  https://getcomposer.org/