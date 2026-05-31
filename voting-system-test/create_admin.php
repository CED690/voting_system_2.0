<?php
/**
 * create_admin.php
 * A utility script to seed/create an Admin account in the university_voting database.
 * You can run this by opening http://localhost/voting-system-test/create_admin.php in your browser
 * or by executing `php create_admin.php` in your command line.
 */

require_once __DIR__ . '/system-files/apps/config/dbconnection.php';

use apps\config\dbconnection;

header('Content-Type: text/html; charset=utf-8');

try {
    $dbClass = new dbconnection();
    $db = $dbClass->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Admin Credentials to create
    $loginID   = 'admin';
    $email     = 'admin@university.edu';
    $password  = 'admin12345'; // You can change this to your desired password
    $firstname = 'System';
    $lastname  = 'Administrator';
    $role      = 'admin';

    // Check if the admin already exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE loginID = :loginID OR email = :email");
    $stmt->execute([':loginID' => $loginID, ':email' => $email]);
    $exists = (bool) $stmt->fetchColumn();

    if ($exists) {
        echo "<div style='font-family: sans-serif; padding: 20px; border-radius: 8px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; max-width: 600px; margin: 50px auto;'>";
        echo "<h2 style='margin-top: 0;'>Admin Already Exists</h2>";
        echo "<p>An administrator account with login ID <strong>{$loginID}</strong> or email <strong>{$email}</strong> already exists in the database.</p>";
        echo "<p>You can log in directly using the main login page with these credentials.</p>";
        echo "</div>";
    } else {
        // Hash the password securely using BCRYPT (same as the User model)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $insertStmt = $db->prepare("
            INSERT INTO users (loginID, firstname, lastname, email, password, roles)
            VALUES (:loginID, :firstname, :lastname, :email, :password, :roles)
        ");

        $insertStmt->execute([
            ':loginID'   => $loginID,
            ':firstname' => $firstname,
            ':lastname'  => $lastname,
            ':email'     => $email,
            ':password'  => $hashedPassword,
            ':roles'     => $role
        ]);

        echo "<div style='font-family: sans-serif; padding: 20px; border-radius: 8px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; max-width: 600px; margin: 50px auto;'>";
        echo "<h2 style='margin-top: 0;'>✓ Admin Account Created Successfully!</h2>";
        echo "<p>An administrator account has been seeded into your <strong>university_voting</strong> database.</p>";
        echo "<table style='border-collapse: collapse; width: 100%; margin-top: 15px;'>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>Login ID / Username:</td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><code>{$loginID}</code></td></tr>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>Email Address:</td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><code>{$email}</code></td></tr>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>Password:</td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><code>{$password}</code></td></tr>";
        echo "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>Role:</td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><span style='background: #007bff; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.85em;'>{$role}</span></td></tr>";
        echo "</table>";
        echo "<p style='margin-top: 20px; font-size: 0.9em; color: #6c757d;'><strong>Security Note:</strong> For security reasons, please delete this <code>create_admin.php</code> file from your folder once you have successfully logged in.</p>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; border-radius: 8px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; max-width: 600px; margin: 50px auto;'>";
    echo "<h2 style='margin-top: 0;'>Database Connection Error</h2>";
    echo "<p>Could not connect to the database. Error details:</p>";
    echo "<pre style='background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;'>{$e->getMessage()}</pre>";
    echo "<p>Please ensure that MySQL is running in XAMPP and that the database <strong>university_voting</strong> exists.</p>";
    echo "</div>";
}
