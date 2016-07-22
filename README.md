# Legend of the Green Dragon (Core)

[![Build Status](https://travis-ci.org/lotgd/core.svg?branch=master)](https://travis-ci.org/lotgd/core)

Legend of the Green Dragon is a text-based RPG originally developed by Eric Stevens and JT Traub as a remake of and homage to the classic BBS Door game,
Legend of the Red Dragon, by Seth Able Robinson. You can play it at numerous sites, including http://www.lotgd.net/.

After checking out the developer forums at http://dragonprime.net, it seemed that development had stalled, and specifically,
any movement toward more modern technologies and decoupling the game from its web UI was non-existent.

Thus, we sought to create our own rewrite, codenamed Daenerys :)

## Goals

* Headless LotGD: instead of coupling the game with a specific UI, this core game functionality is meant to be
wrapped in the interface of your choice.
* Modular for easy extension.
* Modern web technologies: PHP 7 since it's familiar to existing LotGD developers, with an appropriate ORM, etc.
* MVVMC architecture.
  * Model. Of course, we access data through models.
  * View. I lied, there is no view for this core LotGD library! See Headless LotGD goal above.
  * View Model. Instead of a view, we'll have a view model with all the required information to build a view, in a structured format (i.e., in a class).
  * Controller. Game actions and state transitions occur through controllers.
* Well-documented with as many type hints as PHP 7's limping type system will allow.
* Well-tested. The only hope of keeping such a large codebase to a low bug count is unit tests. See `tests/`.

### Non-Goals

* Backward compatibility. It may be controversial since it would mean existing
admins can't upgrade to this version, but keeping the old database schema would
be seriously limiting to the quality of the codebase. If we actually finish this,
then writing an importer wouldn't be too hard.

## How it Works
Here are some quick notes to get our contributors moving. Anticipated (as in,
not yet implemented) bits are marked with [PLANNED].

### Introduction
This core is not a game; it's the guts of a game, some code around which a game
could be built. It provides (or will provide) the concepts of fighting and communicating characters in an
RPG and how those characters move through a turn-based, text-based world. It does not provide
implementation details like user interface, login or authentication or even
anything about the text-based world (like specific locations, etc.). While designed
with the Legend of the Green Dragon game in mind, it could be used to build a wide
variety of MUD-style games.

The core is designed to be wrapped by something we call a "crate." The crate
makes the core into a playable game by providing a UI, locations for the characters
to interact and actions for them to take (along with consequences).
Crates are separate pieces of software, living in separate repos (see our [Sample Crate](https://github.com/lotgd/crate-sample) and
[GraphQL API Crate](https://github.com/lotgd/crate-graphql-relay)), and can
make use of "modules," which are plug-and-play pieces of code that interact with
the core and each other.

Some technical notes:
* The core's data model is based around [Doctrine](http://www.doctrine-project.org/).

### Configuration
The crate is responsible for configuring the game, which is done through
a configuration file, in the YAML format. The path to the configuration file is
specified by the environment variable `LOTGD_CONFIG`.

Here's the format of the config file:

```
database:
    dsn: # [Data Source Name](https://en.wikipedia.org/wiki/Data_source_name), a way to describe where the database is.
    name: # name of the database to access inside the specified DSN.
    user: # user name that has access to the database.
    password: # password for the database user.
logs:
    path: # the path to the directory to store log files, relative to this config file.
```

See the [`config.yml`](https://github.com/lotgd/core/blob/master/config/test.yml) we use for testing.

### Initialization/Installation [PLANNED]
Check out the [Sample Crate's README](https://github.com/lotgd/crate-sample/blob/master/README.md)
for how we're boostrapping the
initialization and installation of a game, including installing modules. This
has not been thought through yet, consider this a stopgap measure.

### Scenes
Locations in the game (like where a character is) are represented by `Scene`
objects (see the [Scene model](https://github.com/lotgd/core/blob/master/src/Models/Scene.php)).
Conceptually, scenes have a title, description (the text to display to the user about
where they are) and a menu.

Scenes are designed to be hierarchical, with parents and children, so that one
could display all the locations in the game as a tree. In fact, we hope to build
configuration tools to help game creators visualize their world using tree structures. This
also provides for an easy way for users to go "back" to the previous screen without
the `Scene` object needing to explicitly know about its parent, or for the same
`Scene` data to appear in the tree multiple times, with different parents/children
(in the LotGD world, this is like the Healer being accessible from the Village and
the Forest).

### Main Loop [PLANNED]
The crate interacts with the game via the `Game` object. Make a `Game` object
by using the `Bootstrap::createGame()` method. All the initialization (like
database connections) will be done within this method. After that, we'll have to
figure out a way to specify which user is being played currently---a game should
really represent only a single user.

"Playing" the game involves sending the `Game` object messages about actions taken
by the player and receiving menus and forms back to display to the user. This interface
has not been finalized, but possibly something like `$game->takeAction($someAction)` where
`$someAction` could be an object representing a menu item or the result of a form
entry.

Then something like `$s = $game->getScene()` would return the new scene to display
to the user. The crate would acquire input then send it back through `takeAction()`
and the loop would continue.

### Events
Events are a way for modules and parts of the core to communicate with each other
without having to know exactly what communication should take place. Events are
represented by strings. There are a
number of uses for events, with these rough naming conventions:

* `e/[vendor]/[module]/[event-name]`: Simple announcement events, just saying
that something has occurred. Possible Examples:
`e/lotgd/core/startup`.
* `h/[vendor]/[module]/[event-name]`: Hooks, or events that are designed to
seek input from other parts of the system (like modules). Possible examples:
`h/lotgd/core/get-attack-value`.
* `a/[vendor]/[module]/[event-name]`: Analytic events, those only for tracking
purposes. Possible examples: `a/lotgd/core/startup-perf`, `a/lotgd/core/motd-new`.

Events are handled by a class that implements the `EventHandler` interface and
has been previously subscribed to events by calling `$game->getEventManager()->subscribe()`.
Subscriptions use regular expressions: subscribers provide a regex
to match against event names and any published event that matches the regex
triggers a call to the class's `handleEvent()` method. See the [Sample Crate](https://github.com/lotgd/crate-sample) and
the [Hello World Module](https://github.com/lotgd/module-helloworld) for an example.

Events are published via `$game->getEventManager()->publish()` and can pass an
`array()` which represents the context of the event. This `array()` is a so-called
"in-out" variable, so changes made to the `array()` in `handleEvent()` calls will
be visible to the publisher. This is how hooks will communicate their input to
the publisher.

### Modules
Modules extend the core or provide some additional functionality. Currently, supported
functionality includes adding/modifying database tables and/or subscribing to events.

Scenes are modules, providing `Scene` models to the database. You can also
imagine any number of utility modules, like analytic event aggregators or whole
new permission systems.

#### Installing a Module

Modules are installed via [Composer](http://getcomposer.org) packages. Composer
is a common dependency manager for PHP, providing centralized storage and easy
installation of code. We are hosting our own repository of packages at http://code.lot.gd.

1. To install a new module, add it as a Composer dependency. See
the example of specifying the HelloWorld module in the
[crate-sample](https://github.com/lotgd/crate-sample) repo.
1. Run `composer update` to install the module's code.
1. Finally, register any newly added modules with the core by using the `daenerys` tool:
```
vendor/bin/daenerys module:register
```

Note: modules are added to crates, so these instructions are to be run from a crate
directory.

#### Making a Module

1. Make a module by starting with the code in the `module-project` repo:
```
# --no-secure-http is temporarily required while we setup HTTPS on code.lot.gd
composer create-project --no-secure-http --repository http://code.lot.gd -s dev lotgd/module-project fancymodule
```
This creates a new directory, `fancymodule`, with some boilerplate code.

1. Inside your module's directory, modify the `composer.json` file to include information specific to your module.

1. Change the namespace, name of the file and classname of `src/MyModule.php` and put your code there.

1. Finally, either push your module to GitHub and add it to our module repository at [code.lot.gd](https://github.com/lotgd/code.lot.gd) or put it somewhere `composer` can find it for your crate. See [Composer Repositories](https://getcomposer.org/doc/05-repositories.md) for more about how `composer` finds dependencies.

## Development Environment

To ensure a consistent development environment, we encourage you to use Vagrant,
which is a VM environment that runs on OS X, Linux or Windows.

### Install Vagrant
* Download from https://www.vagrantup.com/downloads.html and install following the instructions on the site.
* You'll need virtualization software like virtualbox, https://www.virtualbox.org/wiki/Downloads

```bash
# Create a project directory somewhere, like ~/vagrant for example:
mkdir ~/vagrant
cd ~/vagrant

# Create a vagrant instance with a basic Ubuntu setup:
vagrant init ubuntu/trusty64

# Start your instance:
vagrant up

# Connect to your instance:
vagrant ssh
```

### Install the necessary packages
```bash
# Install git:
sudo apt-get -y install git

# Install zip/unzip for composer's use
sudo apt-get -y install zip unzip

# Install php7:
sudo LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get -y install php7.0 php7.0-fpm php7.0-mysql php7.0-mbstring php7.0-sqlite php-xml

# Install composer:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install phpunit:
wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
sudo mv phpunit.phar /usr/local/bin/phpunit
```

### Clone the repo and test
```
cd /vagrant
git clone https://github.com/lotgd/core.git
cd core
composer install
./t
```

## Contributing
Looking to help us? Awesome! Check out the [Help Wanted Issues](https://github.com/lotgd/core/labels/help%20wanted) to find out what needs to be done, or reach out on Slack.

Lots of communication is happening on our [Slack channel](http://lotgd.slack.com). Reach out to austenmc by opening an issue or contacting @austenmc on [Dragon Prime](http://dragonprime.net).

Some notes:
* Pull requests cannot be accepted that break the continuous integration checks we have in place (like tests, for example).
* Please include tests for new functionality. Not sure how to test? Say so in your PR and we'll help you.
* Our git workflow requires squashing your commits into something that resembles a reasonable story, rebasing them onto master, and pushing instead of merging. We want our commit history to be as clean as possible.

Workflow should be something like:
```bash
# Start this flow from master:
git checkout master

# Create a new feature branch, tracking origin/master.
git checkout -b feature/my-feature-branch -t origin/master

# Make some awesome commits and put up a pull request! Don't forget to push your branch to remote before creating the PR. Try something like hub (https://hub.github.com/) if you want to create PRs from the command line.
...

# If necessary, squash your commits to ensure a clean commit history.
git rebase -i

# Edit the last commit message, saying you want to close the PR by adding "closes #[PR number]" to the message.
git commit --amend

# Rebase to ensure you have the latest changes.
git pull --rebase

# Push to remote.
git push origin feature/my-feature-branch:master

# Delete your feature branch.
git branch -D feature/my-feature-branch
```

## Contributors

Leads
* [vassyli](https://github.com/vassyli)
* [austenmc](https://github.com/austenmc)

Other Contributors
* [nekosune](https://github.com/nekosune)
