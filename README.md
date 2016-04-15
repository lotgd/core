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

* Backward compatibility. It may be controversial since it would mean existing admins can't upgrade to this version, but keeping the old database schema would be seriously limiting to the quality of the codebase. If we actually finish this, then writing an importer wouldn't be too hard.

## Development Environment

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

# Install composer:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install php7:
sudo LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get -y install php7.0 php7.0-fpm php7.0-mysql php7.0-mbstring php-xml
```

### Clone the repo and test
```
sudo apt-get -y install git
cd /vagrant
git clone git@github.com:lotgd/core.git
cd core
composer install
./t
```
