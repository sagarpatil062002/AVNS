<?php
// user_list.php

include 'config.php'; // Ensure this contains your DB connection logic

function fetchRecords($conn, $entity) {
    switch ($entity) {
        case 'users':
            $query = "SELECT id, name, email, mobileNo, created_at FROM users";
            break;
        case 'Employee':
            $query = "SELECT id, name, mailId AS email, mobileNo, createdAt FROM Employee";
            break;
        case 'Freelancer':
            $query = "SELECT id, name, mailId AS email, mobileNo, createdAt FROM Freelancer";
            break;
        case 'CustomerDistributor':
            $query = "SELECT id, companyName AS name, mailId AS email, mobileNo, createdAt FROM CustomerDistributor";
            break;
        default:
            return [];
    }
    return $conn->query($query);
}

$users = fetchRecords($conn, 'users');
$employees = fetchRecords($conn, 'Employee');
$freelancers = fetchRecords($conn, 'Freelancer');
$customers_distributors = fetchRecords($conn, 'CustomerDistributor');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Super Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        table thead {
            background-color: #007BFF;
            color: #fff;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: #007BFF;
            color: white;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .container {
            width: 90%;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        p {
            text-align: center;
            font-size: 16px;
            color: #555;
        }
    </style>
</head>
<body>

<div class="container">

<h1>User Management for Super Admin</h1>

<!-- Users Section -->
<h2>Users</h2>
<?php if ($users && $users->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile No</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['mobileNo']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No users found.</p>
<?php endif; ?>

<!-- Employees Section -->
<h2>Sub Admins (Employees)</h2>
<?php if ($employees && $employees->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile No</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $employees->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['mobileNo']; ?></td>
                    <td><?php echo $row['createdAt']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No employees found.</p>
<?php endif; ?>

<!-- Freelancers Section -->
<h2>Freelancers</h2>
<?php if ($freelancers && $freelancers->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile No</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $freelancers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['mobileNo']; ?></td>
                    <td><?php echo $row['createdAt']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No freelancers found.</p>
<?php endif; ?>

<!-- Customers/Distributors Section -->
<h2>Customers/Distributors</h2>
<?php if ($customers_distributors && $customers_distributors->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Company Name</th>
                <th>Email</th>
                <th>Mobile No</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $customers_distributors->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['mobileNo']; ?></td>
                    <td><?php echo $row['createdAt']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No customers/distributors found.</p>
<?php endif; ?>

</div>

</body>
</html>
