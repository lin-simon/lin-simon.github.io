<?php
if (isset($_POST['submit'])) {
  // Get the form data
  $name = $_POST['name'];
  $email = $_POST['email'];
  $subject = $_POST['subject'];
  $body = $_POST['body'];

  // Set up the email message
  $to = 'lin.simon2003@gmail.com';
  $headers = "From: $name <$email>\r\n";
  $message = "Subject: $subject\r\n\r\n$body";

  // Send the email
  if (mail($to, $subject, $message, $headers)) {
    echo 'Your message has been sent.';
  } else {
    echo 'There was an error sending your message.';
  }
}
?>