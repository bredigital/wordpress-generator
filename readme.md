# Sandbox WordPress Generator
One-click generation of WordPress sites, for quick spin-up development and
testing without configuration. All through the power of [WP-CLI][wp]!

![Screenshot of the application](https://user-images.githubusercontent.com/44401434/61215275-49b6ca00-a702-11e9-84a1-adcdd4987387.png)

**This is not intended to be used in a production environment.**

## Download
Pre-made setups can be found on the [releases page][r], as
`wordpress-generator-x.x.zip`. Simply extract into the root of your desired web
directory, rename `.env.example` to `.env` and configure as desired.

**There is also a [Docker image available][d] for this project**, which is a
more efficient way to set up if you have used Docker before.

The system will work with either **MySQL** or **MariaDB**. On loading the
homepage, the system will check the database credentials given, and setup the
necessary tables once connection has been established.

A cron/task needs to be set to hit the following URL on a regular basis for the
automatic features to run.

`<root web address>/controls.php?control=cron`

## Sites
Each site will be given a small must-use plugin that maintains a link back to
the generator. This brings the generator email settings into the site, and adds
a small admin bar option to head back. This automatically detects if it is
within the generator, and will disable itself if the site is moved outside.

Within `assets/wordpress` you can place themes and plugins (regular and must-use
) that will be added into each newly generated site.

[wp]: https://wp-cli.org/
[c]:  https://getcomposer.org/
[d]:  https://hub.docker.com/r/bredigital/wordpress-generator
[r]:  https://github.com/bredigital/wordpress-generator/releases
