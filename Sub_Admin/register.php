<?php
// Include your database configuration file
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize form data
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $mailId = $_POST['mailId'] ?? '';
    $mobileNo = $_POST['mobileNo'] ?? '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : ''; // Hash the password
    $experience = $_POST['experience'] ?? 0;
    $designation = $_POST['designation'] ?? '';

    // Handle the photo file upload
    $photo = null; // Default to null if no file is uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo = 'uploads/' . basename($_FILES['photo']['name']); // Save to 'uploads' folder
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    // Handle the PDF document upload
    $documents = null; // Default to null if no document is uploaded
    if (isset($_FILES['documents']) && $_FILES['documents']['error'] == 0) {
        $fileType = pathinfo($_FILES['documents']['name'], PATHINFO_EXTENSION);
        if (strtolower($fileType) == 'pdf') {
            $documents = 'uploads/' . basename($_FILES['documents']['name']); // Save to 'uploads' folder
            move_uploaded_file($_FILES['documents']['tmp_name'], $documents);
        } else {
            $error = "Only PDF files are allowed for documents!";
        }
    }

    // If no errors, proceed to store data in the database
    if (!isset($error)) {
        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO Employee (name, address, mailId, mobileNo, password, experience, documents, photo, designation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssissss', $name, $address, $mailId, $mobileNo, $password, $experience, $documents, $photo, $designation);

        // Execute the statement and check for errors
        if ($stmt->execute()) {
            // Redirect to a success page (or any other page)
            header('Location: login.php');
            exit(); // Ensure no further code is executed after redirect
        } else {
            $error = "Error in registration!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>Employee Registration</title>
      <style>
         @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
         * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Poppins', sans-serif;
         }
         html, body {
         display: grid;
         height: 100%;
         width: 100%;
         place-items: center;
         background: #f2f2f2;
         }
         .wrapper {
         width: 600px;
         background: #fff;
         border-radius: 15px;
         box-shadow: 0px 15px 20px rgba(0,0,0,0.1);
         }
         .wrapper .title {
         font-size: 35px;
         font-weight: 600;
         text-align: center;
         line-height: 100px;
         color: #fff;
         user-select: none;
         border-radius: 15px 15px 0 0;
         background: #0e4bf1;
         }
         .wrapper form {
         padding: 10px 30px 50px 30px;
         }
         .wrapper form .field {
         margin-top: 20px;
         position: relative;
         }
         .wrapper form .field input, .wrapper form .field textarea, .wrapper form .field select {
         height: 100%;
         width: 100%;
         outline: none;
         font-size: 17px;
         padding-left: 20px;
         border: 1px solid lightgrey;
         border-radius: 25px;
         transition: all 0.3s ease;
         }
         .wrapper form .field input:focus, form .field input:valid,
         .wrapper form .field textarea:focus, form .field textarea:valid,
         .wrapper form .field select:focus, form .field select:valid {
         border-color: #4158d0;
         }
         .wrapper form .field label {
         position: absolute;
         top: 50%;
         left: 20px;
         color: #999999;
         font-weight: 400;
         font-size: 17px;
         pointer-events: none;
         transform: translateY(-50%);
         transition: all 0.3s ease;
         }
         form .field input:focus ~ label,
         form .field input:valid ~ label,
         form .field textarea:focus ~ label,
         form .field textarea:valid ~ label,
         form .field select:focus ~ label,
         form .field select:valid ~ label {
         top: 0%;
         font-size: 16px;
         color: #4158d0;
         background: #fff;
         transform: translateY(-50%);
         }
         form .field input[type="file"] {
         padding-left: 0;
         height: auto;
         border-radius: 10px;
         }
         form .field textarea {
         height: 100px;
         border-radius: 10px;
         padding-top: 10px;
         }
         form .field input[type="submit"] {
         color: #fff;
         border: none;
         padding-left: 0;
         margin-top: -10px;
         font-size: 20px;
         font-weight: 500;
         cursor: pointer;
         background: #0e4bf1;
         transition: all 0.3s ease;
         }
         form .field input[type="submit"]:active {
         transform: scale(0.95);
         }
         form .signup-link {
         color: #262626;
         margin-top: 20px;
         text-align: center;
         }
         form .pass-link a,
         form .signup-link a {
         color: #4158d0;
         text-decoration: none;
         }
         form .pass-link a:hover,
         form .signup-link a:hover {
         text-decoration: underline;
         }
         .alert {
         text-align: center;
         color: red;
         margin-top: 10px;
         }
      </style>
   </head>
   <body>
      <div class="wrapper">
         <div class="title">Employee Registration</div>
         <form method="POST" action="" enctype="multipart/form-data">
            <div class="field">
               <input type="text" name="name" required>
               <label>Name</label>
            </div>
            <div class="field">
               <input type="text" name="address" required>
               <label>Address</label>
            </div>
            <div class="field">
               <input type="email" name="mailId" required>
               <label>Email Address</label>
            </div>
            <div class="field">
               <input type="password" name="password" required>
               <label>Password</label>
            </div>
            <div class="field">
               <input type="text" name="mobileNo" required>
               <label>Mobile No</label>
            </div>
            <div class="field">
               <input type="number" name="experience" required>
               <label>Experience (Years)</label>
            </div>
            <div class="field">
               <input type="file" name="photo" accept="image/*">
               <label>Upload Photo</label>
            </div>
            <div class="field">
               <input type="file" name="documents" accept=".pdf">
               <label>Upload Documents (PDF only)</label>
            </div>
            <div class="field">
               <input type="text" name="designation" required>
               <label>Designation</label>
            </div>
            <?php if (isset($error)) { echo "<div class='alert'>$error</div>"; } ?>
            <div class="field">
               <input type="submit" value="Register">
            </div>
         </form>
      </div>
   </body>
</html>
