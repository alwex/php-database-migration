PhpDbMigration - full PHP database migration tool
=================================================

This is a full standalone PHP tool based on symfony console and inspired by the rails database migration tool and MyBatis.
It merge the functionnality of the two tools and has been desined to be as flexible as possible.

Adding an environment
---------------------
The first thing to do before playing with SQL migrations is to add an environment, let's add the dev one.

![alt tag](http://tikotepadventure.com/files/php-database-migration/addenv.gif)

Initialization
--------------
Once the environment is added, you have to initialize it (create the changelog table on the good database) 

![alt tag](http://tikotepadventure.com/files/php-database-migration/init.gif)

Create a migration
------------------
It is time to create our first migration file.

![alt tag](http://tikotepadventure.com/files/php-database-migration/create.gif)

Migrations file are like this

    --// add table users
    -- Migration SQL that makes the change goes here.
    create table users (id integer, name text);
    -- @UNDO
    -- SQL to undo the change goes here.
    drop table users;

Up and down
------------------
You can now up all the pending migrations. If you decided to down a migration, the last one will be downed alone to prevent from mistake. You will be asked to confirm the downgrade of your database before runing the real SQL script. 
![alt tag](http://tikotepadventure.com/files/php-database-migration/status.gif)

For developement purpose, it is also possible to up a single migration without taking care of the other ones.

![alt tag](http://tikotepadventure.com/files/php-database-migration/uponly.gif)

Same thing for down

![alt tag](http://tikotepadventure.com/files/php-database-migration/downonly.gif)
