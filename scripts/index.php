<?php
// Database connection credentials
$host = 'your-rds-endpoint.amazonaws.com'; // Replace with your RDS instance endpoint
$username = 'your_rds_username'; // Replace with your RDS username
$password = 'your_rds_password'; // Replace with your RDS password
$dbname = 'your_database_name'; // Name of the database to be created

// Connect to MySQL
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the 'names' table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS names (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )";
    $pdo->exec($createTableSQL);

} catch (PDOException $e) {
    die("Error connecting or creating the database: " . $e->getMessage());
}

// Function to insert a name
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO names (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);
    }
}

// Function to delete a name
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM names WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

// Function to update a name
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $stmt = $pdo->prepare("UPDATE names SET name = :name WHERE id = :id");
    $stmt->execute(['name' => $name, 'id' => $id]);
}

// Fetch all names
$stmt = $pdo->query("SELECT * FROM names ORDER BY id DESC");
$names = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Name Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        h1, h2 {
            text-align: center;
        }
        form {
            margin-bottom: 20px;
        }
        form input[type="text"] {
            padding: 5px;
            font-size: 16px;
        }
        form button {
            padding: 5px 10px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        form button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #DDDDDD;
        }
        a {
            text-decoration: none;
            color: #008CBA;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Name Manager</h1>

    <form method="post">
        <input type="text" name="name" placeholder="Enter a name" required>
        <button type="submit" name="add">Add</button>
    </form>

    <h2>Names List</h2>
    <table>
        <tr>
            <th>Name</th>
            <th>Action</th>
        </tr>
        <?php foreach ($names as $name): ?>
            <tr>
                <td><?= htmlspecialchars($name['name']) ?></td>
                <td>
                    <a href="?delete=<?= $name['id'] ?>" onClick="return confirm('Are you sure you want to delete?')">Delete</a> |
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $name['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($name['name']) ?>" style="width: 150px;">
                        <button type="submit" name="update">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
