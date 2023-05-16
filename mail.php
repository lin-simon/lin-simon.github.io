<?php
if(isset($_POST['submit'])){
    $to = "lin.simon2003@gmail.com"; // replace with your email address
    $subject = "Contact form submission";
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['subject'];
    $headers = "From: $email \r\n";
    $headers .= "Reply-To: $email \r\n";
    
    $body = "From: $name\n E-Mail: $email\n Message:\n $message";
    
    // Send email
    mail($to, $subject, $body, $headers);
    exit();
}
?>