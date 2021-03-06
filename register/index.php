<?php
session_start();

require "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::create("../");
$dotenv->load();

$name = $_POST["name"];
$surname = $_POST["surname"];
$email = $_POST["email"];
$major = $_POST["major"];
$year = $_POST["year"];
$event = $_POST["event"];
$departments = "";
if(isset($_POST["involved-0"])){
    $departments = $departments.";sponsorship";
}
if(isset($_POST["involved-1"])){
    $departments = $departments.";design";
}
if(isset($_POST["involved-2"])){
    $departments = $departments.";logistics";
}
if(isset($_POST["involved-3"])){
    $departments = $departments.";marketing";
}
if(isset($_POST["involved-4"])){
    $departments = $departments.";webdev";
}
if(isset($_POST["involved-5"])){
    $departments = $departments.";staff";
}
if(isset($_POST["involved-6"])){
    $departments = $departments.";hackerxperience";
}
$similar = $_POST["similar"];
$english = $_POST["confidence"];
$linkedin = $_POST["linkedin"];
$github = $_POST["github"];
$website = $_POST["website"];
$description = $_POST["description"];
$extra = $_POST["extra"];

$hash = bin2hex(random_bytes(32));

$conn = new mysqli(getenv('DB_SERVER'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_NAME'));
if($conn->connect_error){
    $_SESSION["status"] = "database";
    header("Location: ../");
    die();
}

$sql = "SELECT * FROM application WHERE email = '" . $email . "' AND confirmed = 0;";
$result = $conn->query($sql);
if($result->num_rows > 0){
    $_SESSION["status"] = "registered";
    $conn->close();
    header("Location: ../");
    die();
}

$sql = "SELECT * FROM application WHERE email = '" . $email . "' AND confirmed = 1;";
$result = $conn->query($sql);
if($result->num_rows > 0){
    $_SESSION["status"] = "confirmed";
    $conn->close();
    header("Location: ../");
    die();
}

$sql = "INSERT INTO application (email, hash, name, surname, major, year, event, departments, similar, english, linkedin, github, website, description, extra)
VALUES ('" . $email . "', '" . $hash . "', '" . $name . "', '" . $surname . "', '" . $major . "', " . $year . ", '" . $event . "', '" . $departments . "', '" . $similar . "', " . $english . ", '" . $linkedin . "', '" . $github . "', '" . $website . "', '" . $description . "', '" . $extra . "');";

if($conn->query($sql) !== TRUE){
    $_SESSION["status"] = "database";
    $conn->close();
    header("Location: ../");
    die();
}

$conn->close();

$template = file_get_contents("templates/confirm.html");
$template = str_replace('{{confirm_hash}}', $hash, $template);

use SendGrid\Mail\Content;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\To;

$subject = "Confirm your email to apply as organiser for KTHack 2020!";
$fromEmail = "noreply@kthack.com";
$fromName = "KTHack";
$toEmail = $email;
$htmlContent = $template;

$from = new From($fromEmail, $fromName);
$to = new To($toEmail);

$content = new Content("text/html", $htmlContent);

$mail = new Mail($from, $to, $subject);
$mail->addContent($content);

$sg = new SendGrid(getenv('SENDGRID_KEY'));

$response = $sg->client->mail()->send()->post($mail);

if($response->statusCode() == 202){
    echo 'Mail sent!';
}
else{
    echo 'Mail not sent!';
}

// Send email to webdev for testing

$template = file_get_contents("templates/admin.html");
$template = str_replace('{{name}}', $name." ".$surname, $template);
$template = str_replace('{{email}}', $email, $template);

$subject = "[KTHack] New application";
$fromEmail = "noreply@kthack.com";
$fromName = "KTHack";
$toEmail = "contact@kthack.com";
$htmlContent = $template;

$from = new From($fromEmail, $fromName);
$to = new To($toEmail);

$content = new Content("text/html", $htmlContent);

$mail = new Mail($from, $to, $subject);
$mail->addContent($content);

$sg = new SendGrid(getenv('SENDGRID_KEY'));

$response = $sg->client->mail()->send()->post($mail);

if($response->statusCode() == 202){
    echo 'Test mail sent!';
}
else{
    echo 'Test mail not sent!';
}

$_SESSION["status"] = "done";
header("Location: ../");
die();
?>

