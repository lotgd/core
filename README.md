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

## Learn More

Read more about the game at our [wiki](https://github.com/lotgd/core/wiki).

Here are some good articles to get you started:
* [Gameplay Summary](https://github.com/lotgd/core/wiki/Gameplay-Summary): if you're unfamiliar with the original Legend of the Red Dragon or Legend of the Green Dragon, learn more about what kind of game this is :).
* [Architecture Overview](https://github.com/lotgd/core/wiki/Architecture-Overview): Get a high-level overview of the pieces of a LotGD game, based on a simple example realm.

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
