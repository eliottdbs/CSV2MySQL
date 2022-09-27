CSV2MySQL
==============

Forked from [sanathp/largeCSV2mySQL](https://github.com/sanathp/largeCSV2mySQL).

A PHP script to import very large CSV file to MySQL database.

This script uploads a CSV up to 2 Million rows in 60 seconds.

<h3> Instructions </h3>
<ol>
    <li>Create a table in your Mysql database to which you want to import</li>
    <li>Grant the right permissions to the user who is going to connect</li>
    <li>Open the PHP file from your localhost server</li>
    <li>Enter all the fields</li>
    <li>Click on upload button</li>
</ol>

The fastest way to import a CSV file is to use Mysql LOAD DATA command.

PS: server configuration might be reviewed (authorize file upload, file size, enable mysqli extension, enable 'LOAD DATA' query, ...).
