<?php
session_start(); // Start the session

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: manual_attendance.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Sending Progress</title>
    <link rel="icon" href="images/favicon_io/favicon.ico" type="image/x-icon">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            text-align: center;
            margin: 20px;
        }
        h2 {
            color: #333;
        }
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
        }
        th {
            background: #28a745;
            color: white;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .failed {
            color: red;
            font-weight: bold;
        }
        /* Progress Bar */
        .progress-container {
            width: 80%;
            background: #e0e0e0;
            margin: 20px auto;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar {
            width: 0%;
            height: 100%;
            background: #28a745;
            transition: width 0.3s ease-in-out;
        }
    </style>
</head>
<body>

<h2>Email Sending Progress</h2>

<!-- Progress Bar -->
<div class="progress-container">
    <div id="progressBar" class="progress-bar"></div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Email</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody id="progressTable">
        <tr>
            <td colspan="4">Waiting for progress...</td>
        </tr>
    </tbody>
</table>

<script>
    const eventSource = new EventSource("mail.php");
    const tableBody = document.getElementById("progressTable");
    const progressBar = document.getElementById("progressBar");

    let totalStudents = 0;
    let count = 0;

    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);

        // When the total number of students is received
        if (data.status === "total_students") {
            totalStudents = data.total;
        } 
        // Handle case when no students are present
        else if (data.status === "no_students") {
            tableBody.innerHTML = `<tr><td colspan="4">${data.message}</td></tr>`;
            progressBar.style.width = "100%";
            eventSource.close();
        } 
        // Success or failed cases (email sent or failed)
        else if (data.status === "success" || data.status === "failed") {
            let rowNumber = tableBody.rows.length;
            tableBody.innerHTML += `
                <tr>
                    <td>${rowNumber}</td>
                    <td>${data.name}</td>
                    <td>${data.email}</td>
                    <td class="${data.status === "success" ? "success" : "failed"}">
                        ${data.status === "success" ? "Sent ✅" : `Failed ❌ (${data.error})`}
                    </td>
                </tr>`;

            // Update progress bar (calculating the percentage of emails sent)
            count++;
            let progress = (count / totalStudents) * 100;
            progressBar.style.width = progress + "%";
        } 
        // Completed case (all emails processed)
        else if (data.status === "completed") {
            tableBody.innerHTML += `<tr><td colspan="4">${data.message}</td></tr>`;
            progressBar.style.width = "100%";
            eventSource.close();
        }
    };

    // Debugging: log every event to check what is received from the server
    eventSource.onerror = function(error) {
        console.error("EventSource failed:", error);
    };
</script>

</body>
</html>
