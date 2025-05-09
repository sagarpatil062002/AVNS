<?php
ob_start(); // Start output buffering

include 'config.php';

// Fetch records dynamically based on the table name
function fetchRecords($conn, $entity) {
    $query = match ($entity) {
        'users' => "SELECT id, name, email, mobileNo, created_at FROM users",
        'Employee' => "SELECT id, name, mailId AS email, mobileNo, createdAt FROM Employee",
        'Freelancer' => "SELECT id, name, email, skills, experience FROM Freelancer",
        'CustomerDistributor' => "SELECT id, companyName AS name, mailId AS email, mobileNo, createdAt FROM CustomerDistributor",
        'Distributor' => "SELECT id, companyName AS name, mailId AS email, mobileNo, createdAt FROM Distributor",
        default => null,
    };
    return $query ? $conn->query($query) : null;
}

function exportToCSV($conn, $entity) {
    $records = fetchRecords($conn, $entity);
    if ($records && $records->num_rows > 0) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $entity . '.csv');
        
        $output = fopen("php://output", "w");
        $columns = $records->fetch_fields();
        fputcsv($output, array_map(fn($col) => $col->name, $columns));

        while ($row = $records->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit; // Ensure no further output after the CSV export
    }
}

function importFromCSV($conn, $entity, $file) {
    $handle = fopen($file, 'r');
    if ($handle) {
        $columns = fgetcsv($handle);
        $columnList = implode(", ", $columns);

        while (($data = fgetcsv($handle)) !== false) {
            $values = implode(", ", array_map(fn($val) => "'" . $conn->real_escape_string($val) . "'", $data));
            $query = "INSERT INTO $entity ($columnList) VALUES ($values)";
            $conn->query($query);
        }
        fclose($handle);
    }
}

if (isset($_GET['export'])) {
    exportToCSV($conn, $_GET['table']);
    exit; // Stop further script execution after export
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if ($file) {
        importFromCSV($conn, $_POST['table'], $file);
    }
}

$table_options = [
    'users' => 'Super Admin',
    'Employee' => 'Sub Admin',
    'Freelancer' => 'Freelancers',
    'CustomerDistributor' => 'Customer',
    'Distributor' => 'Distributor',
];

$selected_table = $_GET['table'] ?? 'users';
$records = fetchRecords($conn, $selected_table);

include 'admin_navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Super Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --success-color: #4cc9f0;
            --error-color: #f72585;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;  }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: var(--text-color);
        }
        
        .main-content {
            margin-left: 250px;
            transition: all 0.3s ease;
            margin-top: -30px;

        }
        
        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background: var(--white);
            box-shadow: var(--shadow);
            border-radius: 12px;
            margin-left: 50px;
        }
        
        h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 28px;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 25px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 15px;
            text-align: left;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            background-color: var(--white);
        }
        
        tr:hover td {
            background-color: var(--light-gray);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        select, button, input[type="file"] {
            padding: 12px 15px;
            margin: 8px 0;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        select {
            width: 250px;
            background-color: white;
            cursor: pointer;
            border: 1px solid #ccc;
        }
        
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
        }
        
        .button-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .import-section {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        button {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            cursor: pointer;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        button i {
            font-size: 16px;
        }
        
        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 16px;
        }
        
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0;
            }
            
            .container {
                margin: 20px;
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .button-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .import-section {
                width: 100%;
            }
            
            select {
                width: 100%;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="container">
        <h1>User Management for Super Admin</h1>

        <!-- Dropdown to select table -->
        <form method="GET" action="">
            <select name="table" onchange="this.form.submit()">
                <?php foreach ($table_options as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php if ($selected_table === $value) echo 'selected'; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Export and Import Buttons -->
        <div class="button-container">
            <form method="GET" action="">
                <input type="hidden" name="table" value="<?php echo $selected_table; ?>">
                <button type="submit" name="export">
                    <i class="uil uil-export"></i> Export to CSV
                </button>
            </form>

            <form method="POST" action="" enctype="multipart/form-data" class="import-section">
                <input type="hidden" name="table" value="<?php echo $selected_table; ?>">
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit">
                    <i class="uil uil-import"></i> Import from CSV
                </button>
            </form>
        </div>

        <!-- Display records -->
        <?php if ($records && $records->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <?php
                        $columns = $records->fetch_fields();
                        foreach ($columns as $col) {
                            echo "<th>" . ucfirst(str_replace('_', ' ', $col->name)) . "</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $records->fetch_assoc()): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-records">No records found for the selected table.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
ob_end_flush(); // End output buffering and send the output
?>