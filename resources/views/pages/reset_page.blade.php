<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config("app.name") }} - Reset Password Page</title>

</head>

<body>


    <h3 style="text-align: center">{{ config("app.name") }} Reset Page</h3>

    <form style="margin: auto;width: 200px;display:block;text-align:center" method="POST" action="{{ route("manager.reset_password", [
        "reset_code"=> $reset_code,
    ]) }}">

        
        Password: <input type="password" name="password" id="password"> <br /><br />
        Confirm Password: <input type="password" name="confirm_password" id="confirm_password"> <br /><br />

        <input type="submit" value="Reset">
    </form>


</body>

</html>