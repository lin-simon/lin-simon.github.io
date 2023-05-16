function sendMail() {
    var name = document.getElementById("name").value;
    var email = document.getElementById("email").value;
    var message = document.getElementById("message").value;

    if (!name || !email || !message) {
        alert("Please fill out all fields.")
        return;
    }

    var params = {
        name: name,
        email: email,
        message: message,
    };

    const serviceID = "service_z8z3myc"; //TODO: add to a dotenv
    const templateID = "template_5udphrs";
    
    emailjs.send(serviceID, templateID, params)
      .then(res=>{
          document.getElementById("name").value = "";
          document.getElementById("email").value = "";
          document.getElementById("message").value = "";
          console.log(res);
          alert("Your email was received!")
  
      })
      .catch(err=>console.log(err));
}
  