<?php
include '../config/db.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $title = $_POST['job_title'];
    $dept = $_POST['department'];
    $type = $_POST['employment_type'];
    $salary = $_POST['salary'];
    $location = $_POST['location'];
    $desc = $_POST['description'];

    $stmt = $conn->prepare("
        INSERT INTO jobs
        (job_title, department, employment_type, salary, location, description)
        VALUES (?,?,?,?,?,?)
    ");

    if(!$stmt){
        die("PREPARE ERROR: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $title, $dept, $type, $salary, $location, $desc);

    if(!$stmt->execute()){
        die("EXECUTE ERROR: " . $stmt->error);
    }

<<<<<<< HEAD
<<<<<<< HEAD
    header("Location: jobs.php?success=1");
    exit();
=======
    echo "SUCCESS INSERTED"; // temporary test
>>>>>>> 0b3fa6079a9c5adc408cef2ff7364f1e35f8d539
=======
    echo "SUCCESS INSERTED"; // temporary test
>>>>>>> 0b3fa6079a9c5adc408cef2ff7364f1e35f8d539

}
?>