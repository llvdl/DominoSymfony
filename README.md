DominoSymfony
=============
An incomplete implementation of a Domino Game. The purpose of this application is to practice
and get some experience with:

* Symfony Framework
* Domain Driven Design
* Test Driven Development

License
-------
See the `LICENSE` file


Testing
-------
The test files are placed in the `tests` folder. To run the tests, run `phpunit` in the root 
directory of the cloned repository, e.g.

	phpunit --testdox

The repository tests require a database to be set up. See the configuration files 
`app/config/config.yml` and `app/config/config_dev.yml` for the database configuration.


Running
-------
The webapplication can be started using the PHP built-in webserver

	bin/console server:run
