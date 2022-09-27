<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- CSS only -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <title>CSV to MySQL</title>
</head>

<body class="p-5">
    <h1>CSV to MySQL</h1>
    <p>This Php script will import very large CSV files to MYSQL database (up to ~2m<sup>n</sup>/min).</p>

    <?php
    // Turn off all error reporting
    error_reporting(0);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            break;

        case 'POST':
            $request = &$_POST;

            // Initialize variables
            $server_address = $request['server_address'] or "127.0.0.1:3306";
            $username = $request['username'] or "";
            $password = $request['password'] or "";
            $db_name = $request['db_name'] or "";
            $table_name = $request['table_name'] or "";
            $csv_delimiter = $request['csv_delimiter'] or ";";
            $csv_end_lines = $request['csv_end_lines'] or "\n";
            $csv_file = $_FILES["csv_file"] or "";

            $step_1 = ['message' => "Not executed."];
            $step_2 = ['message' => "Not executed."];
            $step_3 = ['message' => "Not executed."];
            $step_4 = ['message' => "Not executed."];
            $step_5 = ['message' => "Not executed."];

            // Bad request if any of these field is empty
            if (empty($username) || empty($db_name) || empty($table_name) || empty($csv_file)) {
                $step_1['err_code'] = 400;
                $step_1['message'] = "Emtpy field.";
            } else {
                $target_dir = "./";
                $target_file = $target_dir . basename($csv_file["name"]);
                $filetype = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Check file size and file format
                if ($csv_file["size"] > 2000000) {
                    $step_1['err_code'] = 400;
                    $step_1['message'] = "File is too large.";
                } elseif ($filetype != "csv" && $filetype != "txt") {
                    $step_1['err_code'] = 400;
                    $step_1['message'] = "Only CSV and TXT files are allowed.";
                } else {
                    $step_1['message'] = "<span>The form is valid.</span>";

                    // Upload file in the script directory
                    if (!move_uploaded_file($csv_file["tmp_name"], $target_file)) {
                        $step_2['err_code'] = 500;
                        $step_2['message'] = "Error while uploading the file.";
                    } else {
                        $step_2['message'] = "<span>The file ".htmlspecialchars(basename($csv_file["name"]))." has been uploaded.</span>";

                        // Database connection
                        $cons = mysqli_connect("$server_address", "$username", "$password", "$db_name");
                        if ($err = mysqli_connect_error()) {
                            $step_3['err_code'] = 500;
                            $step_3['message'] = $err;
                        } else {
                            $step_3['message'] = "Database connection established.";

                            // Count records before INSERT
                            $result1 = mysqli_query($cons, "select count(*) count from $table_name");
                            $r1 = mysqli_fetch_array($result1);
                            $count_1 = (int) $r1['count'];

                            // Query to load file into database
                            mysqli_query($cons, 'LOAD DATA LOCAL INFILE "'.$csv_file["name"].'" INTO TABLE '.$table_name.' FIELDS TERMINATED by \''.$csv_delimiter.'\' LINES TERMINATED BY \''.$csv_end_lines.'\'') or $err = mysqli_error($cons);
                            if (isset($err)) {
                                $step_4['err_code'] = 500;
                                $step_4['message'] = $err;
                            } else {
                                // Count records after INSERT
                                $result2 = mysqli_query($cons, "select count(*) count from $table_name");
                                $r2 = mysqli_fetch_array($result2);
                                $count_2 = (int) $r2['count'];

                                // Calculate the difference
                                $diff = $count_2 - $count_1;
                                if ($diff > 0) {
                                    $step_4['message'] = "<span>Success! Data have been imported. $diff new records have been added to the table.</span>";
                                } else {
                                    $step_4['err_code'] = 200;
                                    $step_4['message'] = "No data imported.";
                                }
                            }

                        }
                    }
                    
                    // Delete the file
                    if (unlink($csv_file["name"])) {
                        $step_5['message'] = "<span>The file ".$csv_file["name"]." has been deleted.</span>";
                    } else {
                        $step_5['err_code'] = 500;
                        $step_5['message'] = "Error while deleting the file.";
                    }
                }
            }
    ?>

            <!-- Show resume -->
            <div id="steps">
                <h3> Script steps </h3>
                <ol>
                    <li id="valid_form_step" class="step">
                        <span class="step_title">Form validation:</span>
                        <?= isset($step_1['err_code']) ? print_error($step_1['err_code'], $step_1['message']) : $step_1['message'] ?>
                    </li>
                    <li id="upload_step" class="step">
                        <span class="step_title">File upload:</span>
                        <?= isset($step_2['err_code']) ? print_error($step_2['err_code'], $step_2['message']) : $step_2['message'] ?>
                    </li>
                    <li id="import_step" class="step">
                        <span class="step_title">Database connection:</span>
                        <?= isset($step_3['err_code']) ? print_error($step_3['err_code'], $step_3['message']) : $step_3['message'] ?>
                    </li>
                    <li id="import_step" class="step">
                        <span class="step_title">Data import:</span>
                        <?= isset($step_4['err_code']) ? print_error($step_4['err_code'], $step_4['message']) : $step_4['message'] ?>
                    </li>
                    <li id="delete_step" class="step">
                        <span class="step_title">File deletion:</span>
                        <?= isset($step_5['err_code']) ? print_error($step_5['err_code'], $step_5['message']) : $step_5['message'] ?>
                    </li>
                </ol>
            </div>

    <?php
            break;

        default:
            print_error(405, "");
            break;
    }
    ?>

    <form class="mb-5 w-25" style="min-width: 700px;" method="POST" enctype="multipart/form-data">
        <div class="form-group row mb-3">
            <label for="server_address" class="col-sm-5 col-form-label text-end">Mysql server address <small>(or)</small> hostname</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="server_address" id="server_address" placeholder="127.0.0.1:3306">
            </div>
        </div>
        <div class="form-group row mb-3">
            <label for="username" class="col-sm-5 col-form-label text-end">Username</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="username" id="username" placeholder="root" required>
            </div>
        </div>
        <div class="form-group row mb-3">
            <label for="password" class="col-sm-5 col-form-label text-end">Password</label>
            <div class="col-sm-7">
                <input type="password" class="form-control" name="password" id="password" placeholder="root">
            </div>
        </div>
        <div class="form-group row mb-3">
            <label for="db_name" class="col-sm-5 col-form-label text-end">Database name</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="db_name" id="db_name" placeholder="shop" required>
            </div>
        </div>

        <div class="form-group row mb-3">
            <label for="table_name" class="col-sm-5 col-form-label text-end">Table name</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="table_name" id="table_name" placeholder="articles" required>
            </div>
        </div>

        <div class="form-group row mb-3">
            <label for="csv_delimiter" class="col-sm-5 col-form-label text-end">CSV delimiter</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="csv_delimiter" id="csv_delimiter" placeholder=";">
            </div>
        </div>

        <div class="form-group row mb-3">
            <label for="csv_end_lines" class="col-sm-5 col-form-label text-end">CSV end lines</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="csv_end_lines" id="csv_end_lines" placeholder="\n">
            </div>
        </div>

        <div class="form-group row mb-3">
            <label for="csv_file" class="col-sm-5 col-form-label text-end">Choose a file to transfer</label>
            <div class="col-sm-7">
                <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv,.txt">
            </div>
        </div>

        <div class="form-group row mb-3">
            <label for="login" class="col-sm-5 col-form-label text-end"></label>
            <div class="col-sm-7">
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </div>
    </form>

    <h3> Instructions </h3>
    <ol>
        <li>Create a table in your Mysql database to which you want to import</li>
        <li>Grant the right permissions to the user who is going to connect</li>
        <li>Open the PHP file from your localhost server</li>
        <li>Enter all the fields</li>
        <li>Click on upload button</li>
    </ol>

    <h3> Facing Problems ? Some of the reasons can be the ones shown below </h3>
    <ol>
        <li>Check if the table to which you want to import is created and the datatype of each column matches with the data in the file.</li>
        <li>If fields in your file are not separated by commas, you can change it in the "CSV delimiter" above.</li>
        <li>If each tuple in your file are not one below other (i.e not seperated by a new line), you can change it in the "CSV end lines" above.</li>
        <li>"No data imported" or the number of inserted rows is not as expected? Duplicate Primary Key might occured. Check that you didn't upload the same file twice.</li>
    </ol>
</body>
</html>

<?php
function print_error($err_code, $details)
{
    $HTTP_VERSION = "HTTP/1.1";

    switch ($err_code) {
        case 400:
            $err_msg = "Bad Request";
            break;

        case 405:
            $err_msg = "Method Not Allowed";
            break;

        case 500:
            $err_msg = "Internal Server Error";
            break;

        default:
            $err_code = '500';
            $err_msg = "Internal Server Error";
            break;
    }

    echo ("<strong class='text-danger'>" . $err_msg . ": " . $details . "</strong>");
    header("$HTTP_VERSION $err_code $err_msg");
}
?>